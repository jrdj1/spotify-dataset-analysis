# spotify_cm — Aplicación Web PHP 8 MVC

Panel web con dos módulos interactivos sobre la base de datos `spotify_cm`. Desarrollado en PHP 8 puro con arquitectura MVC y un único Front Controller, sin frameworks externos.

**URL en producción:** http://79.72.55.215/spotify_cm/

---

## Estructura

```
spotify_cm/
├── index.php                        ← Front Controller (?modulo=)
├── Conexion.php                     ← PDO con ATTR_EMULATE_PREPARES=false
├── .htaccess                        ← reescritura de URLs con mod_rewrite
│
├── panel/
│   ├── MantenimientoController.php
│   ├── MantenimientoModel.php
│   └── MantenimientoView.php
│
└── visualizaciones/
    ├── VisualizacionesController.php
    ├── VisualizacionesModel.php
    └── VisualizacionesView.php
```

---

## Requisitos

- PHP 8.0+
- Apache 2.4 con `mod_rewrite` habilitado y `AllowOverride All`
- MySQL 8.0 con el esquema `spotify_cm` cargado (ver `data_model/` y `etl/`)
- En Oracle Linux / RHEL con SELinux activo:
  ```bash
  chcon -R -t httpd_sys_content_t /var/www/html/spotify_cm/
  setsebool -P httpd_can_network_connect_db 1
  ```

---

## Instalación

```bash
# 1. Copiar al servidor web
cp -r spotify_cm/ /var/www/html/

# 2. Editar credenciales de base de datos
nano /var/www/html/spotify_cm/Conexion.php
# Ajustar: host, dbname, usuario, contraseña

# 3. Verificar que mod_rewrite está activo
a2enmod rewrite
systemctl restart apache2   # Debian/Ubuntu
# systemctl restart httpd   # RHEL/Oracle Linux
```

Abrir en el navegador: `http://<servidor>/spotify_cm/`

---

## Módulos

### `?modulo=panel` — Panel de Estado

Monitorización en tiempo real del servidor y la base de datos.

| Sección | Detalle |
|---------|---------|
| **KPI Almacenamiento** | `disk_free_space()` → espacio libre en disco; `INFORMATION_SCHEMA` → tamaño del esquema (MB) |
| **KPI Rendimiento** | Profiling MySQL (`SET profiling=1`, `FLUSH TABLES`, `SHOW PROFILES`) — tiempo en frío vs. caliente con semáforo visual |
| **Recolector de basura** | Mueve filas con `year < 2010 AND popularity < 30` a `favoritas_historico` (motor ARCHIVE, compresión zlib ~10:1). Patrón Post/Redirect/Get |

### `?modulo=visualizaciones` — Cuadros de Mando

Cuatro dashboards interactivos con datos cargados vía AJAX/JSON.

| Dashboard | Librería | Interactividad |
|-----------|----------|----------------|
| Artistas favoritos | Chart.js 4 | Filtro por ciudad (petición AJAX en tiempo real) |
| Catálogo de canciones | DataTables 1.13 | Server-side processing: búsqueda, ordenación, paginación |
| Top 15 canciones | Chart.js 4 | Barras horizontales con tooltips detallados |
| Mapa de usuarios | Leaflet 1.9 + CARTO dark | Marcadores proporcionales por provincia, popups |

---

## Arquitectura MVC

```
Navegador
   ↓  HTTP request (?modulo=panel)
Apache (.htaccess → index.php)
   ↓
index.php  ← Front Controller: lee ?modulo=, instancia Controlador
   ↓
MantenimientoController / VisualizacionesController
   ↓
Modelo (PDO)  ←→  MySQL 8.0 (localhost)
   ↓
Vista (HTML + JS)  →  Navegador
```

**Seguridad:** PDO con `ATTR_EMULATE_PREPARES = false` — los prepared statements se ejecutan en el servidor MySQL, inmunes a SQL injection por diseño.
