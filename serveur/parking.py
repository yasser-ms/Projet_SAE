from db import get_connection
from datetime import datetime

## ----------------------------------------- Des fonctions principales de partie serveur applicatif-----------------------------------------------------

# 🔹 Vérifier si la borne appartient au parking et est activej
def tester_borne_parking(id_parking, id_borne):
    """
    Vérifie si une borne appartient à un parking donné et si elle est active.
    :param id_parking: L'identifiant du parking.
    :param id_borne: L'identifiant de la borne.
    :return: Un message indiquant le statut de la borne.
    """
    
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
    ##print("=== DEBUG SERVEUR ===")
    ##print("parking =", repr(id_parking))
    ##print("borne   =", repr(id_borne_res))

    if etat_borne != 'active':
        return f"ERREUR;Borne {id_borne_res} inactive"

    return f"Borne reconnue et active dans le parking "

## verifcation si le contrat est un abonnement ou un ticketHoraire

def verif_abonnement_ou_ticketHoraire(id_contrat):
    """
    Vérifie si le contrat est un abonnement ou un ticket horaire.
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: "abonnement" si c'est un abonnement, "ticketHoraire" si c'est un ticket horaire, None sinon.
    """
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

    """
    Génère un nouvel ID de pénalité unique pour la création d'une pénalité.
    :param curseur: Le curseur de la base de données pour exécuter les requêtes.
    :return: Un nouvel ID de pénalité au format "PNLXXXX".
    """  
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

