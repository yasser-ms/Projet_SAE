from parking import verifier_contrat, tester_borne_parking

# 🔹 Exemple d'appel
if __name__ == "__main__":
    # Tester une borne dans un parking
    id_parking = "PK0001"
    id_borne = "B0001"
    resultat_borne = tester_borne_parking(id_parking, id_borne)
    print(resultat_borne)

    # Tester un contrat dans un parking
    id_contrat = "CT00001"
    resultat_contrat = verifier_contrat(id_contrat)
    print(resultat_contrat)