"""
questions_vers_la_bd.py

Contient uniquement des fonctions pour interroger la base de données.
Ces fonctions effectuent des requêtes SELECT pour récupérer des informations
comme les contrats, les bornes, les parkings, etc.
"""


import psycopg2
from db import get_connection
from datetime import datetime

# 🔹Question 1 ) Vérifier si la borne appartient au parking et est active
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

# Question 2 ) le contrat est il associer au bon parking
def tester_contrat_parking(id_parking, id_contrat):
    conn = get_connection()
    cur = conn.cursor()

    query = """
        SELECT c.id_contrat, pl.id_place, pl.id_parking
        FROM contrat c
        JOIN place pl ON c.id_place = pl.id_place
        WHERE c.id_contrat = %s
        AND pl.id_parking = %s;
    """

    cur.execute(query, (id_contrat, id_parking))
    result = cur.fetchone()

    cur.close()
    conn.close()

    print(result)  # Debug

    # Aucun résultat = contrat n'est pas associé à ce parking
    if result is None:
        return "ERREUR;Contrat inconnu pour ce parking"

    id_contrat_res, id_place_res, id_parking_res = result

    return f"Contrat {id_contrat_res} associé à la place {id_place_res} dans le parking {id_parking_res}"

# Question 3 ) extraire le nombre de vehicule par personne 
def extraire_nombre_vehicules_par_client():
    """
    Retourne une chaîne de caractères contenant une ligne par client au format :
    Nom nb_vehicules
    Si aucun client, retourne "Aucun client trouvé."
    """
    conn = get_connection()
    cur = conn.cursor()
    query = """
        SELECT c.nom, c.prenom, COUNT(v.id_vehicule) AS nb_vehicules
        FROM client c
        LEFT JOIN vehicule v ON v.id_client = c.id_client
        GROUP BY c.id_client, c.nom, c.prenom
        ORDER BY c.nom;
    """
    cur.execute(query)
    rows = cur.fetchall()
    cur.close()
    conn.close()

    if not rows:
        return "Aucun client trouvé."

    lignes = [f"{nom} | {nb}" for nom, prenom, nb in rows]
    return "\n".join(lignes)

#QUESTION 4)  Extraire toute les informations d’un client qui possede un abonnement actif de minimum de 6 mois

def extraire_clients_abonnement_long():
    """
    Retourne une chaîne contenant les informations des clients ayant un abonnement
    d'une durée minimale de 6 mois, au format :
    id_client | nom | prenom | date_de_naissance | adresse_mail | num_telephone | nombre_vehicules
    """
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
    """
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

#QUESTION 5)  affciher les plus grands tarifs paye par des clients pas moin de 100 euro
def extraire_paiements_importants():
    """
    Retourne une chaîne contenant les paiements supérieurs ou égaux à 100 euros,
    triés par montant décroissant, au format :
    id_paiement | id_contrat | montant | id_vehicule | type | modele | id_client | nom | prenom
    """
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
    """
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

# Quetion 6: Quels sont les clients enregistrés qui n'ont encore jamais effectué le moindre paiement?


def extraire_clients_sans_paiement():
    """
    Retourne une liste de tuples contenant les clients n'ayant effectué aucun paiement,
    au format : (id_client, nom, prenom)
    """
    conn = get_connection()
    cur = conn.cursor()
    query = """
        SELECT id_client, nom, prenom 
        FROM client 
        WHERE id_client NOT IN (
            SELECT DISTINCT id_client 
            FROM paiement 
            JOIN contrat USING (id_contrat)
        );
    """
    cur.execute(query)
    rows = cur.fetchall()
    cur.close()
    conn.close()
    if not rows:
        return "Aucun client sans paiement trouvé."
    
    lignes = [f"{id_client} | {nom} | {prenom}" for id_client, nom, prenom in rows]
    return "\n".join(lignes)


# Quetion 7 : Qui est le client qui a écopé de la plus grande pénalité ?
def extraire_client_plus_grande_penalite():
    """
    Retourne un tuple contenant le client ayant la plus grande pénalité,
    au format : (id_client, nom, prenom, montant_p, description)
    """
    conn = get_connection()
    cur = conn.cursor()
    
    query = """
        SELECT c.id_client, c.nom, c.prenom, p.montant_p, p.description
        FROM client c
        JOIN vehicule v ON c.id_client = v.id_client
        JOIN contrat co ON v.id_vehicule = co.id_vehicule
        JOIN penalite p ON co.id_contrat = p.id_contrat
        WHERE p.montant_p = (
            SELECT MAX(montant_p)
            FROM penalite
        );
    """
    
    cur.execute(query)
    row = cur.fetchone()  # On récupère un seul client
    cur.close()
    conn.close()
    
    if not row:
        return "Aucun client avec pénalité trouvé."
    
    id_client, nom, prenom, montant_p, description = row
    return f"{id_client} | {nom} | {prenom} | {montant_p} | {description}"

