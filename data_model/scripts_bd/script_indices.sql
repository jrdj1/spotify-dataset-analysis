-- ============================================================
-- FASE 2 — Estrategia de índices: spotify_cm
-- Fecha: 2026-05-17
-- Idempotente: usa INFORMATION_SCHEMA para no recrear índices existentes
-- ============================================================
USE spotify_cm;

-- ─────────────────────────────────────────────────────────────────────────────
-- Procedimiento auxiliar: crea un índice solo si no existe
-- Usa INFORMATION_SCHEMA (compatible con cualquier MySQL 8.x)
-- ─────────────────────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS crear_indice_si_no_existe;

DELIMITER //
CREATE PROCEDURE crear_indice_si_no_existe(
    IN p_tabla  VARCHAR(64),
    IN p_indice VARCHAR(64),
    IN p_ddl    TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name   = p_tabla
          AND index_name   = p_indice
        LIMIT 1
    ) THEN
        SET @_sql = p_ddl;
        PREPARE _stmt FROM @_sql;
        EXECUTE _stmt;
        DEALLOCATE PREPARE _stmt;
    END IF;
END//
DELIMITER ;

-- ─────────────────────────────────────────────────────────────────────────────
-- BLOQUE A: COVERING INDEX en favoritas
-- Justifica la desnormalización de la Fase 1
-- Resuelve el JOIN complejo en una sola tabla sin tocar usuarios ni canciones
-- Orden: poblacion primero (filtro WHERE), artist_name segundo (GROUP BY)
-- ─────────────────────────────────────────────────────────────────────────────
CALL crear_indice_si_no_existe(
    'favoritas',
    'idx_fav_pob_artist',
    'CREATE INDEX idx_fav_pob_artist ON favoritas(poblacion, artist_name)'
);

-- ─────────────────────────────────────────────────────────────────────────────
-- BLOQUE B: FULLTEXT en canciones
-- Sustituye LIKE ''%texto%'' (no usa B-Tree) por MATCH() AGAINST()
-- ─────────────────────────────────────────────────────────────────────────────
CALL crear_indice_si_no_existe(
    'canciones',
    'ft_canciones_artist',
    'CREATE FULLTEXT INDEX ft_canciones_artist ON canciones(artist_name)'
);

CALL crear_indice_si_no_existe(
    'canciones',
    'ft_canciones_track',
    'CREATE FULLTEXT INDEX ft_canciones_track ON canciones(track_name)'
);

-- ─────────────────────────────────────────────────────────────────────────────
-- BLOQUE C: Índice para rankings de popularidad
-- ORDER BY popularity DESC LIMIT N sin filesort
-- MySQL 8+ soporta índices descendentes nativamente
-- ─────────────────────────────────────────────────────────────────────────────
CALL crear_indice_si_no_existe(
    'canciones',
    'idx_canciones_popularity',
    'CREATE INDEX idx_canciones_popularity ON canciones(popularity DESC)'
);

-- ─────────────────────────────────────────────────────────────────────────────
-- Limpieza del procedimiento auxiliar
-- ─────────────────────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS crear_indice_si_no_existe;

-- ─────────────────────────────────────────────────────────────────────────────
-- VERIFICACIÓN FINAL
-- ─────────────────────────────────────────────────────────────────────────────
SHOW INDEX FROM canciones;
SHOW INDEX FROM direcciones;
SHOW INDEX FROM favoritas;
SHOW INDEX FROM usuarios;
