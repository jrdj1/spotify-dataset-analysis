# 4_poblar_favoritas.py
import time
import mysql.connector
from db_config import CONFIG, get_csv_path

def poblar():
    csv_path = get_csv_path('favoritas.csv')
    print(f"-> Procesando masivamente {csv_path} vía LOAD DATA LOCAL INFILE...")

    conexion = mysql.connector.connect(**CONFIG)
    cursor = conexion.cursor()

    try:
        cursor.execute("ALTER TABLE favoritas DISABLE KEYS;")
    except Exception:
        pass

    # Importación con saneamiento absoluto de saltos de línea CRLF/LF
    consulta = """
        LOAD DATA LOCAL INFILE %s 
        IGNORE INTO TABLE favoritas 
        CHARACTER SET utf8mb4 
        FIELDS TERMINATED BY ',' 
        ENCLOSED BY '"' 
        LINES TERMINATED BY '\\n' 
        IGNORE 1 LINES 
        (dni, @var_track_id)
        SET track_id = TRIM(REPLACE(@var_track_id, '\\r', ''));
    """

    t0 = time.time()
    cursor.execute(consulta, (csv_path,))
    conexion.commit()

    try:
        cursor.execute("ALTER TABLE favoritas ENABLE KEYS;")
    except Exception:
        pass
    t1 = time.time()

    print(f"✓ Tabla 'favoritas' poblada con éxito en {t1 - t0:.3f} segundos ({cursor.rowcount} filas procesadas).")

    # Desnormalización M:N — poblar poblacion y artist_name
    # Permite resolver el JOIN complejo en una sola tabla sin tocar usuarios ni canciones
    print("-> Desnormalizando: copiando poblacion y artist_name desde usuarios y canciones...")
    t2 = time.time()
    cursor.execute("""
        UPDATE favoritas f
        JOIN usuarios u  ON f.dni      = u.dni
        JOIN canciones c ON f.track_id = c.track_id
        SET f.poblacion   = u.poblacion,
            f.artist_name = c.artist_name
    """)
    conexion.commit()
    t3 = time.time()
    print(f"✓ Desnormalización completada en {t3 - t2:.3f} segundos ({cursor.rowcount} filas actualizadas).")

    cursor.close()
    conexion.close()

if __name__ == "__main__":
    poblar()