import socket
from parking import tester_borne_parking, verifier_contrat, verifier_penalite, type_borne, scanne_contrat

HOST = "0.0.0.0"
PORT = 5000

server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
server_socket.bind((HOST, PORT))
server_socket.listen(1)

print(f"Serveur en attente sur le port {PORT}...")


while True:

    conn, addr = server_socket.accept()
    print(f"Client connecté : {addr}")

    try:
        
        #  RÉCEPTION borne + parking

        data = conn.recv(1024).decode("utf-8").strip()
        if not data:
            print("Client déconnecté lors de la réception de la borne et du parking")
            continue
        try:
            id_borne, id_parking = data.split(";")
            print("commence par tester la borne et le parking")
        except:
            conn.sendall("ERREUR;FORMAT_INVALIDE".encode("utf-8"))
            continue

        msg_borne = tester_borne_parking(id_parking, id_borne)
        print("Réponse :", msg_borne)
        conn.sendall((msg_borne + "\n").encode("utf-8"))

        #    on arrête si la borne est incorrecte
        if msg_borne.startswith("ERREUR"):
            print("Borne incorrecte, fin communication.")
            continue

        ####################### RÉCEPTION ID CONTRAT ##################################
        
        data2 = conn.recv(1024).decode("utf-8").strip()

        if not data2:
            print("Client déconnecté lors de la réception du contrat")
            continue

        print("Reçu :", data2)

        id_contrat = data2.strip()
        # print("Vérification du contrat :", id_contrat) -> DEBUG


        valide, msg_contrat = scanne_contrat(id_contrat,id_borne)
        
        if isinstance(msg_contrat, tuple):
            try:
                msg_contrat = str(msg_contrat[-1])
            except Exception:
                msg_contrat = " ".join(map(str, msg_contrat))

        conn.sendall((msg_contrat).encode("utf-8"))

        if not valide:
            continue
    
      ##  type_borne_val = type_borne(id_borne)
        ##if type_borne_val == "sortie":
        # Vérifier pénalité (si borne sortie)
         ##ok, msg_penalite = verifier_penalite(id_contrat, id_borne)
         ##conn.sendall(msg_penalite.encode("utf-8"))
         
    except Exception as e:
        print("Erreur serveur :", e)

    finally:
        conn.close()
        print("Connexion fermée.\n")

