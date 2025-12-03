"""
parking.py

Fichier principal côté serveur pour la gestion des parkings, bornes et contrats.
Il contient des fonctions utilitaires pour :

- Gérer et valider les ports de connexion du serveur.
- Vérifier la validité et l'état des bornes dans les parkings.
- Contrôler la validité des contrats (abonnement ou ticket horaire).
- Calculer et enregistrer les pénalités en cas de dépassement.
- Fournir des fonctions auxiliaires pour l'accès à la base de données et la génération d'IDs uniques.

Ce module est utilisé par le serveur TCP pour traiter les requêtes des clients, 
assurer l'intégrité des données et journaliser toutes les opérations importantes.
"""
from db import get_connection
from datetime import datetime
import sys
import logging

# Configuration des logs pour parking.py
logger = logging.getLogger(__name__)

## ----------------------------------------- Des fonctions principales de partie serveur applicatif-----------------------------------------------------

def demander_port():
    """Demande un port à l'utilisateur"""
    while True:
        try:
            port_input = input(f"Entrez le port du serveur (1024-65535) : ").strip()
            
            if port_input == "":
                logger.warning("Port vide, redemande a l'utilisateur")
                print("Erreur : Veuillez entrer un port !")
                continue
                
            port = int(port_input)
            
            # Vérifier que le port est dans la plage valide
            if port < 1024 or port > 65535:
                logger.warning(f"Port {port} hors limite (1024-65535)")
                print("Erreur : Le port doit être entre 1024 et 65535 !")
                continue
            
            logger.info(f"Port {port} valide et selectionne")
            return port
            
        except ValueError:
            logger.error("Saisie invalide : pas un nombre")
            print("Erreur : Veuillez entrer un nombre valide !")
        except KeyboardInterrupt:
            logger.info("Interruption clavier (Ctrl+C)")
            print("\n Arrêt du serveur...")
            exit(0)

def obtenir_port():
    """Obtient le port depuis les arguments ou demande à l'utilisateur"""
    # Vérifier si un port est passé en paramètre
    if len(sys.argv) > 1:
        try:
            port = int(sys.argv[1])
            
            # Vérifier que le port est dans la plage valide
            if port < 1024 or port > 65535:
                logger.error(f"Port {port} invalide (doit etre entre 1024-65535)")
                print(" Erreur : Le port doit être entre 1024 et 65535 !")
                print("Usage: python3 serveur.py [port]")
                exit(1)
            
            logger.info(f"Port {port} recupere depuis les arguments")
            return port
            
        except ValueError:
            logger.error(f"Argument port invalide : {sys.argv[1]}")
            print("Erreur : Le port doit être un nombre valide !")
            print("Usage: python3 serveur.py [port]")
            exit(1)
    else:
        # Aucun paramètre, demander à l'utilisateur
        logger.info("Aucun argument, passage en mode interactif")
        return demander_port()


# Vérifier si la borne appartient au parking et est active
def tester_borne_parking(id_parking, id_borne):
    """
    Vérifie si une borne appartient à un parking donné et si elle est active.
    :param id_parking: L'identifiant du parking.
    :param id_borne: L'identifiant de la borne.
    :return: Un message indiquant le statut de la borne.
    """
    logger.debug(f"tester_borne_parking appele avec parking={id_parking}, borne={id_borne}")
    
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
    
    logger.debug(f"Resultat requete BD : {result}")
    
    if result is None:
        logger.warning(f"Borne {id_borne} inconnue pour le parking {id_parking}")
        return "ERREUR;Borne inconnue pour ce parking"
    
    id_borne_res, etat_borne = result

    if etat_borne != 'active':
        logger.warning(f"Borne {id_borne_res} existe mais est inactive (etat={etat_borne})")
        return f"ERREUR;Borne {id_borne_res} inactive"

    logger.info(f"Borne {id_borne_res} validee : active dans le parking {id_parking}")
    return f"Borne reconnue et active dans le parking "

## verifcation si le contrat est un abonnement ou un ticketHoraire

def verif_abonnement_ou_ticketHoraire(id_contrat):
    """
    Vérifie si le contrat est un abonnement ou un ticket horaire.
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: "abonnement" si c'est un abonnement, "ticketHoraire" si c'est un ticket horaire, None sinon.
    """
    logger.debug(f"verif_abonnement_ou_ticketHoraire appele pour contrat={id_contrat}")
    
    conn = get_connection()
    cur = conn.cursor()

    # Vérifier si c'est un abonnement
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
        logger.warning(f"Contrat {id_contrat} introuvable dans la BD")
        return None  # Contrat n'existe pas

    type_contrat = res[0].lower()
    logger.debug(f"Type de contrat {id_contrat} : {type_contrat}")

    # Normalisation
    if type_contrat == "abonnement": 
        return "abonnement"
    if type_contrat == "tickethoraire":
        return "ticketHoraire"

    logger.warning(f"Type de contrat inconnu : {type_contrat}")
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
        logger.debug("Aucune penalite existante, creation de PNL0001")
        return "PNL0001"

    last_id = row[0]        # Exemple : "PNL0032"
    numero = int(last_id[3:]) + 1   # Récupère "0032" → 32 → +1 = 33
    new_id = f"PNL{numero:04d}"      # Formate → PNL0033
    
    logger.debug(f"Nouvel ID penalite genere : {new_id} (dernier : {last_id})")
    return new_id

