import socket
import logging
from parking import tester_borne_parking, scanne_contrat, demander_port, obtenir_port

HOST = "0.0.0.0"
DEFAULT_PORT = 5000
CLIENT_TIMEOUT = 180  # secondes

# Configuration des logs
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - [%(levelname)s] - %(message)s',
    handlers=[
        logging.FileHandler('serveur.log', encoding='utf-8'),
        logging.StreamHandler()
    ]
)


# Demarrage du serveur
logging.info("="*60)
logging.info("DEMARRAGE DU SERVEUR PARKING")
logging.info("="*60)

PORT = obtenir_port()

server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

try:
    server_socket.bind((HOST, PORT))
    logging.info(f"Bind reussi sur {HOST}:{PORT}")
except OSError as e:
    if e.errno == 98:  # Address already in use
        logging.error(f"ERREUR : Le port {PORT} est deja utilise")
        logging.error(f"   -> Un autre serveur ecoute sur ce port")
        logging.error(f"   -> Utilisez 'lsof -i :{PORT}' ou 'netstat -tuln | grep {PORT}'")
        logging.error(f"   -> Ou choisissez un autre port")
    elif e.errno == 13:  # Permission denied
        logging.error(f"ERREUR : Permission refusee pour le port {PORT}")
        logging.error(f"   -> Les ports < 1024 necessitent les droits root/admin")
        logging.error(f"   -> Utilisez un port >= 1024")
    else:
        logging.error(f"ERREUR OSError : {e}")
    
    logging.info(f"Tentative avec le port par defaut {DEFAULT_PORT}...")
    
    try:
        server_socket.close()
        server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        server_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        server_socket.bind((HOST, DEFAULT_PORT))
        PORT = DEFAULT_PORT
        logging.info(f"Bind reussi sur le port par defaut {DEFAULT_PORT}")
    except Exception as e2:
        logging.error(f"Impossible de lier le port par defaut {DEFAULT_PORT} : {e2}")
        logging.info("Arret du serveur...")
        exit(1)

server_socket.listen(5)
logging.info(f"Serveur en ecoute sur {HOST}:{PORT}")
logging.info(f"Timeout client configure : {CLIENT_TIMEOUT} secondes")
logging.info(f"En attente de connexions clients...")
logging.info("="*60 + "\n")

