# data_model — Modelo Relacional y Scripts SQL

Contiene el modelo de datos MySQL Workbench, el diagrama EER exportado y los scripts SQL para crear, optimizar y corregir el esquema `spotify_cm`.

---

## Contenido

| Fichero | Descripción |
|---------|-------------|
| `spotify_cm_data_model.mwb` | Modelo MySQL Workbench 8.0 — abre con File → Open Model |
| `spotify_cm_EER.pdf` | Diagrama EER exportado (consulta rápida sin necesidad de Workbench) |
| `scripts_bd/script_creacion_bd_optimizada.sql` | DDL completo: esquema, 4 tablas, claves primarias y foráneas |
| `scripts_bd/script_indices.sql` | Crea los 4 índices secundarios de rendimiento |
| `scripts_bd/script_correccion_canciones.sql` | Corrige 2 filas con `track_id` erróneo (`'52'` y `'2'`) |

---

## Esquema de la base de datos

```
spotify_cm
│
├── direcciones   (PK: id_direccion)
│     codigo_postal, poblacion, provincia
│
├── usuarios      (PK: dni · FK: id_direccion → direcciones)
│     nombre, apellidos, fecha_nacimiento
│     poblacion*, provincia*          ← desnormalizados desde direcciones
│
├── canciones     (PK: track_id)
│     artist_name, track_name, genre, year, popularity
│     danceability, energy, key, loudness, tempo …
│
└── favoritas     (PK: dni + track_id · FK → usuarios, canciones)
      poblacion*, artist_name*        ← desnormalizados para eliminar JOINs en BI
```

`*` columnas desnormalizadas para mejorar el rendimiento de las consultas de los dashboards.

---

## Orden de ejecución de los scripts

```bash
# Prerequisito: MySQL 8.0 corriendo, usuario con permisos CREATE/INSERT

# 1. Crear el esquema y las tablas
mysql -u root -p < scripts_bd/script_creacion_bd_optimizada.sql

# 2. Cargar los datos (ver etl/)

# 3. Crear los índices DESPUÉS de la carga (mucho más rápido)
mysql -u root -p spotify_cm < scripts_bd/script_indices.sql

# 4. Aplicar correcciones de datos
mysql -u root -p spotify_cm < scripts_bd/script_correccion_canciones.sql
```

> **Nota:** los índices se crean tras la carga para aprovechar el bulk-insert sin el overhead de mantenerlos fila a fila. Equivale a `ALTER TABLE DISABLE KEYS` / `ENABLE KEYS` a nivel de script.

---

## Tablas y volumen

| Tabla | Filas | Tamaño aprox. |
|-------|------:|--------------:|
| `canciones` | 1.159.764 | ~520 MB |
| `usuarios` | 300.000 | ~35 MB |
| `direcciones` | 150.000 | ~12 MB |
| `favoritas` | 2.099.985 | ~430 MB |
| **Total** | **3.709.749** | **~997 MB** |

---

## Optimizaciones de rendimiento

| Optimización | Impacto |
|---|---|
| `innodb_buffer_pool_size = 6G` en `/etc/my.cnf` | JOIN complejo: 10,094 s → 0,594 s (▼94%) |
| `idx_canciones_artist` en `artist_name` | Acelera agrupaciones por artista |
| `idx_fav_dni` en `favoritas.dni` | Acelera consultas de favoritas por usuario |
| `idx_fav_track` en `favoritas.track_id` | Acelera consultas de favoritas por canción |
| `idx_dir_poblacion` en `direcciones.poblacion` | Acelera filtros geográficos |
