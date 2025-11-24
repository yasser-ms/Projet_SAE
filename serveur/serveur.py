import socket                                     # Module pour créer un serveur TCP
from parking import tester_borne_parking, verifier_contrat, verifier_penalite, type_borne, scanne_contrat
# Import des fonctions de logique métier depuis le module parking

HOST = "0.0.0.0"                                  # Le serveur écoute sur toutes les interfaces réseau
PORT = 5000                                       # Port sur lequel le serveur va écouter les connexions

server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)  # Création d’un socket TCP (SOCK_STREAM = TCP)
server_socket.bind((HOST, PORT))                 # Attache le socket à l'adresse et au port
server_socket.listen(1)                          # Met le serveur en mode écoute (1 = nombre max de connexions en file d’attente)

print(f"Serveur en attente sur le port {PORT}...")  # Indique que le serveur est prêt


while True:                                      # Boucle infinie pour accepter plusieurs clients

    conn, addr = server_socket.accept()          # Accepte une connexion entrante (conn = socket du client, addr = adresse client)
    print(f"Client connecté : {addr}")           # Affiche l’adresse du client connecté

    try:
        # ------------------------ RÉCEPTION borne + parking ------------------------

        data = conn.recv(1024).decode("utf-8").strip()    # Lit jusqu’à 1024 bytes, décode en UTF-8 et enlève les espaces
        if not data:                                      # Si le client ferme avant d’envoyer quoi que ce soit
            print("Client déconnecté lors de la réception de la borne et du parking")
            continue                                       # On repart attendre un autre client

        try:
            id_borne, id_parking = data.split(";")        # Sépare la borne et le parking grâce au ";"
            print("commence par tester la borne et le parking")
        except:
            conn.sendall("ERREUR;FORMAT_INVALIDE".encode("utf-8"))  # En cas de mauvais format
            continue

        msg_borne = tester_borne_parking(id_parking, id_borne)      # Vérifie si la borne appartient au parking
        print("Réponse :", msg_borne)                               # Affiche la réponse pour debug
        conn.sendall((msg_borne + "\n").encode("utf-8"))            # Envoie la réponse au client

        # Si la borne est incorrecte, on arrête la communication
        if msg_borne.startswith("ERREUR"):
            print("Borne incorrecte, fin communication.")
            continue

        # ------------------------ RÉCEPTION ID CONTRAT ------------------------

        data2 = conn.recv(1024).decode("utf-8").strip()   # Réception de l’ID contrat

        if not data2:                                     # Si le client s’est déconnecté
            print("Client déconnecté lors de la réception du contrat")
            continue

        print("Reçu :", data2)                            # Debug : afficher l’ID reçu

        id_contrat = data2.strip()                        # Nettoie la valeur

        # Vérifie la validité du contrat en fonction de la borne
        valide, msg_contrat = scanne_contrat(id_contrat, id_borne)

        # Si la fonction renvoie un tuple, on convertit proprement en string
        if isinstance(msg_contrat, tuple):
            try:
                msg_contrat = str(msg_contrat[-1])        # Prend le dernier élément si possible
            except Exception:
                msg_contrat = " ".join(map(str, msg_contrat))

        conn.sendall((msg_contrat).encode("utf-8"))       # Envoie la réponse au client

        if not valide:                                    # Si le contrat n’est pas valide, on arrête
            continue

        # --------------------------- VÉRIFICATION PÉNALITÉ (désactivée) ---------------------------

        # type_borne_val = type_borne(id_borne)           # Récupère le type de la borne
        # if type_borne_val == "sortie":                  # Si c’est une borne de sortie
        #     ok, msg_penalite = verifier_penalite(id_contrat, id_borne)   # Vérifie les pénalités
        #     conn.sendall(msg_penalite.encode("utf-8"))   # Renvoie au client

    except Exception as e:                                # En cas d’erreur serveur
        print("Erreur serveur :", e)

    finally:
        conn.close()                                      # Ferme la connexion avec ce client
        print("Connexion fermée.\n")                      # Ligne de séparation pour la lisibilité
    
