<?php
/**
 * VisualizacionesView.php
 * ─────────────────────────────────────────────────────────────────────────────
 * CAPA VISTA — Renderiza el panel completo de 4 cuadros de mando.
 *
 * Librerías frontend (CDN):
 *   · Chart.js 4       → Dashboards 1 y 3 (barras verticales/horizontales)
 *   · DataTables 1.13  → Dashboard 2 (catálogo server-side)
 *   · Leaflet 1.9      → Dashboard 4 (mapa de usuarios)
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

class VisualizacionesView
{
    public function renderizar(array $d): void
    {
        $titulo    = htmlspecialchars($d['titulo']);
        $timestamp = htmlspecialchars($d['timestamp']);
        $kpis      = $d['kpis'];

        // Listas para los <select> de filtros
        $ciudades = $d['ciudades'];
        $generos  = $d['generos'];

        // Ciudad inicial = primera de la lista (para carga rápida del dashboard 1)
        $ciudadInicial = !empty($ciudades) ? htmlspecialchars($ciudades[0]) : '';

        $optsCiudades = '<option value="">Todas las ciudades</option>';
        foreach ($ciudades as $c) {
            $esc = htmlspecialchars($c);
            $sel = ($c === ($ciudades[0] ?? '')) ? ' selected' : '';
            $optsCiudades .= "<option value=\"{$esc}\"{$sel}>{$esc}</option>";
        }

        $optsGeneros = '<option value="">Todos los géneros</option>';
        foreach ($generos as $g) {
            $esc = htmlspecialchars($g);
            $optsGeneros .= "<option value=\"{$esc}\">{$esc}</option>";
        }

        $nUsuarios   = number_format((int)$kpis['total_usuarios'],  0, ',', '.');
        $nCanciones  = number_format((int)$kpis['total_canciones'], 0, ',', '.');
        $nFavoritas  = number_format((int)$kpis['total_favoritas'], 0, ',', '.');
        $nGeneros    = (int)$kpis['total_generos'];

        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$titulo}</title>

  <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

  <style>
    /* ── Variables ──────────────────────────────────────────────────────────── */
    :root {
      --bg:        #0f1117;
      --surface:   #1a1d27;
      --surface2:  #22263a;
      --border:    #2a2f47;
      --accent:    #4e7fff;
      --accent2:   #00c896;
      --accent3:   #f59e0b;
      --danger:    #ef4444;
      --text:      #e2e8f0;
      --muted:     #7a8599;
      --radius:    10px;
    }

    /* ── Reset / Base ───────────────────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      font-size: 14px;
    }

    /* ── Cabecera ───────────────────────────────────────────────────────────── */
    header {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: 0 28px;
      position: sticky; top: 0; z-index: 100;
    }
    .header-inner {
      max-width: 1400px; margin: 0 auto;
      display: flex; align-items: center; gap: 16px;
      height: 60px;
    }
    .header-logo {
      font-size: 20px; font-weight: 700; color: var(--accent);
      white-space: nowrap; text-decoration: none;
      display: flex; align-items: center; gap: 8px;
    }
    .header-logo span { color: var(--text); }
    .header-kpis {
      display: flex; gap: 20px; margin-left: auto; flex-wrap: wrap;
    }
    .hkpi { text-align: center; line-height: 1.2; }
    .hkpi-val { font-size: 16px; font-weight: 700; color: var(--accent2); }
    .hkpi-lbl { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
    .nav-link {
      color: var(--muted); text-decoration: none; font-size: 12px;
      padding: 6px 12px; border: 1px solid var(--border); border-radius: 6px;
      transition: all .2s; white-space: nowrap;
    }
    .nav-link:hover { color: var(--text); border-color: var(--accent); }
    .ts { font-size: 11px; color: var(--muted); white-space: nowrap; }

    /* ── Contenedor principal ───────────────────────────────────────────────── */
    main { max-width: 1400px; margin: 0 auto; padding: 24px 28px; }

    /* ── Tabs ───────────────────────────────────────────────────────────────── */
    .tabs {
      display: flex; gap: 4px; margin-bottom: 24px;
      border-bottom: 1px solid var(--border); padding-bottom: 0;
    }
    .tab-btn {
      background: none; border: none; cursor: pointer;
      color: var(--muted); font-size: 14px; font-weight: 500;
      padding: 10px 20px; border-radius: var(--radius) var(--radius) 0 0;
      border: 1px solid transparent; border-bottom: none;
      transition: all .2s; display: flex; align-items: center; gap: 8px;
      margin-bottom: -1px;
    }
    .tab-btn:hover { color: var(--text); background: var(--surface); }
    .tab-btn.active {
      color: var(--accent); background: var(--surface);
      border-color: var(--border); border-bottom-color: var(--surface);
    }
    .tab-icon { font-size: 16px; }

    /* ── Dashboard (sección) ────────────────────────────────────────────────── */
    .dashboard { display: none; }
    .dashboard.active { display: block; }

    .dash-header { margin-bottom: 20px; }
    .dash-title {
      font-size: 20px; font-weight: 700; color: var(--text);
      display: flex; align-items: center; gap: 10px; margin-bottom: 4px;
    }
    .dash-title .icon { font-size: 22px; }
    .dash-subtitle { color: var(--muted); font-size: 13px; }

    /* ── KPI cards ──────────────────────────────────────────────────────────── */
    .kpi-row {
      display: grid; gap: 16px; margin-bottom: 24px;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
    .kpi-card {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 18px 20px;
      display: flex; flex-direction: column; gap: 6px;
    }
    .kpi-label {
      font-size: 11px; color: var(--muted);
      text-transform: uppercase; letter-spacing: .6px;
    }
    .kpi-val {
      font-size: 26px; font-weight: 700; line-height: 1;
      color: var(--accent);
    }
    .kpi-val.green  { color: var(--accent2); }
    .kpi-val.orange { color: var(--accent3); }
    .kpi-sub { font-size: 11px; color: var(--muted); }

    /* ── Filtros ────────────────────────────────────────────────────────────── */
    .filters {
      display: flex; gap: 12px; align-items: center;
      margin-bottom: 20px; flex-wrap: wrap;
    }
    .filter-label { font-size: 13px; color: var(--muted); }
    select, input[type="text"] {
      background: var(--surface2); border: 1px solid var(--border);
      color: var(--text); border-radius: 7px; padding: 8px 14px;
      font-size: 13px; outline: none;
      transition: border-color .2s;
    }
    select:focus, input[type="text"]:focus { border-color: var(--accent); }
    select option { background: var(--surface2); }

    /* ── Chart container ────────────────────────────────────────────────────── */
    .chart-card {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 24px;
    }
    .chart-wrapper { position: relative; height: 420px; }
    .chart-wrapper-h { position: relative; height: 520px; }

    /* ── Spinner de carga ───────────────────────────────────────────────────── */
    .spinner-wrap {
      display: flex; align-items: center; justify-content: center;
      height: 200px; gap: 12px; color: var(--muted);
    }
    .spinner {
      width: 24px; height: 24px; border-radius: 50%;
      border: 3px solid var(--border); border-top-color: var(--accent);
      animation: spin .8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── DataTables override (dark theme) ───────────────────────────────────── */
    .dataTables_wrapper { color: var(--text); }
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter { margin-bottom: 14px; color: var(--muted); }
    .dataTables_wrapper .dataTables_filter input {
      background: var(--surface2); border: 1px solid var(--border);
      color: var(--text); border-radius: 7px; padding: 6px 12px;
      margin-left: 8px;
    }
    .dataTables_wrapper .dataTables_length select {
      background: var(--surface2); border: 1px solid var(--border);
      color: var(--text); border-radius: 6px; padding: 4px 8px;
      margin: 0 6px;
    }
    table.dataTable { border-collapse: collapse !important; width: 100% !important; }
    table.dataTable thead th {
      background: var(--surface2) !important; color: var(--muted) !important;
      border-bottom: 1px solid var(--border) !important;
      font-size: 11px; text-transform: uppercase; letter-spacing: .5px;
      padding: 10px 14px !important; cursor: pointer;
    }
    table.dataTable thead th:hover { color: var(--text) !important; }
    table.dataTable tbody tr { background: var(--surface) !important; }
    table.dataTable tbody tr:nth-child(even) { background: var(--surface2) !important; }
    table.dataTable tbody tr:hover td { background: rgba(78,127,255,.08) !important; }
    table.dataTable tbody td {
      border-bottom: 1px solid var(--border) !important;
      padding: 9px 14px !important; color: var(--text) !important;
      font-size: 13px;
    }
    .dataTables_wrapper .dataTables_info { color: var(--muted); font-size: 12px; }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      background: none !important; color: var(--muted) !important;
      border: 1px solid transparent !important; border-radius: 6px !important;
      padding: 4px 10px !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--accent) !important; color: #fff !important;
      border-color: var(--accent) !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
      background: var(--surface2) !important; color: var(--text) !important;
      border-color: var(--border) !important;
    }
    .table-card {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 24px;
    }

    /* ── Popularity bar (en DataTables) ─────────────────────────────────────── */
    .pop-bar-wrap { display: flex; align-items: center; gap: 8px; min-width: 120px; }
    .pop-bar-bg {
      flex: 1; height: 6px; background: var(--surface2);
      border-radius: 3px; overflow: hidden;
    }
    .pop-bar-fill { height: 100%; border-radius: 3px; }
    .pop-val { font-size: 12px; color: var(--muted); min-width: 26px; text-align: right; }

    /* ── Mapa ───────────────────────────────────────────────────────────────── */
    #mapa-div {
      height: 520px; border-radius: var(--radius);
      border: 1px solid var(--border); overflow: hidden;
    }
    .leaflet-popup-content-wrapper {
      background: var(--surface2) !important;
      border: 1px solid var(--border) !important;
      color: var(--text) !important;
      border-radius: 8px !important;
    }
    .leaflet-popup-tip { background: var(--surface2) !important; }

    /* ── Responsive ─────────────────────────────────────────────────────────── */
    @media (max-width: 768px) {
      main { padding: 16px; }
      .header-kpis { display: none; }
      .tabs { overflow-x: auto; }
      .tab-btn { padding: 8px 14px; font-size: 13px; }
    }
  </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════════════════════
     CABECERA
