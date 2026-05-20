# 3_poblar_canciones.py
import csv
import time
import tempfile
import os
import unicodedata
import mysql.connector
from db_config import CONFIG, get_csv_path

def nfc(text):
    return unicodedata.normalize('NFC', text) if text else text

def preparar_csv_normalizado(csv_origen):
    """Lee el CSV original, aplica NFC a track_name y artist_name,
    y escribe un fichero temporal con los datos transformados.
    Devuelve la ruta del temporal (el llamador es responsable de borrarlo)."""
    tmp = tempfile.NamedTemporaryFile(
        mode='w', suffix='.csv', delete=False, encoding='utf-8', newline=''
    )
    modificadas = 0
    with open(csv_origen, encoding='utf-8', newline='') as fin:
        reader = csv.DictReader(fin)
        writer = csv.DictWriter(tmp, fieldnames=reader.fieldnames)
        writer.writeheader()
        for row in reader:
            orig_name   = row['track_name']
            orig_artist = row['artist_name']
            row['track_name']  = nfc(orig_name)
            row['artist_name'] = nfc(orig_artist)
            if row['track_name'] != orig_name or row['artist_name'] != orig_artist:
                modificadas += 1
            writer.writerow(row)
    tmp.close()
    print(f"   Normalización NFC aplicada ({modificadas:,} filas modificadas).")
    return tmp.name

def poblar():
    csv_path = get_csv_path('spotify_data.csv')
    print(f"-> Normalizando Unicode (NFC) de {csv_path}...")
    tmp_path = preparar_csv_normalizado(csv_path)

    print(f"-> Cargando en BD vía LOAD DATA LOCAL INFILE (alta velocidad)...")
    try:
        conexion = mysql.connector.connect(**CONFIG)
        cursor = conexion.cursor()

        # 1. Deshabilitamos índices para acelerar drásticamente la inserción masiva
        try:
            cursor.execute("ALTER TABLE canciones DISABLE KEYS;")
        except Exception:
            pass

        # 2. Volcado directo desde el fichero temporal normalizado
        consulta = """
            LOAD DATA LOCAL INFILE %s
            IGNORE INTO TABLE canciones
            CHARACTER SET utf8mb4
            FIELDS TERMINATED BY ','
            ENCLOSED BY '"'
            LINES TERMINATED BY '\\n'
            IGNORE 1 LINES
            (@dummy, artist_name, track_name, track_id, popularity, year, genre,
             danceability, energy, `key`, loudness, `mode`, speechiness,
             acousticness, instrumentalness, liveness, valence, tempo,
             duration_ms, time_signature);
        """

        t0 = time.time()
        cursor.execute(consulta, (tmp_path,))
        conexion.commit()

        # 3. Reconstruimos los índices de una sola pasada al finalizar
        try:
            cursor.execute("ALTER TABLE canciones ENABLE KEYS;")
        except Exception:
            pass
        t1 = time.time()

        print(f"✓ Tabla 'canciones' poblada con éxito en {t1 - t0:.3f} segundos ({cursor.rowcount} filas procesadas).")
        cursor.close()
        conexion.close()
    finally:
        os.remove(tmp_path)

if __name__ == "__main__":
    poblar()
