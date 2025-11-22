from questions_vers_la_BD import tester_contrat_parking, tester_borne_parking, extraire_nombre_vehicules_par_client,extraire_places_par_parking, extraire_clients_abonnement_long, extraire_paiements_importants
# 🔹 Exemple d'appel
if __name__ == "__main__":
    # test Question 1 et 2 : borne et parking

    """ ## Borne Active et parking reconnue
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
    print(tester_contrat_parking("PK9999", "CT99999")) """
    
    """  #test Question 3 : le nombre de vehicule par client 
    print(extraire_nombre_vehicules_par_client()) """

    #test Question 4 : le nombre de place libre et occupé dans un parking
    """ resultat = extraire_places_par_parking()
        faut tester si le resultat est non null
    for ligne in resultat.split("\n"):
        nom_parking, places_libres, places_occupees = ligne.split()
        print(f"Parking: {nom_parking} | Places libres: {places_libres} | Places occupées: {places_occupees}")

 """
        #test Question 5 :  les informations des clients qui possede un abonnement de minimum de 6 mois

""" print(extraire_clients_abonnement_long()) """

    #test Question 6 : les plus grands tarifs payé par des clients pas moins de 100 euro
print(extraire_paiements_importants())
