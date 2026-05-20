import os
from dotenv import load_dotenv

# Carga las variables de etl/.env (si existe; no falla si no está)
load_dotenv(os.path.join(os.path.dirname(__file__), '.env'))

CONFIG = {
    'host':             os.getenv('DB_HOST', 'localhost'),
    'user':             os.getenv('DB_USER', 'root'),
    'password':         os.getenv('DB_PASS', ''),
    'database':         os.getenv('DB_NAME', 'spotify_cm'),
    'charset':          'utf8mb4',
    'collation':        'utf8mb4_spanish_ci',
    'allow_local_infile': True
}

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_DIR = os.path.join(BASE_DIR, 'data')

def get_csv_path(filename):
    return os.path.join(DATA_DIR, filename)
