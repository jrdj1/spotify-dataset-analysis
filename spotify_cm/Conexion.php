<?php
/**
 * Conexion.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Patrón Singleton para la conexión PDO a MySQL.
 * Una única instancia es reutilizada durante toda la petición HTTP,
 * evitando abrir múltiples sockets innecesariamente.
 *
 * Seguridad:
 *  - PDO::ERRMODE_EXCEPTION → cualquier error lanza una PDOException auditable.
 *  - PDO::ATTR_EMULATE_PREPARES = false → las sentencias preparadas son reales
 *    (el servidor MySQL hace el parsing), no emuladas por PDO. Esto elimina
 *    por diseño los vectores de SQL Injection.
 *  - Credenciales en un único lugar; en producción moverlas a .env.
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

class Conexion
{
    // ── Parámetros de conexión ──────────────────────────────────────────────
    private const DB_HOST    = '127.0.0.1';   // localhost vía túnel SSH
    private const DB_PORT    = '3306';
    private const DB_NAME    = 'spotify_cm';
    private const DB_USER    = 'root';
    private const DB_PASS    = 'mycontGI_7_6';
    private const DB_CHARSET = 'utf8mb4';

    /** Única instancia PDO compartida en toda la petición */
    private static ?PDO $instancia = null;

    /** Constructor privado → impide instanciación externa */
    private function __construct() {}

    /** Clonación prohibida */
    private function __clone() {}

    /**
     * Devuelve (o crea) la instancia PDO.
     *
     * @throws PDOException Si la conexión falla.
     */
    public static function obtenerConexion(): PDO
    {
        if (self::$instancia === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                self::DB_HOST,
                self::DB_PORT,
                self::DB_NAME,
                self::DB_CHARSET
            );

            $opciones = [
                // Lanza PDOException en cualquier error (nunca devuelve false silencioso)
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Los resultados llegan como array asociativo por defecto
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Sentencias preparadas REALES en el servidor MySQL (anti-SQLi)
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Fuerza charset y collation en todas las consultas de la sesión
                PDO::MYSQL_ATTR_INIT_COMMAND =>
                    "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_spanish_ci'",
            ];

            self::$instancia = new PDO($dsn, self::DB_USER, self::DB_PASS, $opciones);
        }

        return self::$instancia;
    }
}
