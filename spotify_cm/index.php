<?php
/**
 * index.php — Front Controller unificado
 * ─────────────────────────────────────────────────────────────────────────────
 * Único punto de entrada de la aplicación spotify_cm.
 *
 * Routing por ?modulo=:
 *   (vacío)          → Hub / página de inicio
 *   panel            → Panel de Estado y Mantenimiento
 *   visualizaciones  → Cuadros de Mando interactivos
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

session_start();

require_once __DIR__ . '/Conexion.php';

$modulo = preg_replace('/[^a-z_]/', '', strtolower($_GET['modulo'] ?? 'home'));

try {
    match ($modulo) {

        'panel' => (static function (): void {
            require_once __DIR__ . '/panel/MantenimientoModel.php';
            require_once __DIR__ . '/panel/MantenimientoView.php';
            require_once __DIR__ . '/panel/MantenimientoController.php';
            (new MantenimientoController())->manejarPeticion();
        })(),

        'visualizaciones' => (static function (): void {
            require_once __DIR__ . '/visualizaciones/VisualizacionesModel.php';
            require_once __DIR__ . '/visualizaciones/VisualizacionesView.php';
            require_once __DIR__ . '/visualizaciones/VisualizacionesController.php';
            (new VisualizacionesController())->manejarPeticion();
        })(),

        default => renderHub(),
    };

} catch (PDOException $e) {
    http_response_code(503);
    echo '<div style="font-family:monospace;background:#1a1d27;color:#e74c3c;'
       . 'padding:40px;border-radius:10px;max-width:600px;margin:60px auto;">'
       . '<h2>&#9888; Error de conexión a la base de datos</h2>'
       . '<p style="color:#7a8599;margin-top:12px;">' . htmlspecialchars($e->getMessage()) . '</p></div>';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<div style="font-family:monospace;background:#1a1d27;color:#e74c3c;'
       . 'padding:40px;border-radius:10px;max-width:600px;margin:60px auto;">'
       . '<h2>Error interno</h2>'
       . '<p style="color:#7a8599;margin-top:12px;">' . htmlspecialchars($e->getMessage()) . '</p></div>';
}

// ── Hub: página de inicio ─────────────────────────────────────────────────────
function renderHub(): void
{
    // KPIs globales para la portada
    try {
        $db   = Conexion::obtenerConexion();
        $stmt = $db->query(
            "SELECT
                (SELECT COUNT(DISTINCT dni)      FROM usuarios)  AS usuarios,
                (SELECT COUNT(DISTINCT track_id) FROM canciones) AS canciones,
                (SELECT COUNT(*)                 FROM favoritas) AS favoritas,
                (SELECT COUNT(DISTINCT genre)    FROM canciones) AS generos"
        );
        $kpis = $stmt->fetch();
    } catch (Throwable) {
        $kpis = ['usuarios' => '—', 'canciones' => '—', 'favoritas' => '—', 'generos' => '—'];
    }

    $usuarios  = is_numeric($kpis['usuarios'])  ? number_format((int)$kpis['usuarios'],  0, ',', '.') : '—';
    $canciones = is_numeric($kpis['canciones']) ? number_format((int)$kpis['canciones'], 0, ',', '.') : '—';
    $favoritas = is_numeric($kpis['favoritas']) ? number_format((int)$kpis['favoritas'], 0, ',', '.') : '—';
    $generos   = $kpis['generos'] ?? '—';
    $ts        = date('d/m/Y H:i:s');

    echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>spotify_cm — Panel de Control</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #0f1117; --surface: #1a1d27; --surface2: #22263a;
      --border: #2a2f47; --accent: #4e7fff; --accent2: #00c896;
      --accent3: #f59e0b; --text: #e2e8f0; --muted: #7a8599; --radius: 12px;
    }
    body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg);
           color: var(--text); min-height: 100vh; display: flex; flex-direction: column; }

    /* Header */
    header { background: var(--surface); border-bottom: 1px solid var(--border);
             padding: 20px 40px; display: flex; align-items: center; justify-content: space-between; }
    .logo { font-size: 22px; font-weight: 700; color: var(--accent); display: flex; align-items: center; gap: 10px; }
    .logo-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--accent2);
                animation: pulse 2s ease-in-out infinite; }
    @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.3)} }
    .header-ts { font-size: 12px; color: var(--muted); }

    /* Main */
    main { flex: 1; max-width: 1100px; margin: 0 auto; padding: 60px 40px; width: 100%; }
    .hero { text-align: center; margin-bottom: 56px; }
    .hero h1 { font-size: 42px; font-weight: 800; margin-bottom: 14px;
               background: linear-gradient(135deg, var(--accent), var(--accent2));
               -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .hero p { font-size: 16px; color: var(--muted); max-width: 520px; margin: 0 auto; line-height: 1.6; }

    /* KPI strip */
    .kpi-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 56px; }
    .kpi { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
           padding: 20px 24px; text-align: center; }
    .kpi-val { font-size: 30px; font-weight: 700; color: var(--accent2); margin-bottom: 4px; }
    .kpi-lbl { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .6px; }

    /* Module cards */
    .cards { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .card {
      background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
      padding: 36px; text-decoration: none; color: var(--text);
      transition: transform .25s, border-color .25s, box-shadow .25s;
      display: flex; flex-direction: column; gap: 14px;
    }
    .card:hover { transform: translateY(-4px); border-color: var(--accent);
                  box-shadow: 0 12px 40px rgba(78,127,255,.18); }
    .card-icon { font-size: 44px; }
    .card-title { font-size: 22px; font-weight: 700; }
    .card-desc { font-size: 14px; color: var(--muted); line-height: 1.6; }
    .card-features { list-style: none; display: flex; flex-direction: column; gap: 7px; margin-top: 4px; }
    .card-features li { font-size: 13px; color: var(--muted); display: flex; align-items: center; gap: 8px; }
    .card-features li::before { content: ''; width: 6px; height: 6px; border-radius: 50%;
                                 background: var(--accent); flex-shrink: 0; }
    .card-btn { margin-top: auto; padding: 11px 0; border-radius: 8px; font-size: 14px;
                font-weight: 600; text-align: center; background: var(--accent); color: #fff; }
    .card:nth-child(2) .card-btn { background: var(--accent2); }
    .card:nth-child(2):hover { border-color: var(--accent2);
                                box-shadow: 0 12px 40px rgba(0,200,150,.15); }

    /* Footer */
    footer { text-align: center; padding: 24px; color: var(--muted); font-size: 12px;
             border-top: 1px solid var(--border); }

    @media (max-width: 768px) {
      .cards, .kpi-strip { grid-template-columns: 1fr; }
      .hero h1 { font-size: 28px; }
      main { padding: 32px 20px; }
    }
  </style>
</head>
<body>
<header>
  <div class="logo">
    <div class="logo-dot"></div>
    spotify_cm
  </div>
  <span class="header-ts">&#128337; {$ts}</span>
</header>

<main>
  <div class="hero">
    <h1>Panel de Control</h1>
    <p>Sistema de gestión y análisis de la base de datos <code>spotify_cm</code> · Gestión de la Información · Universidad de Alicante</p>
  </div>

  <div class="kpi-strip">
    <div class="kpi"><div class="kpi-val">{$usuarios}</div><div class="kpi-lbl">Usuarios</div></div>
    <div class="kpi"><div class="kpi-val">{$canciones}</div><div class="kpi-lbl">Canciones</div></div>
    <div class="kpi"><div class="kpi-val">{$favoritas}</div><div class="kpi-lbl">Favoritas</div></div>
    <div class="kpi"><div class="kpi-val">{$generos}</div><div class="kpi-lbl">Géneros</div></div>
  </div>

  <div class="cards">
    <a class="card" href="index.php?modulo=panel">
      <span class="card-icon">&#9881;&#65039;</span>
      <div class="card-title">Panel de Mantenimiento</div>
      <div class="card-desc">Monitorización del servidor y la base de datos en tiempo real.</div>
      <ul class="card-features">
        <li>KPIs de espacio en disco y esquema</li>
        <li>Prueba de rendimiento con profiling MySQL</li>
        <li>Recolector de registros obsoletos (ARCHIVE)</li>
      </ul>
      <div class="card-btn">Abrir panel →</div>
    </a>

    <a class="card" href="index.php?modulo=visualizaciones">
      <span class="card-icon">&#128202;</span>
      <div class="card-title">Cuadros de Mando</div>
      <div class="card-desc">Visualizaciones interactivas sobre usuarios, canciones y favoritas.</div>
      <ul class="card-features">
        <li>Artistas favoritos filtrables por ciudad</li>
        <li>Catálogo con búsqueda y filtrado en tiempo real</li>
        <li>Top canciones · Mapa de usuarios (Leaflet)</li>
      </ul>
      <div class="card-btn">Ver dashboards →</div>
    </a>
  </div>
</main>

<footer>spotify_cm &mdash; Gestión de la Información &mdash; Universidad de Alicante</footer>
</body>
</html>
HTML;
}
