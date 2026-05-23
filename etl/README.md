# etl — Pipeline de Carga Masiva Python

Pipeline modular en Python para la carga masiva de ~3,7 millones de registros en MySQL 8.0 mediante `LOAD DATA LOCAL INFILE`, con normalización Unicode NFC y desnormalización post-carga.

---

## Estructura

| Fichero | Descripción |
|---------|-------------|
| `orquestador.py` | Punto de entrada — ejecuta los 4 scripts en orden FK |
| `1_poblar_direcciones.py` | Carga `ListaDirecciones.csv` → tabla `direcciones` |
| `2_poblar_usuarios.py` | Carga `ListaUsuarios.csv` → tabla `usuarios` + desnormaliza `poblacion`/`provincia` |
| `3_poblar_canciones.py` | Carga `spotify_data.csv` → tabla `canciones` (NFC + `@dummy` para índice pandas) |
| `4_poblar_favoritas.py` | Carga `favoritas.csv` → tabla `favoritas` + desnormaliza `poblacion`/`artist_name` |
| `generar_favoritas.py` | Script auxiliar de generación sintética de datos (no forma parte del pipeline) |
| `db_config.py` | Credenciales y rutas centralizadas — **editar antes de ejecutar** |
| `requirements.txt` | Dependencias Python |
| `data/` | Carpeta de CSVs — **no incluida en el repo** (ver más abajo) |

---

## Obtener los datos

Los CSVs no están en el repositorio por su tamaño (~258 MB). Son los ficheros
proporcionados por los profesores y deben colocarse manualmente en `etl/data/`:

```
etl/data/
├── spotify_data.csv       (168 MB)
├── ListaUsuarios.csv      ( 15 MB)
├── ListaDirecciones.csv   (  6,9 MB)
└── favoritas.csv          ( 69 MB)
```

---

## Requisitos

- Python 3.8+
- MySQL 8.0 con `local_infile = ON`
- El esquema `spotify_cm` ya creado (ver `data_model/`)

Activar `local_infile` en MySQL si no está habilitado:
```sql
SET GLOBAL local_infile = 1;
```

---

## Instalación y ejecución

```bash
# 1. Crear entorno virtual e instalar dependencias
python -m venv venv
venv\Scripts\activate        # Windows
# source venv/bin/activate   # Linux/macOS
pip install -r requirements.txt

# 2. Editar credenciales
#    Abrir db_config.py y ajustar host, user, password

# 3. Asegurarse de que los CSVs están en data/

# 4. Lanzar la carga completa
python orquestador.py
```

El orquestador respeta el orden de claves foráneas:
`direcciones → usuarios → canciones → favoritas`

---

## Tiempos de inserción reales (Oracle Cloud)

| Tabla | Filas | Tiempo |
|-------|------:|-------:|
| `direcciones` | 150.000 | 1,341 s |
| `usuarios` | 300.000 | 3,218 s |
| `canciones` | 1.159.764 | 105,692 s |
| `favoritas` | 2.099.985 | 66,199 s |
| **Total** | **3.709.749** | **~176 s** |

---

## Técnicas aplicadas

- **`LOAD DATA LOCAL INFILE`** — importación binaria directa, sin roundtrip fila a fila
- **`DISABLE / ENABLE KEYS`** — desactiva índices secundarios durante la carga y los reconstruye al final en batch
- **Normalización NFC** — `unicodedata.normalize('NFC', ...)` antes de la carga para evitar duplicados por variantes Unicode
- **Desnormalización post-carga** — `UPDATE JOIN` tras cada tabla para copiar `poblacion`, `provincia` y `artist_name` a las tablas que los necesitan, eliminando JOINs en tiempo de consulta
