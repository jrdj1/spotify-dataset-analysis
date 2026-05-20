<?php
/**
 * MantenimientoView.php
 * ─────────────────────────────────────────────────────────────────────────────
 * CAPA VISTA — Genera el HTML del panel de monitorización.
 * No accede a la BD. Solo consume el array $datos preparado por el Controlador.
 *
 * Diseño: tema oscuro tipo "dashboard de operaciones", con tarjetas KPI,
 * barra de progreso de disco, tabla de tablas y sección de rendimiento.
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

class MantenimientoView
{
    /**
     * Punto de entrada: recibe el array de datos y emite el HTML completo.
     *
     * @param array<string, mixed> $datos
     */
    public function renderizar(array $datos): void
    {
        // Variables extraídas para que la plantilla sea más limpia
        extract($datos, EXTR_PREFIX_ALL, 'v');
        /*
         * Disponibles como: $v_titulo, $v_flash, $v_errores,
         * $v_espacioServidor, $v_espacioBD, $v_espacioPorTabla,
         * $v_rendimiento, $v_queryCompleja, $v_tiempoReferencia,
         * $v_candidatosBasura, $v_timestamp
         */
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($v_titulo) ?></title>
    <style>
        /* ── Reset y variables ─────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:          #0f1117;
            --surface:     #1a1d27;
            --surface2:    #22263a;
            --border:      #2e3354;
            --accent:      #4e7fff;
            --accent2:     #00c896;
            --warn:        #f5a623;
            --danger:      #e74c3c;
            --text:        #e2e8f0;
            --text-muted:  #7a8599;
            --code-bg:     #12141e;
            --radius:      10px;
            --shadow:      0 4px 20px rgba(0,0,0,.45);
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', system-ui, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* ── Layout ────────────────────────────────────────────────────────── */
        .container { max-width: 1280px; margin: 0 auto; padding: 0 24px 48px; }

        header {
            background: linear-gradient(135deg, #1a1d27 0%, #0f1117 100%);
            border-bottom: 1px solid var(--border);
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
            backdrop-filter: blur(8px);
        }
        header h1 { font-size: 1.15rem; font-weight: 600; color: var(--text); }
        header h1 span { color: var(--accent); }
        .badge-live {
            background: var(--accent2); color: #000;
            font-size: .7rem; font-weight: 700;
            padding: 3px 8px; border-radius: 20px; letter-spacing: .05em;
        }
        .timestamp { color: var(--text-muted); font-size: .8rem; }

        /* ── Secciones ─────────────────────────────────────────────────────── */
        section { margin-top: 32px; }
        .section-title {
            font-size: .75rem; font-weight: 700; letter-spacing: .12em;
            text-transform: uppercase; color: var(--text-muted);
            border-left: 3px solid var(--accent);
            padding-left: 10px; margin-bottom: 16px;
        }

        /* ── Tarjetas KPI ──────────────────────────────────────────────────── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            transition: transform .15s;
        }
        .kpi-card:hover { transform: translateY(-2px); }
        .kpi-label { font-size: .72rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 6px; }
        .kpi-value { font-size: 1.9rem; font-weight: 700; line-height: 1; }
        .kpi-unit  { font-size: .85rem; color: var(--text-muted); margin-left: 2px; }
        .kpi-sub   { font-size: .78rem; color: var(--text-muted); margin-top: 6px; }

        .kpi-ok     { color: var(--accent2); }
        .kpi-warn   { color: var(--warn); }
        .kpi-danger { color: var(--danger); }
        .kpi-info   { color: var(--accent); }

        /* ── Barra de progreso ─────────────────────────────────────────────── */
        .progress-wrap { margin-top: 16px; }
        .progress-bar-bg {
            background: var(--surface2);
            border-radius: 20px; height: 10px;
            overflow: hidden; margin-top: 6px;
        }
        .progress-bar-fill {
            height: 100%; border-radius: 20px;
            transition: width .4s ease;
        }
        .progress-label { display: flex; justify-content: space-between; font-size: .75rem; color: var(--text-muted); margin-top: 4px; }

        /* ── Tabla ─────────────────────────────────────────────────────────── */
        .tabla-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        table { width: 100%; border-collapse: collapse; }
        thead { background: var(--surface2); }
        thead th { padding: 12px 16px; text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; color: var(--text-muted); }
        tbody tr { border-top: 1px solid var(--border); transition: background .1s; }
        tbody tr:hover { background: var(--surface2); }
        tbody td { padding: 11px 16px; }
        .td-name { font-weight: 600; color: var(--accent); font-family: monospace; font-size: .9rem; }
        .td-num  { font-family: 'Courier New', monospace; text-align: right; }
        .td-bar  { width: 100px; }

        /* ── Bloque de código (query) ───────────────────────────────────────── */
        .code-block {
            background: var(--code-bg);
            border: 1px solid var(--border);
            border-left: 3px solid var(--accent);
            border-radius: var(--radius);
            padding: 18px 20px;
            font-family: 'Courier New', Courier, monospace;
            font-size: .82rem;
            line-height: 1.7;
            overflow-x: auto;
            white-space: pre;
            color: #a8d8ea;
        }

        /* ── Rendimiento ───────────────────────────────────────────────────── */
        .rendimiento-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
        }
        @media (max-width: 680px) { .rendimiento-grid { grid-template-columns: 1fr; } }

        .semaforo {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 14px; border-radius: 20px;
            font-weight: 700; font-size: .85rem;
        }
        .semaforo.OK      { background: rgba(0,200,150,.15); color: var(--accent2); }
        .semaforo.LENTO   { background: rgba(245,166,35,.15); color: var(--warn); }
        .semaforo.CRITICO { background: rgba(231,76,60,.15); color: var(--danger); }
        .semaforo::before { content: '●'; font-size: 1rem; }

        /* ── Tarjeta grande de rendimiento ─────────────────────────────────── */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
        }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .card-title  { font-weight: 600; color: var(--text); }

        /* ── Flash messages ─────────────────────────────────────────────────── */
        .flash {
            border-radius: var(--radius);
            padding: 14px 18px;
            margin-top: 24px;
            font-size: .9rem;
            border: 1px solid transparent;
        }
        .flash.exito  { background: rgba(0,200,150,.1); border-color: var(--accent2); color: var(--accent2); }
        .flash.error  { background: rgba(231,76,60,.1); border-color: var(--danger);  color: var(--danger); }
        .flash.info   { background: rgba(78,127,255,.1); border-color: var(--accent);  color: var(--accent); }

        /* ── Alertas de error ───────────────────────────────────────────────── */
        .errores-lista {
            background: rgba(231,76,60,.08);
            border: 1px solid var(--danger);
            border-radius: var(--radius);
            padding: 14px 18px;
            margin-top: 16px;
        }
        .errores-lista li { list-style: none; padding: 3px 0; color: var(--danger); font-size: .85rem; }
        .errores-lista li::before { content: '⚠ '; }

        /* ── Recolector de basura ───────────────────────────────────────────── */
        .gc-card {
            background: var(--surface);
            border: 1px solid var(--warn);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
        }
        .gc-title { color: var(--warn); font-weight: 700; font-size: 1rem; margin-bottom: 8px; }
        .gc-desc  { color: var(--text-muted); font-size: .88rem; margin-bottom: 18px; line-height: 1.7; }
        .gc-meta  { display: flex; gap: 24px; margin-bottom: 20px; }
        .gc-stat  { text-align: center; }
        .gc-stat-val  { font-size: 1.5rem; font-weight: 700; color: var(--warn); }
        .gc-stat-label { font-size: .72rem; color: var(--text-muted); text-transform: uppercase; }

        /* ── Botones ────────────────────────────────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 22px; border-radius: 8px;
            font-size: .88rem; font-weight: 600;
            border: none; cursor: pointer; transition: all .15s;
            text-decoration: none;
        }
        .btn-primary   { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: #3a6fef; }
        .btn-danger    { background: var(--danger); color: #fff; }
        .btn-danger:hover  { background: #c0392b; }
        .btn-warn      { background: var(--warn); color: #000; }
        .btn-warn:hover    { filter: brightness(1.1); }
        .btn-outline   { background: transparent; border: 1px solid var(--border); color: var(--text-muted); }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent); }

        .form-inline { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .input-num {
            background: var(--surface2); border: 1px solid var(--border);
            border-radius: 8px; color: var(--text);
            padding: 8px 12px; width: 110px; font-size: .88rem;
        }
        label.input-label { color: var(--text-muted); font-size: .82rem; }

        /* ── Nota informativa ───────────────────────────────────────────────── */
        .nota {
            font-size: .78rem; color: var(--text-muted);
            background: var(--surface2); border-radius: 6px;
            padding: 8px 12px; margin-top: 10px;
            border-left: 3px solid var(--border);
        }

        /* ── Responsive ─────────────────────────────────────────────────────── */
        @media (max-width: 900px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 520px) {
            .kpi-grid { grid-template-columns: 1fr; }
            header { flex-direction: column; gap: 8px; align-items: flex-start; }
        }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════════════════════════════════════
     CABECERA
     ══════════════════════════════════════════════════════════════════════════ -->
