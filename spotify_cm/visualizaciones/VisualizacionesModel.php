<?php
/**
 * VisualizacionesModel.php
 * ─────────────────────────────────────────────────────────────────────────────
 * CAPA MODELO — Todas las consultas SQL de los 4 cuadros de mando.
 *
 * Dashboard 1 · Artistas más favoritos (filtrable por ciudad)
 * Dashboard 2 · Catálogo de canciones  (server-side DataTables)
 * Dashboard 3 · Top canciones por popularidad (horizontal bars)
 * Dashboard 4 · Mapa de usuarios por ciudad (Leaflet + coordenadas)
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../Conexion.php';

class VisualizacionesModel
{
    private PDO $db;

    // ── Lookup: ciudad → [lat, lng] ───────────────────────────────────────────
    private const COORDENADAS = [
        // Provincia de Alicante
        'Alcoy'                   => [38.6959, -0.4747],
        'Alcoi'                   => [38.6959, -0.4747],
        'Alfaz del Pi'            => [38.5893, -0.1025],
        "l'Alfàs del Pi"          => [38.5893, -0.1025],
        'Alicante'                => [38.3453, -0.4831],
        'Alacant'                 => [38.3453, -0.4831],
        'Altea'                   => [38.5990, -0.0497],
        'Benidorm'                => [38.5373, -0.1318],
        'Calpe'                   => [38.6444,  0.0426],
        'Calp'                    => [38.6444,  0.0426],
        'Crevillente'             => [38.2470, -0.8127],
        'Denia'                   => [38.8417,  0.1062],
        'Dénia'                   => [38.8417,  0.1062],
        'Elche'                   => [38.2699, -0.7126],
        'Elx'                     => [38.2699, -0.7126],
        'Elda'                    => [38.4791, -0.7945],
        'Javea'                   => [38.7899,  0.1670],
        'Jávea'                   => [38.7899,  0.1670],
        'Xàbia'                   => [38.7899,  0.1670],
        'La Nucia'                => [38.6194, -0.1101],
        'la Nucia'                => [38.6194, -0.1101],
        'Moraira'                 => [38.6884,  0.1248],
        'Novelda'                 => [38.3861, -0.7680],
        'Orihuela'                => [38.0851, -0.9440],
        'Petrer'                  => [38.4702, -0.7697],
        'Polop'                   => [38.6294, -0.1307],
        'San Vicente del Raspeig' => [38.3967, -0.5228],
        'Sant Vicent del Raspeig' => [38.3967, -0.5228],
        'Teulada'                 => [38.7237,  0.1063],
        'Torrevieja'              => [37.9784, -0.6817],
        'Villajoyosa'             => [38.5014, -0.2351],
        'La Vila Joiosa'          => [38.5014, -0.2351],
        'Guardamar del Segura'    => [38.0897, -0.6601],
        'Santa Pola'              => [38.1900, -0.5562],
        'Pego'                    => [38.8484,  0.1222],
        'Ibi'                     => [38.6281, -0.5714],
        'Mutxamel'                => [38.4014, -0.4432],
        'El Campello'             => [38.4344, -0.3953],
        // Resto de España (fallback)
        'Madrid'                  => [40.4168, -3.7038],
        'Barcelona'               => [41.3851,  2.1734],
        'Valencia'                => [39.4699, -0.3763],
        'Sevilla'                 => [37.3891, -5.9845],
        'Zaragoza'                => [41.6488, -0.8891],
        'Malaga'                  => [36.7213, -4.4214],
        'Málaga'                  => [36.7213, -4.4214],
        'Murcia'                  => [37.9922, -1.1307],
        'Palma'                   => [39.5696,  2.6502],
        'Bilbao'                  => [43.2630, -2.9350],
        'Granada'                 => [37.1773, -3.5986],
        'Córdoba'                 => [37.8882, -4.7794],
        'Valladolid'              => [41.6523, -4.7245],
        'Pamplona'                => [42.8169, -1.6432],
        'San Sebastian'           => [43.3183, -1.9812],
        'San Sebastián'           => [43.3183, -1.9812],
        'Donostia'                => [43.3183, -1.9812],
        'Santander'               => [43.4623, -3.8099],
        'Burgos'                  => [42.3440, -3.6969],
        'Salamanca'               => [40.9701, -5.6635],
        'Toledo'                  => [39.8628, -4.0273],
        'Albacete'                => [38.9942, -1.8585],
        'Almeria'                 => [36.8381, -2.4597],
        'Almería'                 => [36.8381, -2.4597],
        'Huelva'                  => [37.2614, -6.9447],
        'Cadiz'                   => [36.5271, -6.2886],
        'Cádiz'                   => [36.5271, -6.2886],
        'Badajoz'                 => [38.8794, -6.9706],
        'Caceres'                 => [39.4753, -6.3723],
        'Cáceres'                 => [39.4753, -6.3723],
        'Logrono'                 => [42.4650, -2.4456],
        'Logroño'                 => [42.4650, -2.4456],
        'Vigo'                    => [42.2406, -8.7207],
        'A Coruña'                => [43.3713, -8.3960],
        'Oviedo'                  => [43.3614, -5.8490],
        'Gijon'                   => [43.5453, -5.6615],
        'Gijón'                   => [43.5453, -5.6615],
        'Leon'                    => [42.5987, -5.5671],
        'León'                    => [42.5987, -5.5671],
    ];

    public function __construct()
    {
        $this->db = Conexion::obtenerConexion();
    }

    // ── Selectores (para los filtros del frontend) ────────────────────────────

    public function getCiudades(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT poblacion FROM usuarios
             WHERE poblacion IS NOT NULL AND poblacion <> ''
             ORDER BY poblacion"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getGeneros(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT genre FROM canciones
             WHERE genre IS NOT NULL AND genre <> ''
             ORDER BY genre"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // ── KPIs globales (cabecera del panel) ───────────────────────────────────

    public function getKpisGlobales(): array
    {
        $stmt = $this->db->query(
            "SELECT
                (SELECT COUNT(DISTINCT dni)      FROM usuarios)  AS total_usuarios,
                (SELECT COUNT(DISTINCT track_id) FROM canciones) AS total_canciones,
                (SELECT COUNT(*)                 FROM favoritas) AS total_favoritas,
                (SELECT COUNT(DISTINCT genre)    FROM canciones) AS total_generos"
        );
        return $stmt->fetch();
    }

    // ── Dashboard 1: Artistas más favoritos ──────────────────────────────────

    public function getArtistasFavoritos(string $ciudad = ''): array
    {
        // Datos del gráfico
        $stmt = $this->db->prepare(
            "SELECT c.artist_name, COUNT(*) AS veces
             FROM usuarios u
             INNER JOIN favoritas f ON u.dni      = f.dni
             INNER JOIN canciones c ON f.track_id = c.track_id
             WHERE (:ciudad = '' OR u.poblacion = :ciudad2)
             GROUP BY c.artist_name
             ORDER BY veces DESC
             LIMIT 25"
        );
        $stmt->execute([':ciudad' => $ciudad, ':ciudad2' => $ciudad]);
        $artistas = $stmt->fetchAll();

        // KPI: total favoritas y usuarios activos en esa ciudad
        $stmt2 = $this->db->prepare(
            "SELECT COUNT(*) AS total_favs,
                    COUNT(DISTINCT f.dni) AS usuarios_activos
             FROM usuarios u
             INNER JOIN favoritas f ON u.dni = f.dni
             WHERE (:ciudad = '' OR u.poblacion = :ciudad2)"
        );
        $stmt2->execute([':ciudad' => $ciudad, ':ciudad2' => $ciudad]);
        $kpis = $stmt2->fetch();

        // KPI: género más escuchado en esa ciudad
        $stmt3 = $this->db->prepare(
            "SELECT c.genre, COUNT(*) AS cnt
             FROM usuarios u
             INNER JOIN favoritas f ON u.dni      = f.dni
             INNER JOIN canciones c ON f.track_id = c.track_id
             WHERE (:ciudad = '' OR u.poblacion = :ciudad2)
             GROUP BY c.genre
             ORDER BY cnt DESC
             LIMIT 1"
        );
        $stmt3->execute([':ciudad' => $ciudad, ':ciudad2' => $ciudad]);
        $topGenero = $stmt3->fetch();

        return [
            'artistas'         => $artistas,
            'total_favs'       => (int)($kpis['total_favs']       ?? 0),
            'usuarios_activos' => (int)($kpis['usuarios_activos'] ?? 0),
            'top_genero'       => $topGenero['genre'] ?? '—',
        ];
    }

    // ── Dashboard 2: Catálogo — server-side DataTables ───────────────────────

    public function getCatalogoServerSide(array $p, string $genero = ''): array
    {
        $draw    = (int)($p['draw']              ?? 1);
        $start   = max(0, (int)($p['start']      ?? 0));
        $length  = min(100, (int)($p['length']   ?? 25));
        $search  = trim($p['search']['value']    ?? '');

        $cols     = ['track_name', 'artist_name', 'genre', 'popularity'];
        $colIdx   = (int)($p['order'][0]['column'] ?? 3);
        $orderCol = $cols[$colIdx] ?? 'popularity';
        $orderDir = ($p['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        // Construcción dinámica del WHERE
        $where = [];
        $binds = [];

        if ($genero !== '') {
            $where[]          = 'genre = :genero';
            $binds[':genero'] = $genero;
        }
        if ($search !== '') {
            $where[]      = '(track_name LIKE :s OR artist_name LIKE :s2)';
            $binds[':s']  = "%{$search}%";
            $binds[':s2'] = "%{$search}%";
        }

        $wClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total sin filtro
        $total = (int)$this->db->query("SELECT COUNT(*) FROM canciones")->fetchColumn();

        // Total filtrado
        $filtStmt = $this->db->prepare("SELECT COUNT(*) FROM canciones $wClause");
        $filtStmt->execute($binds);
        $filtered = (int)$filtStmt->fetchColumn();

        // Página de datos
        $dataStmt = $this->db->prepare(
            "SELECT track_name, artist_name, genre, popularity
             FROM canciones
             $wClause
             ORDER BY $orderCol $orderDir
             LIMIT :len OFFSET :off"
        );
        foreach ($binds as $k => $v) {
            $dataStmt->bindValue($k, $v);
        }
        $dataStmt->bindValue(':len', $length, PDO::PARAM_INT);
        $dataStmt->bindValue(':off', $start,  PDO::PARAM_INT);
        $dataStmt->execute();

        return [
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $dataStmt->fetchAll(),
        ];
    }

    // ── Dashboard 3: Top canciones por popularidad ───────────────────────────

    public function getTopCanciones(): array
    {
        $stmt = $this->db->query(
            "SELECT track_name, artist_name, MAX(popularity) AS max_pop
             FROM canciones
             GROUP BY track_name, artist_name
             ORDER BY max_pop DESC
             LIMIT 15"
        );
        return $stmt->fetchAll();
    }

    // ── Dashboard 4: Mapa de usuarios por ciudad ─────────────────────────────

    public function getUsuariosPorCiudad(): array
    {
        $stmt = $this->db->query(
            "SELECT poblacion, provincia, COUNT(DISTINCT dni) AS total
             FROM usuarios
             WHERE poblacion IS NOT NULL AND poblacion <> ''
             GROUP BY poblacion, provincia
             ORDER BY total DESC"
        );
        $rows = $stmt->fetchAll();

        // Enriquecer con coordenadas
        foreach ($rows as &$row) {
            $coords          = self::COORDENADAS[$row['poblacion']] ?? null;
            $row['lat']      = $coords[0] ?? null;
            $row['lng']      = $coords[1] ?? null;
        }
        unset($row);

        return $rows;
    }
}
