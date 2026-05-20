# 1_poblar_direcciones.py
import time
import mysql.connector
from db_config import CONFIG, get_csv_path

def poblar():
    csv_path = get_csv_path('ListaDirecciones.csv')
    print(f"-> Procesando masivamente {csv_path} vía LOAD DATA LOCAL INFILE...")

    conexion = mysql.connector.connect(**CONFIG)
    cursor = conexion.cursor()

    consulta = """
        LOAD DATA LOCAL INFILE %s 
        IGNORE INTO TABLE direcciones 
        CHARACTER SET utf8mb4 
        FIELDS TERMINATED BY ';' 
        ENCLOSED BY '"' 
        LINES TERMINATED BY '\\n' 
        IGNORE 1 LINES 
        (id_direccion, direccion, numero, poblacion, cpostal, provincia);
    """

    t0 = time.time()
    cursor.execute(consulta, (csv_path,))
    conexion.commit()
    t1 = time.time()

    print(f"✓ Tabla 'direcciones' poblada con éxito en {t1 - t0:.3f} segundos ({cursor.rowcount} filas procesadas).")
    cursor.close()
    conexion.close()

if __name__ == "__main__":
    poblar()