<header>
    <h1>🗄️ Panel de Estado · <span>spotify_cm</span></h1>
    <div style="display:flex; align-items:center; gap:12px;">
        <span class="badge-live">● EN VIVO</span>
        <a href="index.php?modulo=visualizaciones"
           class="btn btn-outline"
           style="padding:6px 14px; font-size:.78rem; color:var(--accent2); border-color:var(--accent2);">
           &#128202; Cuadros de Mando
        </a>
        <a href="index.php" class="btn btn-outline"
           style="padding:6px 14px; font-size:.78rem;">&#127968; Inicio</a>
        <a href="index.php?modulo=panel" class="btn btn-outline" style="padding:6px 14px; font-size:.78rem;">↻ Actualizar</a>
    </div>
</header>

<div class="container">

    <!-- Timestamp -->
    <p class="timestamp" style="margin-top:14px;">Última actualización: <?= htmlspecialchars($v_timestamp) ?> &nbsp;|&nbsp; Servidor: <?= htmlspecialchars(gethostname() ?: 'desconocido') ?></p>

    <!-- Flash message (tras recolector de basura) -->
    <?php if ($v_flash): ?>
        <div class="flash <?= htmlspecialchars($v_flash['tipo']) ?>">
            <?= htmlspecialchars($v_flash['mensaje']) ?>
        </div>
    <?php endif; ?>

    <!-- Errores no fatales -->
    <?php if (!empty($v_errores)): ?>
        <ul class="errores-lista" style="margin-top:16px;">
            <?php foreach ($v_errores as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════════════
         SECCIÓN 1 · KPIs DE ALMACENAMIENTO
         ══════════════════════════════════════════════════════════════════════ -->
    <section>
        <h2 class="section-title">KPIs de Almacenamiento</h2>

        <div class="kpi-grid">

            <?php if ($v_espacioServidor): ?>
                <?php
                    $pct = $v_espacioServidor['porcentaje_uso'];
                    $colorPct = $pct > 85 ? 'kpi-danger' : ($pct > 65 ? 'kpi-warn' : 'kpi-ok');
                    $colorBar = $pct > 85 ? '#e74c3c' : ($pct > 65 ? '#f5a623' : '#00c896');
                ?>

                <!-- Espacio libre -->
                <div class="kpi-card">
                    <div class="kpi-label">Espacio libre en disco</div>
                    <div class="kpi-value kpi-ok">
                        <?= number_format($v_espacioServidor['libre_gb'], 1) ?>
                        <span class="kpi-unit">GB</span>
                    </div>
                    <div class="kpi-sub">
                        de <?= number_format($v_espacioServidor['total_gb'], 1) ?> GB totales
                    </div>
                    <div class="progress-wrap">
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill"
                                 style="width:<?= min($pct, 100) ?>%; background:<?= $colorBar ?>;"></div>
                        </div>
                        <div class="progress-label">
                            <span>Usado: <?= $v_espacioServidor['usado_gb'] ?> GB</span>
                            <span class="<?= $colorPct ?>"><?= $pct ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Espacio usado (servidor) -->
                <div class="kpi-card">
                    <div class="kpi-label">Espacio usado en disco</div>
                    <div class="kpi-value <?= $colorPct ?>">
                        <?= number_format($v_espacioServidor['usado_gb'], 1) ?>
                        <span class="kpi-unit">GB</span>
                    </div>
                    <div class="kpi-sub">
                        <?= number_format($v_espacioServidor['usado_bytes'] / 1073741824, 3) ?> GB
                        / <?= number_format($v_espacioServidor['total_bytes'] / 1073741824, 1) ?> GB
                    </div>
                </div>

            <?php else: ?>
                <div class="kpi-card"><div class="kpi-label">Espacio servidor</div><div class="kpi-value kpi-danger">N/D</div></div>
            <?php endif; ?>

            <!-- Espacio total BD -->
            <?php if ($v_espacioBD): ?>
                <div class="kpi-card">
                    <div class="kpi-label">Espacio total BD</div>
                    <div class="kpi-value kpi-info">
                        <?= number_format((float)$v_espacioBD['mb'], 1) ?>
                        <span class="kpi-unit">MB</span>
                    </div>
                    <div class="kpi-sub">
                        <?= number_format((float)$v_espacioBD['gb'], 4) ?> GB &nbsp;·&nbsp; schema <code>spotify_cm</code>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Número de tablas -->
            <div class="kpi-card">
                <div class="kpi-label">Tablas en el schema</div>
                <div class="kpi-value kpi-info">
                    <?= count($v_espacioPorTabla) ?>
                    <span class="kpi-unit">tablas</span>
                </div>
                <div class="kpi-sub">datos + índices monitorizados</div>
            </div>

        </div><!-- /kpi-grid -->

        <!-- Tabla desglose por tabla -->
        <?php if (!empty($v_espacioPorTabla)): ?>
            <div class="tabla-wrap" style="margin-top:20px;">
                <table>
                    <thead>
                        <tr>
                            <th>Tabla</th>
                            <th style="text-align:right">Filas (aprox.)</th>
                            <th style="text-align:right">Datos (MB)</th>
                            <th style="text-align:right">Índices (MB)</th>
                            <th style="text-align:right">Total (MB)</th>
                            <th>Tamaño relativo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $maxMb = max(array_column($v_espacioPorTabla, 'total_mb'));
                        ?>
                        <?php foreach ($v_espacioPorTabla as $t): ?>
                            <?php $pctTabla = $maxMb > 0 ? ($t['total_mb'] / $maxMb) * 100 : 0; ?>
                            <tr>
                                <td class="td-name"><?= htmlspecialchars($t['tabla']) ?></td>
                                <td class="td-num"><?= number_format((int)$t['filas']) ?></td>
                                <td class="td-num"><?= number_format((float)$t['datos_mb'], 3) ?></td>
                                <td class="td-num"><?= number_format((float)$t['indices_mb'], 3) ?></td>
                                <td class="td-num" style="font-weight:600;"><?= number_format((float)$t['total_mb'], 3) ?></td>
                                <td class="td-bar">
                                    <div class="progress-bar-bg">
                                        <div class="progress-bar-fill"
                                             style="width:<?= round($pctTabla) ?>%; background: var(--accent);"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════════
         SECCIÓN 2 · KPI DE RENDIMIENTO
         ══════════════════════════════════════════════════════════════════════ -->
    <section>
        <h2 class="section-title">KPI de Rendimiento — Query Compleja (3 INNER JOINs)</h2>

        <!-- Query ejecutada -->
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">
                <span class="card-title">Query sometida a la prueba</span>
                <span style="font-size:.75rem; color:var(--text-muted);">INNER JOIN favoritas × usuarios × canciones</span>
            </div>
            <div class="code-block"><?= htmlspecialchars(trim($v_queryCompleja)) ?></div>
            <p class="nota">
                Protocolo: <code>SET profiling = 1</code> → <code>RESET QUERY CACHE</code> →
                <code>FLUSH TABLES</code> → ejecución → tiempo leído de <code>SHOW PROFILES</code>.
            </p>
        </div>

        <?php if ($v_rendimiento): ?>

            <?php
                $est = $v_rendimiento['estado'];
                $tMs = $v_rendimiento['tiempo_ms'];
                $refMs = $v_tiempoReferencia * 1000;
                $diffMs = $v_rendimiento['diferencia_ms'];
                $signo = $diffMs >= 0 ? '+' : '';
            ?>

            <div class="rendimiento-grid">

                <!-- Tiempo medido -->
                <div class="kpi-card" style="padding:24px;">
                    <div class="kpi-label">Tiempo medido (SHOW PROFILES)</div>
                    <div class="kpi-value <?= $est === 'OK' ? 'kpi-ok' : ($est === 'LENTO' ? 'kpi-warn' : 'kpi-danger') ?>" style="font-size:2.4rem;">
                        <?= number_format($tMs, 3) ?>
                        <span class="kpi-unit">ms</span>
                    </div>
                    <div class="kpi-sub" style="margin-top:10px;">
                        <?= number_format($v_rendimiento['tiempo_s'], 6) ?> s
                        &nbsp;·&nbsp;
                        <?= number_format($v_rendimiento['filas_devueltas']) ?> filas devueltas
                    </div>
                    <div style="margin-top:14px;">
                        <span class="semaforo <?= $est ?>"><?= $est ?></span>
                    </div>
                </div>

                <!-- Comparativa con referencia -->
                <div class="kpi-card" style="padding:24px;">
                    <div class="kpi-label">Valor de referencia (base)</div>
                    <div class="kpi-value kpi-info" style="font-size:2.4rem;">
                        <?= number_format($refMs, 1) ?>
                        <span class="kpi-unit">ms</span>
                    </div>
                    <div class="kpi-sub" style="margin-top:10px;">
                        Medido con buffer pool caliente (innodb_buffer_pool_size = 6G)
                    </div>
                    <div style="margin-top:14px; font-size:.88rem;">
                        Diferencia respecto a referencia:
                        <strong class="<?= $diffMs > 0 ? 'kpi-warn' : 'kpi-ok' ?>">
                            <?= $signo . number_format($diffMs, 3) ?> ms
                        </strong>
                    </div>
                    <?php if ($v_rendimiento['aviso_reset_cache']): ?>
                        <p class="nota" style="margin-top:10px;">
                            ℹ️ <code>RESET QUERY CACHE</code> no disponible en MySQL 8.0 (Query Cache eliminada).
                            Se usa <code>FLUSH TABLES</code> como sustituto.
                        </p>
                    <?php endif; ?>
                </div>

            </div><!-- /rendimiento-grid -->

        <?php else: ?>
            <div class="flash error">No se pudo ejecutar la prueba de rendimiento.</div>
        <?php endif; ?>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════════
         SECCIÓN 3 · RECOLECTOR DE BASURA
         ══════════════════════════════════════════════════════════════════════ -->
    <section>
        <h2 class="section-title">Plan de Eliminación de Basura</h2>

        <div class="gc-card">
            <div class="gc-title">🗑️ Lanzar Recolector de Basura</div>
            <p class="gc-desc">
                Transfiere registros obsoletos de <code>favoritas</code> hacia
                <code>favoritas_historico</code> (motor ARCHIVE, compresión ~10:1)
                y los elimina de la tabla principal.<br>
                <strong>Criterio de obsolescencia:</strong>
                canciones con <code>year &lt; 2010</code> y <code>popularity &lt; 30</code>
                (baja popularidad histórica).
            </p>

            <div class="gc-meta">
                <div class="gc-stat">
                    <div class="gc-stat-val"><?= number_format($v_candidatosBasura) ?></div>
                    <div class="gc-stat-label">Registros candidatos</div>
                </div>
                <div class="gc-stat">
                    <div class="gc-stat-val" style="color:var(--text-muted);">ARCHIVE</div>
                    <div class="gc-stat-label">Motor destino</div>
                </div>
            </div>

            <form method="POST" action="index.php?modulo=panel&amp;accion=recolector"
                  onsubmit="return confirm('¿Ejecutar el recolector de basura? Se archivarán hasta el límite indicado de registros y se eliminarán de favoritas.');">
                <div class="form-inline">
                    <label class="input-label" for="limite">Límite de filas:</label>
                    <input type="number" id="limite" name="limite"
                           class="input-num" value="5000" min="1" max="50000" step="100">
                    <button type="submit" class="btn btn-warn">
                        🗑️ Lanzar Recolector
                    </button>
                </div>
            </form>

            <p class="nota" style="margin-top:14px;">
                ⚠ Esta operación es <strong>irreversible</strong> en la tabla principal.
                Los datos se conservan en <code>favoritas_historico</code>.
                Se recomienda hacer una copia de seguridad antes de ejecutar en producción.
            </p>
        </div>
    </section>

</div><!-- /container -->

<footer style="text-align:center; padding:24px; color:var(--text-muted); font-size:.75rem; border-top:1px solid var(--border); margin-top:32px;">
    Panel de Mantenimiento · <code>spotify_cm</code> · Gestión de la Información · Universidad de Alicante
</footer>

</body>
</html>
        <?php
    }
}