════════════════════════════════════════════════════════════════════════════ -->
<header>
  <div class="header-inner">
    <a class="header-logo" href="index.php?modulo=visualizaciones">
      <span>&#9679;</span> <span>spotify_cm</span> <span style="color:var(--muted);font-weight:400">/ cuadros de mando</span>
    </a>

    <div class="header-kpis">
      <div class="hkpi">
        <div class="hkpi-val">{$nUsuarios}</div>
        <div class="hkpi-lbl">Usuarios</div>
      </div>
      <div class="hkpi">
        <div class="hkpi-val">{$nCanciones}</div>
        <div class="hkpi-lbl">Canciones</div>
      </div>
      <div class="hkpi">
        <div class="hkpi-val">{$nFavoritas}</div>
        <div class="hkpi-lbl">Favoritas</div>
      </div>
      <div class="hkpi">
        <div class="hkpi-val">{$nGeneros}</div>
        <div class="hkpi-lbl">Géneros</div>
      </div>
    </div>

    <a class="nav-link" href="index.php">&#127968; Inicio</a>
    <a class="nav-link" href="index.php?modulo=panel">&#9881; Mantenimiento</a>
    <span class="ts">&#128337; {$timestamp}</span>
  </div>
</header>

<!-- ═══════════════════════════════════════════════════════════════════════════
     CONTENIDO PRINCIPAL