# Boucle principale
try:
    while True:
        conn, addr = server_socket.accept()
        
        # CONFIGURER LE TIMEOUT POUR CE CLIENT
        conn.settimeout(CLIENT_TIMEOUT)
        
        logging.info("="*60)
        logging.info(f"Nouvelle connexion depuis {addr[0]}:{addr[1]}")
        logging.info(f"Timeout defini : {CLIENT_TIMEOUT}s pour ce client")
        
        try:
            # ETAPE 1 : RECEPTION BORNE + PARKING
            logging.info(f"[{addr[0]}] Attente des donnees borne+parking...")
            
            try:
                data = conn.recv(1024).decode("utf-8").strip()
            except socket.timeout:
                logging.error(f"[{addr[0]}] TIMEOUT : Le client n'a pas envoye de donnees dans les {CLIENT_TIMEOUT}s")
                conn.sendall("ERREUR;TIMEOUT_SERVEUR\n".encode("utf-8"))
                continue
            
            if not data:
                logging.warning(f"[{addr[0]}] Client deconnecte (aucune donnee)")
                continue
            
            # Ignorer le PING
            if data == "PING":
                logging.debug(f"[{addr[0]}] PING recu (verification connexion)")
                continue
            
            logging.info(f"[{addr[0]}] Recu : {data}")
            
            # Validation du format
            try:
                id_borne, id_parking = data.strip().split(";")
                logging.info(f"[{addr[0]}] Analyse : borne={id_borne}, parking={id_parking}")
            except ValueError:
                logging.error(f"[{addr[0]}] Format invalide : '{data}' (attendu: BORNE;PARKING)")
                conn.sendall("ERREUR;FORMAT_INVALIDE\n".encode("utf-8"))
                continue
            
            # Validation de la longueur
            if len(data) > 50:
                logging.warning(f"[{addr[0]}] Donnees trop longues : {len(data)} bytes (max 50)")
                conn.sendall("ERREUR;DONNEES_TROP_LONGUES\n".encode("utf-8"))
                continue
            
            # Verification borne + parking
            logging.info(f"[{addr[0]}] Verification borne+parking dans la BD...")
            msg_borne = tester_borne_parking(id_parking, id_borne)
            
            logging.info(f"[{addr[0]}] Envoi reponse : {msg_borne}")
            try:
                conn.sendall((msg_borne + "\n").encode("utf-8"))
            except socket.timeout:
                logging.error(f"[{addr[0]}] TIMEOUT lors de l'envoi de la reponse borne")
                continue
            
            if msg_borne.startswith("ERREUR"):
                logging.warning(f"[{addr[0]}] Borne incorrecte, fin de communication")
                continue
            
            logging.info(f"[{addr[0]}] Borne validee, attente du contrat...")
            
            # ETAPE 2 : RECEPTION ID CONTRAT
            try:
                data2 = conn.recv(1024).decode("utf-8").strip()
            except socket.timeout:
                logging.error(f"[{addr[0]}] TIMEOUT : Le client n'a pas envoye le contrat dans les {CLIENT_TIMEOUT}s")
                conn.sendall("ERREUR;TIMEOUT_SERVEUR\n".encode("utf-8"))
                continue
            
            if not data2:
                logging.warning(f"[{addr[0]}] Client deconnecte (attente contrat)")
                continue
            
            if len(data2) > 20:
                logging.warning(f"[{addr[0]}] ID contrat trop long : {len(data2)} bytes (max 20)")
                conn.sendall("ERREUR;DONNEES_TROP_LONGUES\n".encode("utf-8"))
                continue
            
            logging.info(f"[{addr[0]}] ID Contrat recu : {data2}")
            id_contrat = data2.strip()
            
            logging.info(f"[{addr[0]}] Verification du contrat dans la BD...")
            valide, msg_contrat = scanne_contrat(id_contrat, id_borne)
            
            # Normalisation du message
            if isinstance(msg_contrat, tuple):
                try:
                    msg_contrat = str(msg_contrat[-1])
                except Exception:
                    msg_contrat = " ".join(map(str, msg_contrat))
            
            logging.info(f"[{addr[0]}] Envoi reponse finale : {msg_contrat}")
            try:
                conn.sendall((msg_contrat + "\n").encode("utf-8"))
            except socket.timeout:
                logging.error(f"[{addr[0]}] TIMEOUT lors de l'envoi de la reponse finale")
                continue
            
            if valide:
                logging.info(f"[{addr[0]}] Transaction reussie")
            else:
                logging.warning(f"[{addr[0]}] Transaction echouee : {msg_contrat}")
            
        except socket.timeout:
            logging.error(f"[{addr[0]}] TIMEOUT GENERAL : Client trop lent (>{CLIENT_TIMEOUT}s)")
            try:
                conn.sendall("ERREUR;TIMEOUT_SERVEUR\n".encode("utf-8"))
            except:
                pass  # Ignore si l'envoi échoue
                
        except ConnectionResetError:
            logging.error(f"[{addr[0]}] Connexion reinitialisee par le client")
        except BrokenPipeError:
            logging.error(f"[{addr[0]}] Client deconnecte brutalement (broken pipe)")
        except Exception as e:
            logging.error(f"[{addr[0]}] Erreur inattendue : {e}", exc_info=True)
        finally:
            conn.close()
            logging.info(f"[{addr[0]}] Connexion fermee")
            logging.info("="*60 + "\n")

except KeyboardInterrupt:
    logging.info("\nInterruption utilisateur (Ctrl+C)")
    logging.info("="*60)
    logging.info("ARRET DU SERVEUR")
    logging.info("="*60)
finally:
    server_socket.close()
    logging.info("Socket serveur ferme proprement")