from parking import tester_borne_parking, verifier_contrat, ajouter_historique

# 🔹 Exemple d'appel
if __name__ == "__main__":
    print(tester_borne_parking("PK0001", "B0001")) ## Active et parking reconnue
    print(tester_borne_parking("PK0002", "B0002"))  # parking reconnue mais borne en_panne
    print(tester_borne_parking("PK0002", "B0099"))  # test d'une borne inexistante

    contrats_a_tester = ["CT00001", "CT00002", "CT00003"]
    id_borne = "B0002"

    for contrat in contrats_a_tester:
        valide, message = verifier_contrat(contrat)
        etat_scanne = "encours_in" if valide else "delai_depasse"
        ajouter_historique(contrat, id_borne, etat_scanne)
        print(f"{contrat}: {message} -> Historique: {etat_scanne}")