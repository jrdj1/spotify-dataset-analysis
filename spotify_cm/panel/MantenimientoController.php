<?php
/**
 * MantenimientoController.php
 * ─────────────────────────────────────────────────────────────────────────────
 * CAPA CONTROLADOR — Orquesta la lógica de la aplicación.
 *
 * Responsabilidades:
 *  · Recibir y sanitizar la petición HTTP (GET / POST).
 *  · Decidir qué método del Modelo invocar.
 *  · Preparar el array $datos que se pasa a la Vista.
 *  · Nunca generar HTML directamente (eso es tarea de la Vista).
 *
 * Routing simple basado en el parámetro GET ?accion=<nombre>:
 *  (vacío / "panel") → mostrar el panel de estado completo.
 *  "recolector"      → ejecutar el recolector de basura y redirigir.
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../Conexion.php';
require_once __DIR__ . '/MantenimientoModel.php';
require_once __DIR__ . '/MantenimientoView.php';

class MantenimientoController
{
    private MantenimientoModel $modelo;
    private MantenimientoView  $vista;

    public function __construct()
    {
        $this->modelo = new MantenimientoModel();
        $this->vista  = new MantenimientoView();
    }

    /**
     * Punto de entrada único: lee ?accion= y despacha al método correcto.
     */
    public function manejarPeticion(): void
    {
        // Sanitizamos el parámetro de acción (solo letras/guiones bajos)
        $accion = preg_replace('/[^a-z_]/', '', strtolower(
            $_GET['accion'] ?? 'panel'
        ));

        match ($accion) {
            'recolector' => $this->accionRecolector(),
            default      => $this->accionPanel(),
        };
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ACCIÓN: panel principal
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Recoge todos los KPIs del Modelo y los entrega a la Vista.
     */
    private function accionPanel(): void
    {
        // ── Mensaje flash tras redirección POST (recolector de basura) ───────
        $flash = null;
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }

        // ── Recoger KPIs (cada llamada es independiente y puede fallar sola) ─
        $errores = [];

        try {
            $espacioServidor = $this->modelo->getEspacioServidor();
        } catch (Throwable $e) {
            $espacioServidor = null;
            $errores[] = 'Espacio servidor: ' . $e->getMessage();
        }

        try {
            $espacioBD = $this->modelo->getEspacioTotalBD();
        } catch (Throwable $e) {
            $espacioBD = null;
            $errores[] = 'Espacio BD: ' . $e->getMessage();
        }

        try {
            $espacioPorTabla = $this->modelo->getEspacioPorTabla();
        } catch (Throwable $e) {
            $espacioPorTabla = [];
            $errores[] = 'Espacio por tabla: ' . $e->getMessage();
        }

        try {
            $rendimiento = $this->modelo->ejecutarPruebaRendimiento();
        } catch (Throwable $e) {
            $rendimiento = null;
            $errores[] = 'Prueba de rendimiento: ' . $e->getMessage();
        }

        try {
            $candidatosBasura = $this->modelo->contarRegistrosCandidatos();
        } catch (Throwable $e) {
            $candidatosBasura = 0;
            $errores[] = 'Candidatos basura: ' . $e->getMessage();
        }

        // ── Pasar todo a la Vista ─────────────────────────────────────────────
        $datos = [
            'titulo'           => 'Panel de Estado y Mantenimiento — spotify_cm',
            'flash'            => $flash,
            'errores'          => $errores,
            'espacioServidor'  => $espacioServidor,
            'espacioBD'        => $espacioBD,
            'espacioPorTabla'  => $espacioPorTabla,
            'rendimiento'      => $rendimiento,
            'queryCompleja'    => MantenimientoModel::QUERY_COMPLEJA,
            'tiempoReferencia' => MantenimientoModel::TIEMPO_REFERENCIA,
            'candidatosBasura' => $candidatosBasura,
            'timestamp'        => date('d/m/Y H:i:s'),
        ];

        $this->vista->renderizar($datos);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ACCIÓN: recolector de basura
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Invocado por POST (botón "Lanzar Recolector de Basura").
     * Ejecuta el archivado, guarda un mensaje flash en sesión y redirige
     * al panel principal (patrón Post/Redirect/Get para evitar reenvíos).
     */
    private function accionRecolector(): void
    {
        // Solo aceptar peticiones POST (rechazar acceso directo por URL)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?modulo=panel');
            exit;
        }

        // Límite de filas por ejecución (configurable desde el formulario)
        $limite = isset($_POST['limite'])
            ? max(1, min(50000, (int) $_POST['limite']))
            : 5000;

        $resultado = $this->modelo->lanzarRecolectorBasura($limite);

        // Construir mensaje flash según resultado
        if ($resultado['error'] !== null) {
            $_SESSION['flash'] = [
                'tipo'    => 'error',
                'mensaje' => 'Error en el recolector: ' . $resultado['error'],
            ];
        } elseif ($resultado['archivadas'] === 0) {
            $_SESSION['flash'] = [
                'tipo'    => 'info',
                'mensaje' => 'No se encontraron registros candidatos a archivado con los criterios actuales.',
            ];
        } else {
            $_SESSION['flash'] = [
                'tipo'    => 'exito',
                'mensaje' => sprintf(
                    'Recolector ejecutado correctamente: %d registros archivados en favoritas_historico, %d eliminados de favoritas.',
                    $resultado['archivadas'],
                    $resultado['eliminadas']
                ),
            ];
        }

        // Post/Redirect/Get → evita doble envío al recargar
        header('Location: index.php');
        exit;
    }
}
