import psycopg2                 # Importer la bibliothèque pour se connecter à PostgreSQL
from dotenv import load_dotenv  # Importer pour charger les variables depuis un fichier .env
import os                       # Importer pour accéder aux variables d'environnement

# Charger le fichier .env et rendre disponibles les variables définies
load_dotenv()

# Récupérer l'adresse du serveur PostgreSQL depuis le .env
HOST = os.getenv("DB_HOST")

# Récupérer le port, avec 5432 par défaut si non défini
PORT = int(os.getenv("DB_PORT", 5432))

# Récupérer le nom de la base de données depuis le .env
DBNAME = os.getenv("DB_NAME")

# Récupérer le nom d'utilisateur pour se connecter à PostgreSQL
USER = os.getenv("DB_USER")

# Récupérer le mot de passe pour se connecter à PostgreSQL
PASSWORD = os.getenv("DB_PASSWORD")

# Fonction pour obtenir une connexion à la base PostgreSQL
def get_connection():
    # Retourne un objet connexion utilisable avec cursor()
    return psycopg2.connect(
        host=HOST,       # Hôte / serveur de la base
        port=PORT,       # Port PostgreSQL
        dbname=DBNAME,   # Nom de la base de données
        user=USER,       # Nom d'utilisateur
        password=PASSWORD # Mot de passe
    )
