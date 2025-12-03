# Serveur TCP du système de parking : vérifie la borne et le contrat.
# Reçoit ID_BORNE;ID_PARKING puis un ID_CONTRAT, et retourne les statuts correspondants.
# Gère les erreurs, les timeouts et journalise toutes les opérations.

import socket
import logging
from parking import tester_borne_parking, scanne_contrat, demander_port, obtenir_port

HOST = "0.0.0.0"  # Écoute sur toutes les interfaces réseau
DEFAULT_PORT = 5000  # Port de secours si celui configuré est indisponible
CLIENT_TIMEOUT = 180  # Timeout en secondes pour chaque client

# --- Configuration des logs ---
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - [%(levelname)s] - %(message)s',
    handlers=[
        logging.FileHandler('serveur.log', encoding='utf-8'),
        logging.StreamHandler()
    ]
)

# --- Démarrage du serveur ---
logging.info("="*60)
logging.info("DEMARRAGE DU SERVEUR PARKING")
logging.info("="*60)

PORT = obtenir_port()  # Récupère le port défini par l'utilisateur ou un port par défaut

server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

try:
    server_socket.bind((HOST, PORT))
    logging.info(f"Bind reussi sur {HOST}:{PORT}")
except OSError as e:
    if e.errno == 98:
        logging.error(f"ERREUR : Le port {PORT} est déjà utilisé")
        logging.error("   -> Un autre serveur écoute déjà sur ce port")
    elif e.errno == 13:
        logging.error(f"ERREUR : Permission refusée pour le port {PORT}")
    else:
        logging.error(f"ERREUR OSError : {e}")
    
    logging.info(f"Tentative avec le port par défaut {DEFAULT_PORT}...")

    try:
        server_socket.close()
        server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        server_socket.bind((HOST, DEFAULT_PORT))
        PORT = DEFAULT_PORT
        logging.info(f"Bind réussi sur le port par défaut {DEFAULT_PORT}")
    except Exception as e2:
        logging.error(f"Impossible de lier le port par défaut {DEFAULT_PORT} : {e2}")
        logging.info("Arrêt du serveur...")
        exit(1)

server_socket.listen(5)
logging.info(f"Serveur en écoute sur {HOST}:{PORT}")
logging.info(f"Timeout client : {CLIENT_TIMEOUT} secondes")
logging.info("En attente de connexions clients...")
logging.info("="*60 + "\n")

# --- Boucle principale du serveur ---
try:
    while True:
        conn, addr = server_socket.accept()
        conn.settimeout(CLIENT_TIMEOUT)  # Timeout appliqué à ce client

        logging.info("="*60)
        logging.info(f"Nouvelle connexion depuis {addr[0]}:{addr[1]}")
        logging.info(f"Timeout défini : {CLIENT_TIMEOUT}s")

        try:
            # ---- ETAPE 1 : RÉCEPTION BORNE + PARKING ----
            logging.info(f"[{addr[0]}] Attente BORNE;PARKING...")

            try:
                data = conn.recv(1024).decode("utf-8").strip()
            except socket.timeout:
                logging.error(f"[{addr[0]}] TIMEOUT : aucune donnée reçue")
                conn.sendall("ERREUR;TIMEOUT_SERVEUR\n".encode("utf-8"))
                continue

            if not data:
                logging.warning(f"[{addr[0]}] Client déconnecté")
                continue

            if data == "PING":  # Test de connexion
                logging.debug(f"[{addr[0]}] PING reçu")
                continue

            logging.info(f"[{addr[0]}] Reçu : {data}")

            # Validation du format
            try:
                id_borne, id_parking = data.split(";")
            except ValueError:
                conn.sendall("ERREUR;FORMAT_INVALIDE\n".encode("utf-8"))
                continue

            if len(data) > 50:
                conn.sendall("ERREUR;DONNEES_TROP_LONGUES\n".encode("utf-8"))
                continue

            logging.info(f"[{addr[0]}] Vérification borne+parking...")

            # Vérifie si la borne appartient bien au parking
            msg_borne = tester_borne_parking(id_parking, id_borne)

            conn.sendall((msg_borne + "\n").encode("utf-8"))

            if msg_borne.startswith("ERREUR"):
                logging.warning(f"[{addr[0]}] Borne INVALIDE")
                continue

            logging.info(f"[{addr[0]}] Borne OK, attente ID contrat...")

            # ---- ETAPE 2 : RÉCEPTION ID CONTRAT ----
            try:
                data2 = conn.recv(1024).decode("utf-8").strip()
            except socket.timeout:
                conn.sendall("ERREUR;TIMEOUT_SERVEUR\n".encode("utf-8"))
                continue

            if not data2:
                logging.warning(f"[{addr[0]}] Client déconnecté pendant contrat")
                continue

            if len(data2) > 20:
                conn.sendall("ERREUR;DONNEES_TROP_LONGUES\n".encode("utf-8"))
                continue

            id_contrat = data2
            logging.info(f"[{addr[0]}] Contrat reçu : {id_contrat}")

            logging.info(f"[{addr[0]}] Vérification du contrat...")

            # Vérifie si le contrat est valide pour cette borne
            valide, msg_contrat = scanne_contrat(id_contrat, id_borne)

            if isinstance(msg_contrat, tuple):
                msg_contrat = str(msg_contrat[-1])

            conn.sendall((msg_contrat + "\n").encode("utf-8"))

            if valide:
                logging.info(f"[{addr[0]}] Transaction réussie")
            else:
                logging.warning(f"[{addr[0]}] Transaction échouée")

        except socket.timeout:
            conn.sendall("ERREUR;TIMEOUT_SERVEUR\n".encode("utf-8"))
        except ConnectionResetError:
            logging.error(f"[{addr[0]}] Connexion réinitialisée")
        except BrokenPipeError:
            logging.error(f"[{addr[0]}] Déconnexion brutale")
        except Exception as e:
            logging.error(f"[{addr[0]}] ERREUR : {e}", exc_info=True)
        finally:
            conn.close()
            logging.info(f"[{addr[0]}] Connexion fermée")
            logging.info("="*60 + "\n")

except KeyboardInterrupt:
    logging.info("\nArrêt manuel du serveur (Ctrl+C)")
finally:
    server_socket.close()
    logging.info("Socket serveur fermée proprement")