## Calculer la pénalité en fonction du type de contrat

def verifier_penalite(id_contrat):
    """
    Vérifie et calcule la pénalité pour un contrat donné.
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: Un tuple (bool, message) indiquant si une pénalité a été appliquée et le détail.
    """
    logger.debug(f"verifier_penalite appele pour contrat={id_contrat}")
    
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
        logger.error(f"Contrat {id_contrat} introuvable lors du calcul de penalite")
        curseur.close()
        connexion.close()
        return False, "ERREUR;Contrat introuvable"

    date_fin = row[0]  # date_fin est un objet datetime
    logger.debug(f"Date fin du contrat {id_contrat} : {date_fin}, date actuelle : {now}")

    # Calculer la différence en minutes entre la date de fin et la date actuelle
    if now > date_fin:
        delta = now - date_fin
        minutes_retard = int(delta.total_seconds() // 60)  # Convertir en minutes
        penalite = round(minutes_retard * 0.1, 2)  # 0,1 euro par minute
        description = f"Retard de {minutes_retard} minute(s)"

        logger.warning(f"Penalite appliquee pour contrat {id_contrat} : {penalite} EUR ({description})")

        # Insérer la pénalité dans la base de données
        curseur.execute(
            """
            INSERT INTO penalite (id_penalite, id_contrat, montant_p, description, date_creation)
            VALUES (%s, %s, %s, %s, %s)
            """,
            (id_penalite, id_contrat, penalite, description, now)
        )
        logger.info(f"Penalite {id_penalite} inseree dans la BD pour contrat {id_contrat}")
    else:
        logger.debug(f"Pas de retard pour contrat {id_contrat}, aucune penalite")

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
    logger.debug(f"type_borne appele pour borne={id_borne}")

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
        logger.debug(f"Type de borne {id_borne} : {typeBorne[0]}")
        return typeBorne[0]  # Retourne le type de borne (entrée ou sortie)
    
    logger.warning(f"Borne {id_borne} introuvable, type inconnu")
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
    logger.debug(f"ajouter_historique : contrat={id_contrat}, borne={id_borne}, etat={etat_valide}")
    
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
    
    logger.info(f"Historique ajoute/mis a jour : contrat={id_contrat}, etat={etat_valide}")

# Vérifier un contrat et sa validité
def verifier_contrat(id_contrat):
    """
    Vérifie si un contrat existe, et si oui : son etat.
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: Un tuple (bool, message) indiquant si le contrat existe et le détail.
    """
    logger.debug(f"verifier_contrat appele pour contrat={id_contrat}")

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
        logger.warning(f"Contrat {id_contrat} inconnu dans la BD")
        return False, "Contrat inconnu"

    _, date_debut, date_fin, etat_contrat, type_contrat = contrat
    logger.debug(f"Contrat {id_contrat} : debut={date_debut}, fin={date_fin}, etat={etat_contrat}, type={type_contrat}")

    today = datetime.today().date()

    if etat_contrat.lower() != "actif": 
        logger.warning(f"Contrat {id_contrat} non actif : {etat_contrat}")
        return True, f"Contrat {etat_contrat}"

    if date_fin.date() < today:
        logger.warning(f"Contrat {id_contrat} expire (fin={date_fin.date()}, aujourd'hui={today})")
        return True, "Contrat expiré"

    logger.info(f"Contrat {id_contrat} valide")
    return True, "Contrat valide"

### Fonction pour vérifier si un contrat existe dans l'historique

def exist_contrat_dans_historique(id_contrat):
    """
    Vérifie si un contrat existe dans la table 'verifie'.
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: True si le contrat existe dans l'historique, False sinon.
    """
    logger.debug(f"exist_contrat_dans_historique appele pour contrat={id_contrat}")

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
    
    existe = count > 0
    logger.debug(f"Contrat {id_contrat} dans historique : {existe} (count={count})")
    
    return existe

## Fonction pour verifier la date de fin du contrat

"""def verifier_validite_contrat(id_contrat):  /========Ancient code=========/
    
    Vérifie si la date actuelle est entre date_debut et date_fin et que le contrat est 'actif' pour un contrat donné.
    
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: True si la date actuelle est entre date_debut et date_fin, False sinon.
    
    logger.debug(f"verifier_validite_contrat appele pour contrat={id_contrat}")
    
    conn = get_connection()
    cur = conn.cursor()

    query = 
        SELECT date_debut, date_fin, etat_contrat
        FROM contrat
        WHERE id_contrat = %s AND etat_contrat = 'actif';
    
    cur.execute(query, (id_contrat,))
    result = cur.fetchone()
    cur.close()
    conn.close()

    if not result:
        logger.warning(f"Contrat {id_contrat} non trouve ou non actif")
        return False

    date_debut, date_fin, etat_contrat = result
    today = datetime.today().date()
    
    logger.debug(f"Contrat {id_contrat} : debut={date_debut.date()}, fin={date_fin.date()}, aujourd'hui={today}")

    # Vérifie si la date actuelle est entre date_debut et date_fin
    
    if today >= date-fin.date():
        logger.warning(f"Contrat {id_contrat} expire (fin={date_fin.date()}, aujourd'hui={today})")
        return False
    
    type_contrat = verif_abonnement_ou_ticketHoraire(id_contrat)
    if type_contrat == "ticketHoraire":
        logger.info(f"Contrat {id_contrat} est un ticket horaire, validite jusqu'a date_fin")
        valide = date_debut.time() <= today.time() <= date_fin.time() and etat_contrat == 'actif'

    
    if valide:
        logger.info(f"Contrat {id_contrat} valide dans les dates")
    else:
        logger.warning(f"Contrat {id_contrat} hors des dates de validite")
    
    return valide"""


def verifier_validite_contrat(id_contrat):
    """
    Vérifie la validité du contrat selon son type :
    - abonnement : date_debut <= maintenant <= date_fin
    - ticketHoraire : date_debut.time <= maintenant.time <= date_fin.time
    """

    logger.debug(f"verifier_validite_contrat appele pour contrat={id_contrat}")

    # --- Récupération du contrat ---
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
        logger.warning(f"Contrat {id_contrat} non trouvé ou non actif")
        return False

    date_debut, date_fin, etat_contrat = result
    now = datetime.now()

    logger.debug(
        f"Contrat {id_contrat} : debut={date_debut}, fin={date_fin}, maintenant={now}"
    )

    # --- Vérifier type du contrat ---
    type_contrat = verif_abonnement_ou_ticketHoraire(id_contrat)

    # ----------- CAS 1 : abonnement -----------
    if type_contrat == "abonnement":
        logger.info(f"Contrat {id_contrat} est un abonnement")

        valide = date_debut <= now <= date_fin

        if not valide:
            logger.warning(f"Contrat {id_contrat} expiré (abonnement)")
        return valide

    # ----------- CAS 2 : ticket horaire -----------
    elif type_contrat == "ticketHoraire":
        logger.info(f"Contrat {id_contrat} est un ticket horaire")

        heure_debut = date_debut.time()
        heure_fin = date_fin.time()
        heure_now = now.time()

        valide = heure_debut <= heure_now <= heure_fin

        if not valide:
            logger.warning(f"Contrat {id_contrat} expiré (ticket horaire)")
        return valide

    else:
        logger.error(f"Type de contrat inconnu pour {id_contrat}")
        return False


#fonction pour verifier si le contrat est en cours d'entrée ou de sortie
def encours_in_ou_out(id_contrat):
    """
    Vérifie l'état actuel (encours_in ou encours_out) d'un contrat dans la table 'verifie'.
    :param id_contrat: L'identifiant du contrat à vérifier.
    :return: L'état valide (encours_in ou encours_out) si le contrat existe, None sinon.
    """
    logger.debug(f"encours_in_ou_out appele pour contrat={id_contrat}")

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
        logger.debug(f"Etat du contrat {id_contrat} : {etat[0]}")
        return etat[0]  # Retourne l'état valide (encours_in ou encours_out)
    
    logger.debug(f"Pas d'etat trouve pour contrat {id_contrat}")
    return None

def update_historique(id_contrat, id_borne, etat_valide):
    """
    Met à jour l'état valide et l'heure scannée pour un contrat dans la table 'verifie'.
    :param id_contrat: L'identifiant du contrat à mettre à jour.
    :param id_borne: L'identifiant de la borne où le contrat a été scanné.
    :param etat_valide: Le nouvel état de validité du contrat (encours_in, encours_out, delai_depasse).
    :return: None
    """
    logger.debug(f"update_historique : contrat={id_contrat}, borne={id_borne}, nouvel_etat={etat_valide}")
    
    conn = get_connection()
    cur = conn.cursor()
    heure_scanne = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    query_by_borne = """
        UPDATE verifie
        SET heure_scanne = %s, etat_valide = %s,id_borne = %s
        WHERE id_contrat = %s  
    """
    cur.execute(query_by_borne, (heure_scanne, etat_valide, id_borne, id_contrat))

    conn.commit()
    cur.close()
    conn.close()
    
    logger.info(f"Historique mis a jour : contrat={id_contrat}, nouvel_etat={etat_valide}")

    
def scanne_contrat_entree(id_contrat, id_borne):
    """
    Gère le scan d'un contrat à une borne d'entrée.
    :param id_contrat: L'identifiant du contrat scanné.
    :param id_borne: L'identifiant de la borne où le contrat a été scanné.
    :return: Un tuple (bool, message) indiquant si le contrat est valide pour l'entrée et le détail.
    """
    logger.info(f"Traitement ENTREE : contrat={id_contrat}, borne={id_borne}")
    
    conn = get_connection()
    cur = conn.cursor()
    
    if(verifier_contrat(id_contrat)[0] == True):

        if(verifier_validite_contrat(id_contrat) == True):

            if(exist_contrat_dans_historique(id_contrat) == False):
                logger.info(f"Premier scan du contrat {id_contrat}, ajout dans historique")
                ajouter_historique(id_contrat, id_borne, "encours_in")

            else:
                etat = encours_in_ou_out(id_contrat)
                logger.debug(f"Contrat {id_contrat} deja dans historique avec etat={etat}")
                
                if etat == "encours_out":
                    logger.info(f"Contrat {id_contrat} en sortie, passage en entree")
                    update_historique(id_contrat, id_borne, "encours_in")
                else:
                    logger.warning(f"Contrat {id_contrat} deja en cours d'entree")
                    return False, "ERREUR;véhicule déjà enregistré à l'intérieur du parking."
        else:
            logger.warning(f"Contrat {id_contrat} non valide ou expire")
            return False, "ERREUR;Contrat non valide (expiré ou n'est pas encore valable)"
    else:
        logger.warning(f"Contrat {id_contrat} echec verification")
        return verifier_contrat(id_contrat)[0], verifier_contrat(id_contrat)[1]
    
    cur.close()
    conn.close()
    logger.info(f"Entree autorisee pour contrat {id_contrat}")
    return True, "Contrat actif, accès autorisé"
    
def scanne_contrat_sortie(id_contrat, id_borne):
    """
    Gère le scan d'un contrat à une borne de sortie.
    :param id_contrat: L'identifiant du contrat scanné.
    :param id_borne: L'identifiant de la borne où le contrat a été scanné.
    :return: Un tuple (bool, message) indiquant si le contrat est valide pour la sortie et le détail.
    """
    logger.info(f"Traitement SORTIE : contrat={id_contrat}, borne={id_borne}")

    conn = get_connection()
    cur = conn.cursor()
    
    if(verifier_contrat(id_contrat)[0] == True):

        if(verifier_validite_contrat(id_contrat) == True):

            if(exist_contrat_dans_historique(id_contrat) == True):
                
                if(encours_in_ou_out(id_contrat) == "encours_in"):
                    logger.info(f"Contrat {id_contrat} en cours d'entree, passage en sortie")
                    update_historique(id_contrat, id_borne, "encours_out")
                else:
                    logger.warning(f"Contrat {id_contrat} deja en cours de sortie")
                    return False, "ERREUR;Contrat déjà en cours de sortie"
            else:
                logger.warning(f"Contrat {id_contrat} sans entree enregistree")
                return False, "ERREUR;Contrat n'a pas d'entrée enregistrée"
        else:
            logger.warning(f"Contrat {id_contrat} non valide, application de penalite")
            update_historique(id_contrat, id_borne, "delai_depasse")
            verifier_penalite(id_contrat)
            return False, "ERREUR;Contrat non valide et penalite attribuée"
    else:
        logger.warning(f"Contrat {id_contrat} echec verification")
        return verifier_contrat(id_contrat)
    
    cur.close()
    conn.close()
    logger.info(f"Sortie autorisee pour contrat {id_contrat}")
    return True, "Contrat valide pour sortie"

    
def scanne_contrat(id_contrat, id_borne):
    logger.info(f"scanne_contrat appele : contrat={id_contrat}, borne={id_borne}")
    
    type_borne_val = type_borne(id_borne)
    
    if type_borne_val == "entree":
        logger.info(f"Borne {id_borne} de type ENTREE")
        return scanne_contrat_entree(id_contrat, id_borne)
    elif type_borne_val == "sortie":
        logger.info(f"Borne {id_borne} de type SORTIE")
        return scanne_contrat_sortie(id_contrat, id_borne) 
    else:
        logger.error(f"Type de borne inconnu pour {id_borne} : {type_borne_val}")
        return False, "ERREUR;Type de borne inconnu"