════════════════════════════════════════════════════════════════════════════ -->
<main>

  <!-- Tabs de navegación -->
  <div class="tabs">
    <button class="tab-btn active" data-tab="1" onclick="switchTab(1)">
      <span class="tab-icon">&#127929;</span> Artistas Favoritos
    </button>
    <button class="tab-btn" data-tab="2" onclick="switchTab(2)">
      <span class="tab-icon">&#128196;</span> Catálogo
    </button>
    <button class="tab-btn" data-tab="3" onclick="switchTab(3)">
      <span class="tab-icon">&#127942;</span> Top Canciones
    </button>
    <button class="tab-btn" data-tab="4" onclick="switchTab(4)">
      <span class="tab-icon">&#127759;</span> Mapa de Usuarios
    </button>
  </div>

  <!-- ════════════════════════════════════════════════════════════════════════
       DASHBOARD 1 — Artistas más favoritos por ciudad
  ═════════════════════════════════════════════════════════════════════════ -->
  <section id="dash-1" class="dashboard active">
    <div class="dash-header">
      <div class="dash-title"><span class="icon">&#127929;</span> Artistas más favoritos por ciudad</div>
      <div class="dash-subtitle">Top 25 artistas ordenados por número de veces en favoritas · filtrables por población</div>
    </div>

    <div class="filters">
      <span class="filter-label">Ciudad:</span>
      <select id="sel-ciudad" onchange="cargarArtistas(this.value)">
        {$optsCiudades}
      </select>
    </div>

    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-label">Total favoritas</div>
        <div class="kpi-val green" id="kpi-favs">—</div>
        <div class="kpi-sub">en la ciudad seleccionada</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Usuarios activos</div>
        <div class="kpi-val" id="kpi-usuarios">—</div>
        <div class="kpi-sub">con al menos 1 favorita</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Género más escuchado</div>
        <div class="kpi-val orange" id="kpi-genero" style="font-size:18px">—</div>
        <div class="kpi-sub">por volumen de favoritas</div>
      </div>
    </div>

    <div class="chart-card">
      <div id="wrap-artistas">
        <div class="spinner-wrap">
          <div class="spinner"></div> Cargando datos…
        </div>
      </div>
      <div class="chart-wrapper" style="display:none" id="chart-artistas-wrap">
        <canvas id="canvas-artistas"></canvas>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════════════════
       DASHBOARD 2 — Catálogo de canciones
  ═════════════════════════════════════════════════════════════════════════ -->
  <section id="dash-2" class="dashboard">
    <div class="dash-header">
      <div class="dash-title"><span class="icon">&#128196;</span> Catálogo de canciones</div>
      <div class="dash-subtitle">Explorador interactivo con búsqueda, ordenación y paginación server-side</div>
    </div>

    <div class="filters">
      <span class="filter-label">Género:</span>
      <select id="sel-genero" onchange="filtrarCatalogo(this.value)">
        {$optsGeneros}
      </select>
    </div>

    <div class="table-card">
      <table id="tabla-catalogo" class="display" style="width:100%">
        <thead>
          <tr>
            <th>Canción</th>
            <th>Artista</th>
            <th>Género</th>
            <th>Popularidad</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════════════════
       DASHBOARD 3 — Top 15 canciones por popularidad
  ═════════════════════════════════════════════════════════════════════════ -->
  <section id="dash-3" class="dashboard">
    <div class="dash-header">
      <div class="dash-title"><span class="icon">&#127942;</span> Top 15 canciones por popularidad</div>
      <div class="dash-subtitle">Canciones con mayor popularidad máxima registrada en el catálogo</div>
    </div>

    <div class="chart-card">
      <div id="wrap-top">
        <div class="spinner-wrap">
          <div class="spinner"></div> Cargando datos…
        </div>
      </div>
      <div class="chart-wrapper-h" style="display:none" id="chart-top-wrap">
        <canvas id="canvas-top"></canvas>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════════════════
       DASHBOARD 4 — Mapa de usuarios por ciudad
  ═════════════════════════════════════════════════════════════════════════ -->
  <section id="dash-4" class="dashboard">
    <div class="dash-header">
      <div class="dash-title"><span class="icon">&#127759;</span> Mapa de usuarios por ciudad</div>
      <div class="dash-subtitle">Distribución geográfica de usuarios · círculos proporcionales al número de usuarios</div>
    </div>

    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-label">Total usuarios mapeados</div>
        <div class="kpi-val green" id="kpi-mapa-total">—</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Ciudades con usuarios</div>
        <div class="kpi-val" id="kpi-mapa-ciudades">—</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Ciudad con más usuarios</div>
        <div class="kpi-val orange" id="kpi-mapa-top" style="font-size:18px">—</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-label">Sin coordenadas (ocultas)</div>
        <div class="kpi-val" id="kpi-mapa-sin" style="color:var(--muted)">—</div>
      </div>
    </div>

    <div id="mapa-div"></div>
  </section>

