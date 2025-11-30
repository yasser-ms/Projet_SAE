import socket
import sys
from parking import tester_borne_parking, scanne_contrat

HOST = "0.0.0.0"
DEFAULT_PORT = 5000

def demander_port():
    """Demande un port à l'utilisateur"""
    while True:
        try:
            port_input = input(f"Entrez le port du serveur (1024-65535) : ").strip()
            
            if port_input == "":
                print("Erreur : Veuillez entrer un port !")
                continue
                
            port = int(port_input)
            
            # Vérifier que le port est dans la plage valide
            if port < 1024 or port > 65535:
                print("Erreur : Le port doit être entre 1024 et 65535 !")
                continue
            
            return port
            
        except ValueError:
            print("Erreur : Veuillez entrer un nombre valide !")
        except KeyboardInterrupt:
            print("\n Arrêt du serveur...")
            exit(0)

def obtenir_port():
    """Obtient le port depuis les arguments ou demande à l'utilisateur"""
    # Vérifier si un port est passé en paramètre
    if len(sys.argv) > 1:
        try:
            port = int(sys.argv[1])
            
            # Vérifier que le port est dans la plage valide
            if port < 1024 or port > 65535:
                print(" Erreur : Le port doit être entre 1024 et 65535 !")
                print("Usage: python3 serveur.py [port]")
                exit(1)
            
            return port
            
        except ValueError:
            print("Erreur : Le port doit être un nombre valide !")
            print("Usage: python3 serveur.py [port]")
            exit(1)
    else:
        # Aucun paramètre, demander à l'utilisateur
        return demander_port()

# Obtenir le port (paramètre ou demande interactive)
PORT = obtenir_port()

# Créer et configurer le socket serveur
server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

try:
    # Essayer le port saisi
    server_socket.bind((HOST, PORT))
    print(f"Bind OK sur le port {PORT}")
except Exception as e:
    # Si le port est occupé, utiliser le port par défaut 5000
    print(f"Le port {PORT} est occupé (MODE LISTEN)")
    print(f"Tentative avec le port par défaut {DEFAULT_PORT}...")
    
    try:
        server_socket.close()
        server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        server_socket.bind((HOST, DEFAULT_PORT))
        PORT = DEFAULT_PORT
        print(f"Bind OK sur le port par défaut {DEFAULT_PORT}")
    except Exception as e2:
        print(f"Impossible de lier le port par défaut {DEFAULT_PORT} : {e2}")
        print("Arrêt du serveur...")
        exit(1)

server_socket.listen(1)
print(f"Serveur en attente sur le port {PORT}...\n")

while True:
    conn, addr = server_socket.accept()
    print(f"Client connecté : {addr}")
    
    try:
        # RÉCEPTION borne + parking
        data = conn.recv(1024).decode("utf-8").strip()
        
        if not data:
            print("Client déconnecté lors de la réception de la borne et du parking")
            continue
        
        # Ignorer le PING du client (vérification de connexion)
        if data == "PING":
            print("PING reçu du client (vérification de connexion)")
            continue
        
        try:
            print(f"Reçu : {data}")
            id_borne, id_parking = data.strip().split(";")
            print("Commence par tester la borne et le parking...")
        except:
            conn.sendall("ERREUR;FORMAT_INVALIDE\n".encode("utf-8"))
            print("Format invalide reçu")
            continue
        
        if len(data) > 50:
            conn.sendall("ERREUR;DONNEES_TROP_LONGUES\n".encode("utf-8"))
            print("Données trop longues")
            continue
        
        msg_borne = tester_borne_parking(id_parking, id_borne)
        print(f"Réponse : {msg_borne}")
        conn.sendall((msg_borne + "\n").encode("utf-8"))
        
        if msg_borne.startswith("ERREUR"):
            print("Borne incorrecte, fin communication.")
            continue
        
        # RÉCEPTION ID CONTRAT
        data2 = conn.recv(1024).decode("utf-8").strip()
        
        if not data2:
            print("Client déconnecté lors de la réception du contrat")
            continue
        
        if len(data2) > 20:
            conn.sendall("ERREUR;DONNEES_TROP_LONGUES\n".encode("utf-8"))
            print("Contrat trop long")
            continue
        
        print(f"Reçu : {data2}")
        id_contrat = data2.strip()
        print("Commence par scanner le contrat...")
        
        valide, msg_contrat = scanne_contrat(id_contrat, id_borne)
        
        if isinstance(msg_contrat, tuple):
            try:
                msg_contrat = str(msg_contrat[-1])
            except Exception:
                msg_contrat = " ".join(map(str, msg_contrat))
        
        conn.sendall((msg_contrat + "\n").encode("utf-8"))
        print(f"Réponse : {msg_contrat}")
        
    except Exception as e:
        print(f"Erreur serveur : {e}")
    finally:
        conn.close()
        print("Connexion fermée.\n")