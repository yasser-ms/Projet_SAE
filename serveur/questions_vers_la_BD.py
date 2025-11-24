import psycopg2                             # Bibliothèque PostgreSQL
from db import get_connection               # Fonction pour obtenir une connexion à la base de données
from datetime import datetime               # Pour gérer les dates si nécessaire

# 🔹 Question 1 — Vérifier si la borne appartient au parking et si elle est active
def tester_borne_parking(id_parking, id_borne):
    conn = get_connection()                 # Connexion à la base
    cur = conn.cursor()                     # Création du curseur pour exécuter des requêtes

    query = """
        SELECT b.id_borne, b.etat
        FROM borne b
        WHERE b.id_parking = %s
        AND b.id_borne = %s;
    """                                      # Requête pour vérifier que la borne existe dans ce parking
    cur.execute(query, (id_parking, id_borne))  # Exécution de la requête avec paramètres
    result = cur.fetchone()                 # Récupère la première ligne trouvée

    cur.close()                             # Ferme le curseur
    conn.close()                            # Ferme la connexion

    print(result)                           # Debug (affiche la ligne récupérée)

    if result is None:                      # Si aucun résultat → borne inconnue
        return "ERREUR;Borne inconnue pour ce parking"

    id_borne_res, etat_borne = result       # Décomposition du résultat

    if etat_borne != 'active':              # Vérifie si la borne est active
        return f"ERREUR;Borne {id_borne_res} inactive"

    return f"Borne {id_borne_res} active dans le parking {id_parking}"


# 🔹 Question 2 — Vérifier si le contrat est associé au bon parking
def tester_contrat_parking(id_parking, id_contrat):
    conn = get_connection()                 # Connexion à la base
    cur = conn.cursor()                     # Curseur

    query = """
        SELECT c.id_contrat, pl.id_place, pl.id_parking
        FROM contrat c
        JOIN place pl ON c.id_place = pl.id_place
        WHERE c.id_contrat = %s
        AND pl.id_parking = %s;
    """                                     # Vérifie si le contrat est lié à une place du parking

    cur.execute(query, (id_contrat, id_parking))  # Exécution
    result = cur.fetchone()                 # Récupère la ligne

    cur.close()                             # Ferme curseur
    conn.close()                            # Ferme connexion

    print(result)                           # Debug

    if result is None:                      # Aucun contrat trouvé pour ce parking
        return "ERREUR;Contrat inconnu pour ce parking"

    id_contrat_res, id_place_res, id_parking_res = result  # Décomposition du résultat

    return f"Contrat {id_contrat_res} associé à la place {id_place_res} dans le parking {id_parking_res}"


# 🔹 Question 3 — Extraire le nombre de véhicules par client
def extraire_nombre_vehicules_par_client():
    conn = get_connection()                 # Connexion
    cur = conn.cursor()                     # Curseur

    query = """
        SELECT c.nom, c.prenom, COUNT(v.id_vehicule) AS nb_vehicules
        FROM client c
        LEFT JOIN vehicule v ON v.id_client = c.id_client
        GROUP BY c.id_client, c.nom, c.prenom
        ORDER BY c.nom;
    """                                     # Requête comptant les véhicules par client

    cur.execute(query)                      # Exécution
    rows = cur.fetchall()                   # Toutes les lignes

    cur.close()
    conn.close()

    if not rows:                            # Si aucun client
        return "Aucun client trouvé."

    lignes = [f"{nom} {nb}" for nom, prenom, nb in rows]  # Formatage du résultat
    return "\n".join(lignes)


# 🔹 Question 4 — Nombre de places libres et occupées dans chaque parking
def extraire_places_par_parking():
    """
    Retourne :
    NomParking places_libres places_occupees
    """
    conn = get_connection()
    cur = conn.cursor()

    query = """
        SELECT
            p.nom,
            p.nbrplace,
            COALESCE(COUNT(DISTINCT CASE WHEN c.etat_contrat = 'actif' THEN pl.id_place END), 0) AS places_occupees,
            p.nbrplace - COALESCE(COUNT(DISTINCT CASE WHEN c.etat_contrat = 'actif' THEN pl.id_place END), 0) AS places_libres
        FROM parking p
        LEFT JOIN place pl ON pl.id_parking = p.id_parking
        LEFT JOIN contrat c ON c.id_place = pl.id_place AND c.etat_contrat = 'actif'
        GROUP BY p.id_parking, p.nom, p.nbrplace
        ORDER BY p.nom;
    """                                     # Compte des places occupées via les contrats actifs

    cur.execute(query)
    rows = cur.fetchall()

    cur.close()
    conn.close()

    if not rows:
        return "Aucun parking trouvé."

    lignes = [f"{nom} {libres} {occupees}" for nom, nbrplace, occupees, libres in rows]
    return "\n".join(lignes)


# 🔹 Question 5 — Clients ayant un abonnement actif d’au moins 6 mois
def extraire_clients_abonnement_long():
    conn = get_connection()
    cur = conn.cursor()

    query = """
        SELECT 
            c.id_client,
            c.nom,
            c.prenom,
            c.date_de_naissance,
            c.adresse_mail,
            c.num_telephone,
            COUNT(v.id_vehicule) AS nombre_vehicules
        FROM client c
        LEFT JOIN vehicule v
            ON c.id_client = v.id_client
        LEFT JOIN contrat ct
            ON v.id_vehicule = ct.id_vehicule
            AND AGE(ct.date_fin, ct.date_debut) >= INTERVAL '6 months'
        GROUP BY 
            c.id_client, c.nom, c.prenom, c.date_de_naissance, c.adresse_mail, c.num_telephone
        ORDER BY c.id_client;
    """                                     # Sélection des clients avec contrat ≥ 6 mois

    cur.execute(query)
    rows = cur.fetchall()

    cur.close()
    conn.close()

    if not rows:
        return "Aucun client trouvé avec un abonnement de 6 mois ou plus."

    lignes = [
        f"{id_client} | {nom} | {prenom} | {date_naissance} | {email} | {telephone} | {nb_vehicules}"
        for id_client, nom, prenom, date_naissance, email, telephone, nb_vehicules in rows
    ]
    return "\n".join(lignes)


# 🔹 Question 6 — Afficher les paiements ≥ 100€
def extraire_paiements_importants():
    conn = get_connection()
    cur = conn.cursor()

    query = """
        SELECT 
            p.id_paiement,
            p.id_contrat,
            p.montant,
            v.id_vehicule,
            v.type,
            v.modele,
            c.id_client,
            c.nom,
            c.prenom
        FROM paiement p
        JOIN contrat ct 
            ON p.id_contrat = ct.id_contrat
        JOIN vehicule v 
            ON ct.id_vehicule = v.id_vehicule
        JOIN client c
            ON v.id_client = c.id_client
        WHERE p.montant >= 100
        ORDER BY p.montant DESC;
    """                                     # Sélection des paiements importants

    cur.execute(query)
    rows = cur.fetchall()

    cur.close()
    conn.close()

    if not rows:
        return "Aucun paiement supérieur ou égal à 100 euros trouvé."

    lignes = [
        f"{id_paiement} | {id_contrat} | {montant} | {id_vehicule} | {type_vehicule} | {modele} | {id_client} | {nom} | {prenom}"
        for id_paiement, id_contrat, montant, id_vehicule, type_vehicule, modele, id_client, nom, prenom in rows
    ]
    return "\n".join(lignes)
