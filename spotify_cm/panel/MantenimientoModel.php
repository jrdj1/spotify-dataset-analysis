<?php
/**
 * MantenimientoModel.php
 * ─────────────────────────────────────────────────────────────────────────────
 * CAPA MODELO — Toda la lógica de acceso a datos reside aquí.
 * El Controlador llama a estos métodos; la Vista nunca toca la BD.
 *
 * Responsabilidades:
 *  1. KPIs de almacenamiento (INFORMATION_SCHEMA + funciones PHP de disco).
 *  2. KPI de rendimiento   (profiling MySQL + query compleja con 3 JOINs).
 *  3. Gestión del recolector de basura (tabla ARCHIVE + DELETE controlado).
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../Conexion.php';

class MantenimientoModel
{
    private PDO $pdo;

    /** Nombre del schema que se monitoriza */
    private const SCHEMA = 'spotify_cm';

    /**
     * Tiempo de referencia (segundos) para el KPI de rendimiento.
     * Medido en condiciones óptimas con buffer pool caliente (6 GB).
     * Sirve de línea base para detectar degradaciones.
     */
    public const TIEMPO_REFERENCIA = 0.005;

    /**
     * Query compleja sometida a la prueba de rendimiento.
     * Une las 3 tablas más grandes del modelo (favoritas ~2 M filas,
     * canciones ~1.16 M, usuarios 300 K) con INNER JOIN.
     * LIMIT 200 para que la prueba sea reproducible sin saturar la red.
     */
    public const QUERY_COMPLEJA = <<<SQL
        SELECT
            u.nombre,
            u.apellidos,
            u.poblacion,
            c.artist_name,
            c.track_name,
            c.genre,
            c.popularity
        FROM   favoritas   f
        INNER JOIN usuarios   u ON f.dni      = u.dni
        INNER JOIN canciones  c ON f.track_id = c.track_id
        WHERE  c.popularity > 80
        ORDER  BY c.popularity DESC
        LIMIT  200
        SQL;

    public function __construct()
    {
        $this->pdo = Conexion::obtenerConexion();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 1 · KPIs DE ALMACENAMIENTO
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Información de disco del servidor donde corre PHP/MySQL.
     * disk_free_space() y disk_total_space() devuelven bytes.
     *
     * @return array{libre_bytes: float, total_bytes: float,
     *               libre_gb: float, total_gb: float, porcentaje_uso: float}
     */
    public function getEspacioServidor(): array
    {
        $ruta = '/';                          // raíz del sistema de ficheros
        $libreBytes = disk_free_space($ruta);
        $totalBytes = disk_total_space($ruta);
        $usadoBytes = $totalBytes - $libreBytes;

        return [
            'libre_bytes'    => $libreBytes,
            'total_bytes'    => $totalBytes,
            'usado_bytes'    => $usadoBytes,
            'libre_gb'       => round($libreBytes / 1073741824, 2),   // ÷ 1024³
            'total_gb'       => round($totalBytes / 1073741824, 2),
            'usado_gb'       => round($usadoBytes / 1073741824, 2),
            'porcentaje_uso' => $totalBytes > 0
                ? round(($usadoBytes / $totalBytes) * 100, 1)
                : 0,
        ];
    }

    /**
     * Espacio total (datos + índices) consumido por el schema spotify_cm.
     * Consulta al catálogo INFORMATION_SCHEMA sin tocar las tablas de usuario.
     *
     * @return array{mb: float, gb: float}
     */
    public function getEspacioTotalBD(): array
    {
        $sql = <<<SQL
            SELECT
                ROUND(SUM(data_length + index_length) / 1048576, 2)  AS mb,
                ROUND(SUM(data_length + index_length) / 1073741824, 4) AS gb
            FROM   information_schema.TABLES
            WHERE  table_schema = :schema
            SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':schema' => self::SCHEMA]);
        return $stmt->fetch() ?: ['mb' => 0, 'gb' => 0];
    }

    /**
     * Desglose de espacio por tabla individual.
     * Retorna cada tabla con sus datos, índices y total, ordenados de mayor a menor.
     *
     * @return array<int, array{tabla: string, filas: int,
     *                          datos_mb: float, indices_mb: float, total_mb: float}>
     */
    public function getEspacioPorTabla(): array
    {
        $sql = <<<SQL
            SELECT
                table_name                                                        AS tabla,
                table_rows                                                        AS filas,
                ROUND(data_length  / 1048576, 3)                                  AS datos_mb,
                ROUND(index_length / 1048576, 3)                                  AS indices_mb,
                ROUND((data_length + index_length) / 1048576, 3)                  AS total_mb
            FROM   information_schema.TABLES
            WHERE  table_schema = :schema
            ORDER  BY (data_length + index_length) DESC
            SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':schema' => self::SCHEMA]);
        return $stmt->fetchAll();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 2 · KPI DE RENDIMIENTO — PROFILING MYSQL
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Ejecuta la query compleja bajo condiciones controladas y devuelve
     * el tiempo real de ejecución obtenido de SHOW PROFILES.
     *
     * Protocolo de medición:
     *  [1] SET profiling = 1            → habilita la captura de perfiles.
     *  [2] RESET QUERY CACHE            → invalida la Query Cache
     *                                     (no-op en MySQL 8.0 sin efectos adversos;
     *                                      en MySQL 5.x limpia la caché de resultados).
     *  [3] FLUSH TABLES                 → cierra tablas abiertas y descarta
     *                                     la caché de definiciones.
     *  [4] Ejecutar la query compleja.
     *  [5] SHOW PROFILES                → leer el tiempo real de ejecución
     *                                     del último perfil registrado.
     *
     * @return array{
     *     tiempo_s: float,
     *     tiempo_ms: float,
     *     filas_devueltas: int,
     *     referencia_s: float,
     *     diferencia_ms: float,
     *     estado: string,
     *     aviso_reset_cache: bool
     * }
     */
    public function ejecutarPruebaRendimiento(): array
    {
        // [1] Activar profiling para esta sesión MySQL
        $this->pdo->exec('SET profiling = 1');
        $this->pdo->exec('SET profiling_history_size = 5');

        // [2] Intentar RESET QUERY CACHE (eliminado en MySQL 8.0; capturamos el error)
        $avisoResetCache = false;
        try {
            $this->pdo->exec('RESET QUERY CACHE');
        } catch (PDOException) {
            // MySQL 8.0 HeatWave no tiene Query Cache → se omite sin interrumpir
            $avisoResetCache = true;
        }

        // [3] Vaciar la caché de tablas abiertas (sí disponible en MySQL 8.0)
        $this->pdo->exec('FLUSH TABLES');

        // [4] Ejecutar la query compleja con sentencia preparada
        $stmt = $this->pdo->prepare(self::QUERY_COMPLEJA);
        $stmt->execute();
        $filas = $stmt->fetchAll();   // consumir todo el resultado set

        // [5] Leer el último perfil generado por SHOW PROFILES
        $perfiles = $this->pdo->query('SHOW PROFILES')->fetchAll();
        $ultimoPerfil = !empty($perfiles) ? end($perfiles) : null;

        $tiempoS = $ultimoPerfil
            ? (float) $ultimoPerfil['Duration']
            : 0.0;                   // fallback si profiling no está soportado

        $diferenciaMs = round(($tiempoS - self::TIEMPO_REFERENCIA) * 1000, 3);

        return [
            'tiempo_s'          => round($tiempoS, 6),
            'tiempo_ms'         => round($tiempoS * 1000, 3),
            'filas_devueltas'   => count($filas),
            'referencia_s'      => self::TIEMPO_REFERENCIA,
            'diferencia_ms'     => $diferenciaMs,
            // Estado semáforo: OK / LENTO / CRÍTICO
            'estado'            => $this->clasificarRendimiento($tiempoS),
            'aviso_reset_cache' => $avisoResetCache,
        ];
    }

    /**
     * Clasifica el tiempo medido respecto al valor de referencia.
     */
    private function clasificarRendimiento(float $tiempoS): string
    {
        $ref = self::TIEMPO_REFERENCIA;
        if ($tiempoS <= $ref * 3)   return 'OK';       // hasta 3× la referencia
        if ($tiempoS <= $ref * 10)  return 'LENTO';    // hasta 10× la referencia
        return 'CRITICO';
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 3 · RECOLECTOR DE BASURA
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Crea la tabla de histórico con motor ARCHIVE si todavía no existe.
     * ARCHIVE comprime los registros en disco (ratio ~10:1) y es ideal
     * para datos de solo-lectura de larga retención.
     * Nota: ARCHIVE no admite DELETE, UPDATE ni índices no-PK.
     */
    public function crearTablaHistorico(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS favoritas_historico (
                id             BIGINT       NOT NULL AUTO_INCREMENT,
                dni            VARCHAR(15)  NOT NULL,
                track_id       VARCHAR(50)  NOT NULL,
                poblacion      VARCHAR(100) NULL,
                artist_name    VARCHAR(255) NULL,
                archivado_en   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE = ARCHIVE
              DEFAULT CHARSET = utf8mb4
              COLLATE = utf8mb4_spanish_ci
              COMMENT = 'Histórico comprimido de favoritas archivadas (recolector de basura)'
            SQL;

        $this->pdo->exec($sql);
    }

    /**
     * Transfiere registros "obsoletos" de `favoritas` → `favoritas_historico`
     * y los elimina de la tabla principal.
     *
     * Criterio de obsolescencia: canciones con año < 2010 y popularidad < 30.
     * Se procesan en lotes de $limite filas para no bloquear la tabla.
     *
     * @param  int $limite Máximo de filas a archivar en esta ejecución.
     * @return array{archivadas: int, eliminadas: int, error: string|null}
     */
    public function lanzarRecolectorBasura(int $limite = 5000): array
    {
        // Asegurar que la tabla de destino existe antes de insertar
        $this->crearTablaHistorico();

        try {
            // ── PASO 1: Copiar registros obsoletos al histórico ─────────────
            $sqlInsertar = <<<SQL
                INSERT INTO favoritas_historico (dni, track_id, poblacion, artist_name)
                SELECT f.dni, f.track_id, f.poblacion, f.artist_name
                FROM   favoritas f
                INNER JOIN canciones c ON f.track_id = c.track_id
                WHERE  c.year       < 2010
                  AND  c.popularity < 30
                LIMIT  :limite
                SQL;

            $stmtIns = $this->pdo->prepare($sqlInsertar);
            $stmtIns->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmtIns->execute();
            $archivadas = $stmtIns->rowCount();

            // ── PASO 2: Eliminar de la tabla principal los mismos registros ─
            // Se usa la misma condición; LIMIT garantiza que no borramos más
            // de lo que acabamos de insertar.
            $sqlBorrar = <<<SQL
                DELETE f
                FROM   favoritas f
                INNER JOIN canciones c ON f.track_id = c.track_id
                WHERE  c.year       < 2010
                  AND  c.popularity < 30
                LIMIT  :limite
                SQL;

            $stmtDel = $this->pdo->prepare($sqlBorrar);
            $stmtDel->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmtDel->execute();
            $eliminadas = $stmtDel->rowCount();

            return [
                'archivadas' => $archivadas,
                'eliminadas' => $eliminadas,
                'error'      => null,
            ];

        } catch (PDOException $e) {
            // Devolvemos el error a la Vista sin exponer el stack trace completo
            return [
                'archivadas' => 0,
                'eliminadas' => 0,
                'error'      => $e->getMessage(),
            ];
        }
    }

    /**
     * Número de registros candidatos a archivado (para mostrar en Vista
     * antes de que el usuario ejecute el recolector).
     */
    public function contarRegistrosCandidatos(): int
    {
        $sql = <<<SQL
            SELECT COUNT(*) AS total
            FROM   favoritas f
            INNER JOIN canciones c ON f.track_id = c.track_id
            WHERE  c.year       < 2010
              AND  c.popularity < 30
            SQL;

        $stmt = $this->pdo->query($sql);
        return (int) ($stmt->fetch()['total'] ?? 0);
    }
}
