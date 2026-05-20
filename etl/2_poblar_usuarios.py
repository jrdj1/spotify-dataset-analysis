# 2_poblar_usuarios.py
import time
import mysql.connector
from db_config import CONFIG, get_csv_path

def poblar():
    csv_path = get_csv_path('ListaUsuarios.csv')
    print(f"-> Procesando masivamente {csv_path} vía LOAD DATA LOCAL INFILE...")

    conexion = mysql.connector.connect(**CONFIG)
    cursor = conexion.cursor()

    consulta = """
        LOAD DATA LOCAL INFILE %s 
        IGNORE INTO TABLE usuarios 
        CHARACTER SET utf8mb4 
        FIELDS TERMINATED BY ';' 
        ENCLOSED BY '"' 
        LINES TERMINATED BY '\\n' 
        IGNORE 1 LINES 
        (dni, nombre, apellidos, id_direccion, fecha_nacimiento);
    """

    t0 = time.time()
    cursor.execute(consulta, (csv_path,))
    conexion.commit()
    t1 = time.time()

    print(f"✓ Tabla 'usuarios' poblada con éxito en {t1 - t0:.3f} segundos ({cursor.rowcount} filas procesadas).")

    # Desnormalización 1:M — poblar poblacion y provincia desde direcciones
    # Evita JOINs con direcciones en consultas de BI
    print("-> Desnormalizando: copiando poblacion y provincia desde direcciones...")
    t2 = time.time()
    cursor.execute("""
        UPDATE usuarios u
        JOIN direcciones d ON u.id_direccion = d.id_direccion
        SET u.poblacion = d.poblacion,
            u.provincia = d.provincia
    """)
    conexion.commit()
    t3 = time.time()
    print(f"✓ Desnormalización completada en {t3 - t2:.3f} segundos ({cursor.rowcount} filas actualizadas).")

    cursor.close()
    conexion.close()

if __name__ == "__main__":
    poblar()