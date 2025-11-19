import psycopg2
from db import get_connection
from datetime import datetime

# 🔹Question 1-2 ) Vérifier si la borne appartient au parking et est active
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

# Question 3 ) le contrat est il associer au bon parking
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