</main>

<!-- ═══════════════════════════════════════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════════════════════════════════════════ -->
<!-- jQuery (requerido por DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
'use strict';

/* ── Paleta de colores para Chart.js ───────────────────────────────────────── */
Chart.defaults.color = '#7a8599';
Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";

const BLUE_PALETTE = (n) => Array.from({length: n}, (_, i) =>
  `hsla(\${220 + i * 2}, 75%, \${60 - i * 0.8}%, 0.85)`);
const WARM_PALETTE = (n) => Array.from({length: n}, (_, i) =>
  `hsla(\${38 - i * 2}, \${92 - i}%, \${62 - i * 1.5}%, 0.88)`);

/* ── Tabs ──────────────────────────────────────────────────────────────────── */
function switchTab(n) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.dashboard').forEach(d => d.classList.remove('active'));
  document.querySelector(`.tab-btn[data-tab="\${n}"]`).classList.add('active');
  document.getElementById(`dash-\${n}`).classList.add('active');
  // El mapa Leaflet necesita invalidateSize() al hacerse visible
  if (n === 4 && mapaUsuarios) mapaUsuarios.invalidateSize();
}

/* ══════════════════════════════════════════════════════════════════════════════
   DASHBOARD 1 — Artistas favoritos
══════════════════════════════════════════════════════════════════════════════ */
let chartArtistas = null;

