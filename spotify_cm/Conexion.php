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
 *  - Credenciales cargadas desde spotify_cm/.env (nunca en el repositorio).
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

class Conexion
{
    /** Única instancia PDO compartida en toda la petición */
    private static ?PDO $instancia = null;

    /** Constructor privado → impide instanciación externa */
    private function __construct() {}

    /** Clonación prohibida */
    private function __clone() {}

    /**
     * Lee el fichero .env de la misma carpeta y carga cada KEY=VALUE
     * como variable de entorno del proceso (si no estaba ya definida).
     */
    private static function cargarEnv(): void
    {
        $envFile = __DIR__ . '/.env';
        if (!is_readable($envFile)) {
            return;
        }
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }
            [$clave, $valor] = array_map('trim', explode('=', $linea, 2));
            if (!isset($_ENV[$clave])) {
                $_ENV[$clave] = $valor;
                putenv("$clave=$valor");
            }
        }
    }

    /**
     * Devuelve (o crea) la instancia PDO.
     *
     * @throws PDOException Si la conexión falla.
     */
    public static function obtenerConexion(): PDO
    {
        if (self::$instancia === null) {
            self::cargarEnv();

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: '127.0.0.1',
                getenv('DB_PORT') ?: '3306',
                getenv('DB_NAME') ?: 'spotify_cm'
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

            self::$instancia = new PDO(
                $dsn,
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASS') ?: '',
                $opciones
            );
        }

        return self::$instancia;
    }
}
