from questions_vers_la_BD import tester_contrat_parking, tester_borne_parking
from serveur import verifier_penalite

# 🔹 Exemple d'appel
if __name__ == "__main__":

    print("\n Abonnement non expiré ")
    # Contrat CT00001 : abonnement expiré → pas de pénalité 
    verifier_penalite(
        id_contrat="CT00001",
        id_borne="B0001",
    )

    print("\n Ticket horaire dépassé ")
    # Ticket CT00002 : pénalité attendue
    verifier_penalite(
        id_contrat="CT00002",
        id_borne="B0001",
    )

    print("\n Abonnement valide ")
    # Abonnement CT00003 : encore valide → aucune pénalité
    verifier_penalite(
        id_contrat="CT00003" ,
        id_borne="B0001",
    )

    print("\n Ticket horaire non dépassé ")
    # Ticket CT00004 : aucune pénalité
    verifier_penalite(
        id_contrat="CT00004",
        id_borne="B0001",
    )

    print("\n Contrat inexistant ")
    verifier_penalite(
        id_contrat="CT99999",
        id_borne="B0001",
    )

    print("\n Mauvaise saisie ")
    verifier_penalite(
        id_contrat=None,
        id_borne=None,
    )
    
