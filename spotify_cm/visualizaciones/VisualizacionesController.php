<?php
/**
 * VisualizacionesController.php
 * ─────────────────────────────────────────────────────────────────────────────
 * CAPA CONTROLADOR — Enruta peticiones HTTP y sirve JSON a los dashboards.
 *
 * Routing por ?accion=:
 *   (vacío)        → panel principal (HTML completo)
 *   api_artistas   → JSON Dashboard 1 (filtrable por ?ciudad=)
 *   api_catalogo   → JSON Dashboard 2 (server-side DataTables)
 *   api_top        → JSON Dashboard 3
 *   api_mapa       → JSON Dashboard 4
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../Conexion.php';
require_once __DIR__ . '/VisualizacionesModel.php';
require_once __DIR__ . '/VisualizacionesView.php';

class VisualizacionesController
{
    private VisualizacionesModel $modelo;
    private VisualizacionesView  $vista;

    public function __construct()
    {
        $this->modelo = new VisualizacionesModel();
        $this->vista  = new VisualizacionesView();
    }

    public function manejarPeticion(): void
    {
        $accion = preg_replace('/[^a-z_]/', '', strtolower(
            $_GET['accion'] ?? 'panel'
        ));

        match ($accion) {
            'api_artistas' => $this->apiArtistas(),
            'api_catalogo' => $this->apiCatalogo(),
            'api_top'      => $this->apiTop(),
            'api_mapa'     => $this->apiMapa(),
            default        => $this->accionPanel(),
        };
    }

    // ── Panel principal ───────────────────────────────────────────────────────

    private function accionPanel(): void
    {
        $datos = [
            'titulo'    => 'Cuadros de Mando — spotify_cm',
            'ciudades'  => $this->modelo->getCiudades(),
            'generos'   => $this->modelo->getGeneros(),
            'kpis'      => $this->modelo->getKpisGlobales(),
            'timestamp' => date('d/m/Y H:i:s'),
        ];
        $this->vista->renderizar($datos);
    }

    // ── APIs JSON ─────────────────────────────────────────────────────────────

    private function apiArtistas(): void
    {
        $this->jsonHeader();
        $ciudad = trim($_GET['ciudad'] ?? '');
        echo json_encode(
            $this->modelo->getArtistasFavoritos($ciudad),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    private function apiCatalogo(): void
    {
        $this->jsonHeader();
        $genero = trim($_GET['genero'] ?? '');
        echo json_encode(
            $this->modelo->getCatalogoServerSide($_GET, $genero),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    private function apiTop(): void
    {
        $this->jsonHeader();
        echo json_encode(
            $this->modelo->getTopCanciones(),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    private function apiMapa(): void
    {
        $this->jsonHeader();
        echo json_encode(
            $this->modelo->getUsuariosPorCiudad(),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function jsonHeader(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
}
