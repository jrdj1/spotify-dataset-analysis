# orquestador.py
import time
import sys

# Importamos los módulos de población
import importlib

scripts = [
    ('Direcciones', '1_poblar_direcciones'),
    ('Usuarios', '2_poblar_usuarios'),
    ('Canciones', '3_poblar_canciones'),
    ('Favoritas', '4_poblar_favoritas')
]

def main():
    print("==================================================")
    print("INICIANDO ORQUESTACIÓN DE CARGA DE DATOS - SPOTIFY")
    print("==================================================")
    
    tiempo_inicio = time.time()
    
    for nombre, modulo_str in scripts:
        print(f"\n[Paso] Cargando entidad: {nombre}")
        print("--------------------------------------------------")
        try:
            # Importación dinámica del módulo
            modulo = importlib.import_module(modulo_str)
            modulo.poblar()
        except Exception as e:
            print(f"\nERROR CRÍTICO poblando la tabla {nombre}:")
            print(f"Detalle: {str(e)}")
            print("Deteniendo la orquestación para proteger la integridad del sistema.")
            sys.exit(1)
            
    tiempo_fin = time.time()
    duracion = tiempo_fin - tiempo_inicio
    
    print("\n==================================================")
    print(f"POBLACIÓN COMPLETADA EXITOSAMENTE EN {duracion:.2f} SEGUNDOS")
    print("==================================================")

if __name__ == "__main__":
    main()

