from questions_vers_la_BD import *

if __name__ == "__main__":
    print("=== TESTS FONCTIONS DE parking.py ===")
    
    #Question 1 ) Vérifier si la borne appartient au parking et est active
    #print(tester_borne_parking("PK0001", "B0001"))  # Borne Active et parking reconnue
    # Question 2 ) le contrat est il associer au bon parking
    
    #print(tester_contrat_parking("PK0001", "CT00001"))  # Contrat existe et dans ce parking
    # Question 3 ) extraire le nombre de vehicule par personne 
    #print(extraire_nombre_vehicules_par_client())
    # Question 4 ) extraire le nombre de place libre et occupé dans un parking
    #print(extraire_clients_abonnement_long())
    #QUESTION 5)  affciher les plus grands tarifs paye par des clients pas moin de 100 euro
    #print(extraire_paiements_importants())
    # Quetion 6: Quels sont les clients enregistrés qui n'ont encore jamais effectué le moindre paiement?
    #print(extraire_clients_sans_paiement())
    # Quetion 7 : Qui est le client qui a écopé de la plus grande pénalité ?
    #print(extraire_client_plus_grande_penalite())