def verifier_penalite(id_contrat):
    """
    Vérifie et calcule la pénalité pour un contrat donné.
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: Un tuple (bool, message) indiquant si une pénalité a été appliquée et le détail.
    """
    connexion = get_connection()
    curseur = connexion.cursor()

    # Récupérer la date et l'heure actuelles
    now = datetime.now()

    # Générer un nouvel ID de pénalité
    id_penalite = generer_id_penalite(curseur)
    penalite = 0
    description = ""

    # Récupérer la date de fin du contrat
    curseur.execute(
        "SELECT date_fin FROM contrat WHERE id_contrat = %s",
        (id_contrat,)
    )
    row = curseur.fetchone()

    if row is None:
        curseur.close()
        connexion.close()
        return False, "ERREUR;Contrat introuvable"

    date_fin = row[0]  # date_fin est un objet datetime

    # Calculer la différence en minutes entre la date de fin et la date actuelle
    if now > date_fin:
        delta = now - date_fin
        minutes_retard = int(delta.total_seconds() // 60)  # Convertir en minutes
        penalite = round(minutes_retard * 0.1, 2)  # 0,1 euro par minute
        description = f"Retard de {minutes_retard} minute(s)"

        # Insérer la pénalité dans la base de données
        curseur.execute(
            """
            INSERT INTO penalite (id_penalite, id_contrat, montant_p, description, date_creation)
            VALUES (%s, %s, %s, %s, %s)
            """,
            (id_penalite, id_contrat, penalite, description, now)
        )

    connexion.commit()
    curseur.close()
    connexion.close()

    return True, f"Pénalité = {penalite} € ({description})"
    ## ------------------------------Autres fonction utiles----------------------------------------------

## Savoir le type de la borne ( entrée ou sortie): 

def type_borne(id_borne):

    """
    Retourne le type de la borne en paramètres
    :param id_borne: L'identifiant de la borne concernée.
    :return: type_borne, qui est un string
    """



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
    """
    Ajoute un enregistrement dans la table 'verifie' pour un contrat scanné à une borne donnée.
    Si un enregistrement existe déjà pour ce contrat et cette borne, il est mis à jour.
    :param id_contrat: L'identifiant du contrat scanné.
    :param id_borne: L'identifiant de la borne où le contrat a été scanné.
    :param etat_valide: L'état de validité du contrat (encours_in, encours_out, delai_depasse).
    :return: None
    """
    
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

# Vérifier un contrat et sa validité
def verifier_contrat(id_contrat):

    """
    Vérifie si un contrat existe, et si oui : son etat.
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: Un tuple (bool, message) indiquant si le contrat existe et le détail.
    """

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
        return True, f"Contrat {etat_contrat}"

    if date_fin.date() < today:
        return True, "Contrat expiré"

    return True, "Contrat valide"

### Fonction pour vérifier si un contrat existe dans l'historique

def exist_contrat_dans_historique(id_contrat):
    """
    Vérifie si un contrat existe dans la table 'verifie'.
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: True si le contrat existe dans l'historique, False sinon.
    """


    conn = get_connection()
    cur = conn.cursor()
    query = """
        SELECT COUNT(*) 
        FROM verifie
        WHERE id_contrat = %s;
    """
    cur.execute(query, (id_contrat,))
    count = cur.fetchone()[0]
    cur.close()
    conn.close()
    if count > 0:
        return True
    return False

## Fonction pour verifier la date de fin du contrat

def verifier_validite_contrat(id_contrat):
    """
    Vérifie si la date actuelle est entre date_debut et date_fin et que le contrat est 'actif' pour un contrat donné.
    
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: True si la date actuelle est entre date_debut et date_fin, False sinon.
    """
    conn = get_connection()
    cur = conn.cursor()

    query = """
        SELECT date_debut, date_fin, etat_contrat
        FROM contrat
        WHERE id_contrat = %s AND etat_contrat = 'actif';
    """
    cur.execute(query, (id_contrat,))
    result = cur.fetchone()
    cur.close()
    conn.close()

    if not result:
        # Contrat non trouvé
        return False

    date_debut, date_fin, etat_contrat = result
    today = datetime.today().date()

    # Vérifie si la date actuelle est entre date_debut et date_fin
    if date_debut.date() <= today and today <= date_fin.date() and etat_contrat=='actif':
        return True
    else :
        return False

#fonction pour verifier si le contrat est en cours d'entrée ou de sortie
def encours_in_ou_out(id_contrat):
    """
    Vérifie l'état actuel (encours_in ou encours_out) d'un contrat dans la table 'verifie'.
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: L'état valide (encours_in ou encours_out) si le contrat existe, None sinon.
    """

    conn = get_connection()
    cur = conn.cursor()
    query = """
        SELECT etat_valide 
        FROM verifie
        WHERE id_contrat = %s;
    """
    cur.execute(query, (id_contrat,))
    etat = cur.fetchone()
    cur.close()
    conn.close()
    if etat:
        return etat[0]  # Retourne l'état valide (encours_in ou encours_out)
    return None

def update_historique(id_contrat, id_borne, etat_valide):

    """
    Met à jour l'état valide et l'heure scannée pour un contrat dans la table 'verifie'.
    :param id_contrat: L'identifiant du contrat à mettre à jour.
    :param id_borne: L'identifiant de la borne où le contrat a été scanné.
    :param etat_valide: Le nouvel état de validité du contrat (encours_in, encours_out, delai_depasse).
    :return: None
    """
    
    conn = get_connection()
    cur = conn.cursor()
    heure_scanne = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    # First try to update the specific row for this contract+borne (common case when same borne)
    query_by_borne = """
        UPDATE verifie
        SET heure_scanne = %s, etat_valide = %s,id_borne = %s
        WHERE id_contrat = %s  
    """
    cur.execute(query_by_borne, (heure_scanne, etat_valide, id_borne, id_contrat))

    conn.commit()
    cur.close()
    conn.close()

    
def scanne_contrat_entree(id_contrat, id_borne):
    """
    Gère le scan d'un contrat à une borne d'entrée.
    :param id_contrat: L'identifiant du contrat scanné.
    :param id_borne: L'identifiant de la borne où le contrat a été scanné.
    :return: Un tuple (bool, message) indiquant si le contrat est valide pour l'entrée et le détail.
    """
    conn = get_connection()
    cur = conn.cursor()
    
    if(verifier_contrat(id_contrat)[0] == True):

        if(verifier_validite_contrat(id_contrat) == True):

            if(exist_contrat_dans_historique(id_contrat) == False):

                ajouter_historique(id_contrat, id_borne, "encours_in")

            else : ## Verifier si encours_in ou encours_out -> fonction 
                etat = encours_in_ou_out(id_contrat)
                if etat == "encours_out":
                    update_historique(id_contrat, id_borne, "encours_in")
                else: ## Le contrat est déjà en cours d'entrée
                    return False,"ERREUR;Contrat déjà en cours d'entrée"
        else:
            return False,"ERREUR;Contrat non valide (expiré ou inactif)"
    else:
        return verifier_contrat(id_contrat)[0], verifier_contrat(id_contrat)[1]
    cur.close()
    conn.close()
    return True, "Contrat valide pour entrée"
    
def scanne_contrat_sortie(id_contrat, id_borne):

    """
    Gère le scan d'un contrat à une borne de sortie.
    :param id_contrat: L'identifiant du contrat scanné.
    :param id_borne: L'identifiant de la borne où le contrat a été scanné.
    :return: Un tuple (bool, message) indiquant si le contrat est valide pour la sortie et le détail.
    """

    conn = get_connection()
    cur = conn.cursor()
    
    if(verifier_contrat(id_contrat)[0] == True):

        if(verifier_validite_contrat(id_contrat) == True):

            if(exist_contrat_dans_historique(id_contrat) == True):
                
                if( encours_in_ou_out(id_contrat) == "encours_in" ):
                    update_historique(id_contrat, id_borne, "encours_out")
                else:
                    return False, "ERREUR;Contrat déjà en cours de sortie"
            else :
                return False, "ERREUR;Contrat n'a pas d'entrée enregistrée"
        else:
           update_historique(id_contrat, id_borne, "delai_depasse")
           ## Ajouter une pénalité
           verifier_penalite(id_contrat)
           return False,"ERREUR;Contrat non valide et penalite attribuée"
    else:
        return verifier_contrat(id_contrat)
    
    cur.close()
    conn.close()
    return True, "Contrat valide pour sortie"

    
def scanne_contrat(id_contrat, id_borne):

    type_borne_val = type_borne(id_borne)
    if type_borne_val == "entree":
        return scanne_contrat_entree(id_contrat, id_borne)
    elif type_borne_val == "sortie":
        return scanne_contrat_sortie(id_contrat, id_borne) 
    else:
        return False, "ERREUR;Type de borne inconnu"