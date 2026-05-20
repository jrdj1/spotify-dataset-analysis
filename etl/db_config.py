import os

# Configuración centralizada para conexión nativa dentro de la MV
CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': 'mycontGI_7_6',
    'database': 'spotify_cm',
    'charset': 'utf8mb4',
    'collation': 'utf8mb4_spanish_ci',
    'allow_local_infile': True
}

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_DIR = os.path.join(BASE_DIR, 'data')

def get_csv_path(filename):
    return os.path.join(DATA_DIR, filename)