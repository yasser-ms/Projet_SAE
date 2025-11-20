import psycopg2
from db import get_connection
from datetime import datetime

## ----------------------------------------- Des fonctions principales de partie serveur applicatif-----------------------------------------------------

# 🔹 Vérifier si la borne appartient au parking et est activej
def tester_borne_parking(id_parking, id_borne):
    conn = get_connection()
    cur = conn.cursor()
    query = """
        SELECT b.id_borne, b.etat
        FROM borne b
        WHERE b.id_parking = %s
        AND b.id_borne = %s;
    """
    cur.execute(query, (id_parking, id_borne))
    result = cur.fetchone()
    cur.close()
    conn.close()
    print(result)
    if result is None:
        return "ERREUR;Borne inconnue pour ce parking"

    id_borne_res, etat_borne = result
    if etat_borne != 'active':
        return f"ERREUR;Borne {id_borne_res} inactive"

    return f"Borne {id_borne_res} active dans le parking {id_parking}"

# 🔹 Vérifier un contrat et sa validité
def verifier_contrat(id_contrat):
    conn = get_connection()
    cur = conn.cursor()

    query = """
        SELECT id_contrat, date_debut, date_fin, etat_contrat, type_contrat
        FROM contrat
        WHERE id_contrat = %s;
    """
    cur.execute(query, (id_contrat,))
    contrat = cur.fetchone()

    cur.close()
    conn.close()

    if not contrat:
        return False, "Contrat inconnu"

    _, date_debut, date_fin, etat_contrat, type_contrat = contrat

    today = datetime.today().date()

    if etat_contrat.lower() != "actif":
        return False, f"Contrat {etat_contrat}"

    if date_fin < today:
        return False, "Contrat expiré"

    return True, "Contrat valide"

## verifcation si le contrat est un abonnement ou un ticketHoraire

def verif_abonnement_ou_ticketHoraire(id_contrat):
    conn = get_connection()
    cur = conn.cursor()

    # Vérifier si c’est un abonnement
    query =  """
        SELECT type_contrat 
        FROM contrat 
        WHERE id_contrat = %s;
    """
    cur.execute(query, (id_contrat,))
    
    res = cur.fetchone()

    cur.close()
    conn.close()

    if res is None:
        return None  # Contrat n'existe pas

    type_contrat = res[0].lower()

    # Normalisation
    if type_contrat == "abonnement": 
        return "abonnement"
    if type_contrat == "tickethoraire":
        return "ticketHoraire"

    return None

## Generer id penalite 

def generer_id_penalite(curseur):
    curseur.execute("SELECT id_penalite FROM penalite ORDER BY id_penalite DESC LIMIT 1")
    row = curseur.fetchone()

    if row is None:
        # Si aucune pénalité n'existe encore
        return "PNL0001"

    last_id = row[0]        # Exemple : "PNL0032"
    numero = int(last_id[3:]) + 1   # Récupère "0032" → 32 → +1 = 33
    new_id = f"PNL{numero:04d}"      # Formate → PNL0033

    return new_id

## Calculer la pénalité en fonction du type de contrat

def verifier_penalite(id_contrat, id_borne, etat_valide="expdelai_depasse"):

    connexion = get_connection()
    curseur = connexion.cursor()
    if type_borne(id_borne) != "sortie":
        return False, "La pénalité ne peut être vérifiée qu'à une borne de sortie."
    
    # Récupérer type de contrat
    type_contrat = verif_abonnement_ou_ticketHoraire(id_contrat)

    # Récupérer date + heure actuelle
    now = datetime.now()
    date_scan = now.date()
    heure_scan = now.time()

    # Générer ID pénalité
    id_penalite = generer_id_penalite(curseur)
    penalite = 0
    description = ""


    # CAS 1 : ABONNEMENT

    if type_contrat == 'abonnement':
        curseur.execute(
            "SELECT date_fin FROM contrat WHERE id_contrat = %s ",
            (id_contrat,)
        )
        row = curseur.fetchone()
        if row:
            date_expiration = row[0].date() 
            if date_scan > date_expiration:
                jours_retard = (date_scan - date_expiration).days
                penalite = jours_retard * 20   # 20€ par jour
                description = f"Retard abonnement ({jours_retard} jours)"

                curseur.execute(
                    "INSERT INTO penalite(id_penalite, id_contrat, montant_p, description) VALUES (%s, %s, %s, %s)",
                    (id_penalite, id_contrat, penalite, description)
                )

   
    # CAS 2 : TICKET HORAIRE
 
    elif type_contrat == 'ticketHoraire':
        curseur.execute(
            "SELECT date_fin FROM contrat WHERE id_contrat = %s",
            (id_contrat,)
        )
        row = curseur.fetchone()
        if row:
            dt_fin = row[0]  # timestamp complet (date + heure)
            if now > dt_fin:
                minutes_retard = int((now - dt_fin).total_seconds() // 60)
                penalite = minutes_retard * 10
                description = f"Ticket dépassé ({minutes_retard} minutes)"
                curseur.execute(
                    "INSERT INTO penalite(id_penalite, id_contrat, montant_p, description) VALUES (%s, %s, %s, %s)",
                    (id_penalite, id_contrat, penalite, description)
                )


    # -------------------
    # Mettre à jour l'historique
    # -------------------
    ## ajouter_historique(id_contrat, id_borne, etat_valide)

    connexion.commit()
    curseur.close()
    connexion.close()

    # -------------------
    # Retour
    # -------------------
    if penalite > 0:
        return True, f"Pénalité = {penalite} € ({description})"
    return True, "sortir de parking......."

## ------------------------------Autres fonction utiles----------------------------------------------

## Savoir le type de la borne ( entrée ou sortie): 

def type_borne(id_borne):
    conn = get_connection()
    cur = conn.cursor()
    query = """
        SELECT b.type_de_borne
        FROM borne b
        WHERE b.id_borne = %s;
    """
    cur.execute(query, (id_borne,))
    typeBorne = cur.fetchone()
    cur.close()
    conn.close()

    if typeBorne:
        return typeBorne[0]  # Retourne le type de borne (entrée ou sortie)
    return None

#  Ajouter un événement dans l'historique

def ajouter_historique(id_contrat, id_borne, etat_valide):
    ## partie entre dans le parking
    conn = get_connection()
    cur = conn.cursor()
    heure_scanne = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    query = """
        INSERT INTO verifie (id_contrat, id_borne, heure_scanne, etat_valide)
        VALUES (%s, %s, %s, %s)
        ON CONFLICT (id_contrat, id_borne) 
        DO UPDATE SET heure_scanne = EXCLUDED.heure_scanne,
                      etat_valide = EXCLUDED.etat_valide;
    """
    cur.execute(query, (id_contrat, id_borne, heure_scanne, etat_valide))
    conn.commit()
    cur.close()
    conn.close()

