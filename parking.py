import psycopg2
from db import get_connection
from datetime import datetime

# 🔹 Vérifier si la borne appartient au parking et est active
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


# 🔹 Ajouter un événement dans l'historique
def ajouter_historique(id_contrat, id_borne, etat_valide):
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

## verifcation de penalité