async function cargarArtistas(ciudad = '') {
  // Mostrar spinner
  document.getElementById('chart-artistas-wrap').style.display = 'none';
  document.getElementById('wrap-artistas').innerHTML =
    '<div class="spinner-wrap"><div class="spinner"></div> Cargando datos…</div>';

  try {
    const res  = await fetch(`index.php?modulo=visualizaciones&accion=api_artistas&ciudad=\${encodeURIComponent(ciudad)}`);
    const data = await res.json();

    // Actualizar KPIs
    document.getElementById('kpi-favs').textContent =
      data.total_favs.toLocaleString('es-ES');
    document.getElementById('kpi-usuarios').textContent =
      data.usuarios_activos.toLocaleString('es-ES');
    document.getElementById('kpi-genero').textContent = data.top_genero;

    const labels = data.artistas.map(a => a.artist_name);
    const values = data.artistas.map(a => parseInt(a.veces));
    const colors = BLUE_PALETTE(labels.length);

    if (chartArtistas) chartArtistas.destroy();

    document.getElementById('wrap-artistas').innerHTML = '';
    document.getElementById('chart-artistas-wrap').style.display = 'block';

    chartArtistas = new Chart(
      document.getElementById('canvas-artistas').getContext('2d'), {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Veces en favoritas',
          data:  values,
          backgroundColor: colors,
          borderColor: colors.map(c => c.replace('0.85', '1')),
          borderWidth: 1,
          borderRadius: 4,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 600, easing: 'easeOutQuart' },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#1a1d27',
            borderColor: '#2a2f47', borderWidth: 1,
            callbacks: {
              label: ctx => ` \${ctx.raw.toLocaleString('es-ES')} favoritas`,
            }
          }
        },
        scales: {
          x: {
            ticks: { color: '#7a8599', maxRotation: 45, font: { size: 11 } },
            grid:  { color: '#1e2238' },
          },
          y: {
            ticks: {
              color: '#7a8599',
              callback: v => v.toLocaleString('es-ES'),
            },
            grid: { color: '#1e2238' },
          }
        }
      }
    });
  } catch (e) {
    document.getElementById('wrap-artistas').innerHTML =
      `<div class="spinner-wrap" style="color:#ef4444">&#9888; Error al cargar datos: \${e.message}</div>`;
  }
}

