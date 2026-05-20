# Spotify Dataset Analysis — Cuadros de Mando

![Python](https://img.shields.io/badge/Python-3.8%2B-3776AB?logo=python&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-1DB954)

**Asignatura:** Gestión de la Información · Universidad de Alicante  
**Autor:** Jorge Rafael de Julián Vicedo

Pipeline de ingesta masiva y panel web MVC sobre 3,7 millones de registros del dataset de Spotify (MySQL 8 · PHP 8 · Python 3).

**Base de datos en producción:** MySQL 8.4.8 en Oracle Cloud  
**Aplicación web en producción:** http://79.72.55.215/spotify_cm/

---

## Estructura del repositorio

```
spotify-dataset-analysis/
├── README.md
├── Informe.docx               ← informe técnico completo
├── Presentacion.pptx          ← presentación (12 diapositivas)
│
├── data_model/                ← modelo relacional y scripts SQL
│   ├── README.md
│   ├── spotify_cm_data_model.mwb
│   ├── spotify_cm_EER.pdf
│   └── scripts_bd/
│       ├── script_creacion_bd_optimizada.sql
│       ├── script_indices.sql
│       └── script_correccion_canciones.sql
│
├── etl/                       ← pipeline de carga masiva Python
│   ├── README.md
│   ├── orquestador.py
│   ├── 1_poblar_direcciones.py
│   ├── 2_poblar_usuarios.py
│   ├── 3_poblar_canciones.py
│   ├── 4_poblar_favoritas.py
│   ├── generar_favoritas.py
│   ├── db_config.py
│   ├── requirements.txt
│   └── data/                  ← ⚠️ no incluido en el repo (ver "Obtener los datos")
│
└── spotify_cm/                ← aplicación web PHP 8 MVC
    ├── README.md
    ├── index.php
    ├── Conexion.php
    ├── .htaccess
    ├── panel/
    └── visualizaciones/
```

---

## Clonar y arrancar

```bash
# 1. Clonar el repositorio
git clone https://github.com/jrdj1/spotify-dataset-analysis.git
cd spotify-dataset-analysis

# 2. Crear la base de datos (MySQL 8.0, local_infile = ON)
mysql -u root -p < data_model/scripts_bd/script_creacion_bd_optimizada.sql

# 3. Obtener los CSVs (ver sección siguiente) y colocarlos en etl/data/

# 4. Ejecutar el pipeline de carga
cd etl
pip install -r requirements.txt
python orquestador.py

# 5. Añadir los índices de rendimiento tras la carga
mysql -u root -p spotify_cm < ../data_model/scripts_bd/script_indices.sql
mysql -u root -p spotify_cm < ../data_model/scripts_bd/script_correccion_canciones.sql

# 6. Desplegar la app web (Apache 2.4 + PHP 8)
cp -r ../spotify_cm/ /var/www/html/
# Editar Conexion.php con las credenciales locales
```

---

## Obtener los datos

Los CSVs no están incluidos en el repositorio por su tamaño (~258 MB en total).

| Fichero | Origen |
|---------|--------|
| `spotify_data.csv` | [Kaggle · Spotify Dataset 1921–2020](https://www.kaggle.com/datasets/vatsalmavani/spotify-dataset) — descargar y renombrar a `spotify_data.csv` |
| `ListaDirecciones.csv` | Generado sintéticamente con `etl/generar_favoritas.py` |
| `ListaUsuarios.csv` | Generado sintéticamente con `etl/generar_favoritas.py` |
| `favoritas.csv` | Generado sintéticamente con `etl/generar_favoritas.py` |

Colocar todos los ficheros en `etl/data/` antes de ejecutar el orquestador.

---

## Modelo de datos

| Tabla | Filas | Descripción |
|-------|------:|-------------|
| `canciones` | 1.159.764 | Dataset Spotify: características de audio, género, popularidad |
| `usuarios` | 300.000 | Usuarios simulados con dirección y fecha de nacimiento |
| `direcciones` | 150.000 | Entidad maestra de municipios y provincias |
| `favoritas` | 2.099.985 | Relación M:N usuarios–canciones con desnormalización |

**Relaciones:** `usuarios.id_direccion → direcciones` · `favoritas.dni → usuarios` · `favoritas.track_id → canciones`

**Optimizaciones:**
- `innodb_buffer_pool_size = 6G` → reducción del 94% en JOIN complejo (10,094 s → 0,594 s)
- Índices: `idx_canciones_artist`, `idx_fav_dni`, `idx_fav_track`, `idx_dir_poblacion`
- Desnormalización de `poblacion`/`artist_name` en `favoritas` para eliminar JOINs en consultas BI

Ver [`data_model/README.md`](data_model/README.md) para más detalle.

---

## Pipeline ETL

Carga masiva vía `LOAD DATA LOCAL INFILE` con normalización Unicode NFC, `DISABLE/ENABLE KEYS` y desnormalización post-carga.

| Tabla | Filas | Tiempo (Oracle Cloud) |
|-------|------:|----------------------:|
| `direcciones` | 150.000 | 1,341 s |
| `usuarios` | 300.000 | 3,218 s |
| `canciones` | 1.159.764 | 105,692 s |
| `favoritas` | 2.099.985 | 66,199 s |

Ver [`etl/README.md`](etl/README.md) para instrucciones completas.

---

## Aplicación web

PHP 8 MVC sin framework, desplegada en Oracle Cloud (Apache 2.4, Oracle Linux 9).

| Módulo | URL | Descripción |
|--------|-----|-------------|
| Panel de Estado | `?modulo=panel` | KPIs de disco, profiling MySQL, recolector de basura (motor ARCHIVE) |
| Cuadros de Mando | `?modulo=visualizaciones` | 4 dashboards AJAX: Chart.js, DataTables 1.13, Leaflet + CARTO |

Ver [`spotify_cm/README.md`](spotify_cm/README.md) para despliegue e instalación.

---

## Stack tecnológico

| Componente | Tecnología |
|------------|------------|
| Base de datos | MySQL 8.4.8 |
| Backend web | PHP 8 (MVC sin framework) |
| Servidor web | Apache 2.4, Oracle Linux 9 |
| Infraestructura | Oracle Cloud VM.Standard.A1.Flex |
| Visualización web | Chart.js 4, DataTables 1.13, Leaflet 1.9 |
| Carga de datos | Python 3 + mysql-connector-python |
| Modelado BD | MySQL Workbench 8.0 |

---

## Licencia

[MIT](LICENSE)
