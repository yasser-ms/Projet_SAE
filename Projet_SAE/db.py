import psycopg2

# 🔹 Paramètres de connexion
HOST = "postgresql-groupea5.alwaysdata.net"
PORT = 5432
DBNAME = "groupea5_bd"
USER = "groupea5"
PASSWORD = "yasser0048919"


# 🔹 Fonction pour obtenir une connexion
def get_connection():
    return psycopg2.connect(
        host=HOST,
        port=PORT,
        dbname=DBNAME,
        user=USER,
        password=PASSWORD
    )