/* ══════════════════════════════════════════════════════════════════════════════
   DASHBOARD 2 — Catálogo server-side DataTables
══════════════════════════════════════════════════════════════════════════════ */
let tablaCatalogo = null;
let generoActual  = '';

function initCatalogo() {
  tablaCatalogo = $('#tabla-catalogo').DataTable({
    serverSide: true,
    processing: true,
    ajax: {
      url:  'index.php?modulo=visualizaciones&accion=api_catalogo',
      type: 'GET',
      data: d => { d.genero = generoActual; },
      error: (xhr, err) => {
        console.error('DataTables AJAX error:', err);
      }
    },
    columns: [
      { data: 'track_name',  title: 'Canción',  orderable: true },
      { data: 'artist_name', title: 'Artista',  orderable: true },
      { data: 'genre',       title: 'Género',   orderable: true },
      {
        data: 'popularity', title: 'Popularidad', orderable: true,
        render: function(v) {
          const pct = Math.min(100, Math.max(0, parseInt(v) || 0));
          const hue = Math.round(pct * 1.2); // 0=rojo → 120=verde
          const color = `hsl(\${hue},80%,52%)`;
          return `<div class="pop-bar-wrap">
            <div class="pop-bar-bg">
              <div class="pop-bar-fill" style="width:\${pct}%;background:\${color}"></div>
            </div>
            <span class="pop-val">\${pct}</span>
          </div>`;
        }
      },
    ],
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    order: [[3, 'desc']],
    language: {
      processing:   'Cargando…',
      search:       'Buscar:',
      lengthMenu:   'Mostrar _MENU_ registros',
      info:         'Mostrando _START_–_END_ de _TOTAL_',
      infoEmpty:    'Sin resultados',
      infoFiltered: '(filtrado de _MAX_ total)',
      zeroRecords:  'No se encontraron canciones',
      paginate: { first: '«', last: '»', next: '›', previous: '‹' },
    },
  });
}

function filtrarCatalogo(genero) {
  generoActual = genero;
  if (tablaCatalogo) tablaCatalogo.ajax.reload();
}

/* ══════════════════════════════════════════════════════════════════════════════
   DASHBOARD 3 — Top canciones
══════════════════════════════════════════════════════════════════════════════ */
async function cargarTop() {
  try {
    const res  = await fetch('index.php?modulo=visualizaciones&accion=api_top');
    const data = await res.json();

    const labels   = data.map(t =>
      t.track_name.length > 45 ? t.track_name.slice(0, 45) + '…' : t.track_name);
    const values   = data.map(t => parseInt(t.max_pop));
    const artistas = data.map(t => t.artist_name);
    const colors   = WARM_PALETTE(data.length);

    document.getElementById('wrap-top').innerHTML = '';
    document.getElementById('chart-top-wrap').style.display = 'block';

    new Chart(document.getElementById('canvas-top').getContext('2d'), {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Popularidad máxima',
          data:   values,
          backgroundColor: colors,
          borderRadius: 4,
          borderSkipped: false,
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        animation: { duration: 700, easing: 'easeOutQuart' },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#1a1d27',
            borderColor: '#2a2f47', borderWidth: 1,
            callbacks: {
              title: ctx => ctx[0].label,
              label: ctx => [
                ` Popularidad: \${ctx.raw}`,
                ` Artista: \${artistas[ctx.dataIndex]}`,
              ],
            }
          }
        },
        scales: {
          x: {
            max: 100,
            ticks: { color: '#7a8599' },
            grid:  { color: '#1e2238' },
          },
          y: {
            ticks: { color: '#e2e8f0', font: { size: 12 } },
            grid:  { display: false },
          }
        }
      }
    });
  } catch (e) {
    document.getElementById('wrap-top').innerHTML =
      `<div class="spinner-wrap" style="color:#ef4444">&#9888; \${e.message}</div>`;
  }
}

