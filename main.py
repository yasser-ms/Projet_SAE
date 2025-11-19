from questions_vers_la_BD import tester_contrat_parking, tester_borne_parking

# 🔹 Exemple d'appel
if __name__ == "__main__":
    # test Question 1 et 2 : borne et parking

    ## Borne Active et parking reconnue
    print(tester_borne_parking("PK0001", "B0001"))
    # parking reconnue mais borne en_panne
    print(tester_borne_parking("PK0002", "B0002"))
    # test d'une borne inexistante
    print(tester_borne_parking("PK0002", "B0099"))

    # Test Question 3 : contrat et parking

    # Contrat existe et dans ce parking
    print(tester_contrat_parking("PK0001", "CT00001"))
    # Contrat existe mais pas dans ce parking
    print(tester_contrat_parking("PK0003", "CT00001"))
    # Contrat inexistant
    print(tester_contrat_parking("PK0001", "CT99999"))
    # Parking inexistant (selon les données de la base)
    print(tester_contrat_parking("PK9999", "CT00001"))
    # Les deux inexistants
    print(tester_contrat_parking("PK9999", "CT99999"))