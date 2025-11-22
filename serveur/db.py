import psycopg2
from dotenv import load_dotenv
import os

# Charger le fichier .env
load_dotenv()

# Récupérer les variables d'environnement
HOST = os.getenv("DB_HOST")
PORT = int(os.getenv("DB_PORT", 5432))  # default 5432 si non défini
DBNAME = os.getenv("DB_NAME")
USER = os.getenv("DB_USER")
PASSWORD = os.getenv("DB_PASSWORD")


def get_connection():
    return psycopg2.connect(
        host=HOST,
        port=PORT,
        dbname=DBNAME,
        user=USER,
        password=PASSWORD
    )