/* ══════════════════════════════════════════════════════════════════════════════
   DASHBOARD 4 — Mapa Leaflet
══════════════════════════════════════════════════════════════════════════════ */
let mapaUsuarios = null;

async function initMapa() {
  mapaUsuarios = L.map('mapa-div', {
    center: [38.45, -0.45], zoom: 9, preferCanvas: true,
  });

  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
    subdomains: 'abcd', maxZoom: 19,
  }).addTo(mapaUsuarios);

  try {
    const res  = await fetch('index.php?modulo=visualizaciones&accion=api_mapa');
    const data = await res.json();

    const conCoords = data.filter(c => c.lat !== null && c.lng !== null);
    const sinCoords = data.filter(c => c.lat === null || c.lng === null);
    const totalMapeado = conCoords.reduce((s, c) => s + parseInt(c.total), 0);
    const maxTotal = Math.max(...conCoords.map(c => parseInt(c.total)));

    // KPIs
    document.getElementById('kpi-mapa-total').textContent =
      totalMapeado.toLocaleString('es-ES');
    document.getElementById('kpi-mapa-ciudades').textContent =
      conCoords.length.toLocaleString('es-ES');
    document.getElementById('kpi-mapa-top').textContent =
      (data[0]?.poblacion ?? '—');
    document.getElementById('kpi-mapa-sin').textContent =
      sinCoords.length;

    // Markers
    conCoords.forEach(city => {
      const tot    = parseInt(city.total);
      const radius = 5 + (tot / maxTotal) * 28;
      const alpha  = 0.45 + (tot / maxTotal) * 0.35;

      L.circleMarker([city.lat, city.lng], {
        radius,
        fillColor:   '#4e7fff',
        color:       '#a0b8ff',
        weight:      1.5,
        opacity:     0.9,
        fillOpacity: alpha,
      })
      .bindPopup(`
        <div style="font-family:'Segoe UI',sans-serif;padding:4px 2px">
          <div style="font-size:15px;font-weight:700;color:#4e7fff;margin-bottom:6px">
            \${city.poblacion}
          </div>
          <div style="color:#aab;font-size:12px">
            Provincia: <b style="color:#e2e8f0">\${city.provincia ?? '—'}</b>
          </div>
          <div style="color:#aab;font-size:12px;margin-top:3px">
            Usuarios: <b style="color:#00c896;font-size:15px">\${tot.toLocaleString('es-ES')}</b>
          </div>
        </div>
      `)
      .addTo(mapaUsuarios);
    });

    // Ajustar bounds al conjunto de markers
    if (conCoords.length > 0) {
      mapaUsuarios.fitBounds(
        conCoords.map(c => [c.lat, c.lng]),
        { padding: [40, 40] }
      );
    }
  } catch (e) {
    console.error('Error cargando mapa:', e);
  }
}

/* ── Inicialización al cargar la página ─────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  // Dashboard 1: carga con la primera ciudad seleccionada
  const selCiudad = document.getElementById('sel-ciudad');
  cargarArtistas(selCiudad ? selCiudad.value : '');

  // Dashboard 2: DataTables
  initCatalogo();

  // Dashboard 3: top canciones (se pre-carga aunque no sea el tab activo)
  cargarTop();

  // Dashboard 4: mapa (se inicializa lazy al primera vez que se activa el tab)
  let mapaIniciado = false;
  document.querySelector('.tab-btn[data-tab="4"]').addEventListener('click', () => {
    if (!mapaIniciado) { initMapa(); mapaIniciado = true; }
  });
});
</script>
</body>
</html>
HTML;
    }
}
