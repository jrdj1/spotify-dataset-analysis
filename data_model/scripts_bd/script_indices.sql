-- ============================================================
-- FASE 2 — Estrategia de índices: spotify_cm
-- Fecha: 2026-05-17
-- ============================================================
USE spotify_cm;

-- ------------------------------------------------------------
-- BLOQUE A: COVERING INDEX en favoritas
-- Justifica la desnormalización de la Fase 1
-- Resuelve el JOIN complejo en una sola tabla sin tocar usuarios ni canciones
-- Orden: poblacion primero (filtro WHERE), artist_name segundo (GROUP BY)
-- ------------------------------------------------------------
CREATE INDEX idx_fav_pob_artist
    ON favoritas(poblacion, artist_name);


-- ------------------------------------------------------------
-- BLOQUE B: FULLTEXT en canciones
-- Sustituye LIKE '%texto%' (no usa B-Tree) por MATCH() AGAINST()
-- Útil para búsquedas tipo: buscar artistas con 'Boyce' en el nombre
-- ------------------------------------------------------------
CREATE FULLTEXT INDEX ft_canciones_artist
    ON canciones(artist_name);

CREATE FULLTEXT INDEX ft_canciones_track
    ON canciones(track_name);


-- ------------------------------------------------------------
-- BLOQUE C: Índice para rankings de popularidad
-- ORDER BY popularity DESC LIMIT N sin filesort
-- MySQL 8+ soporta índices descendentes nativamente
-- ------------------------------------------------------------
CREATE INDEX idx_canciones_popularity
    ON canciones(popularity DESC);


-- ------------------------------------------------------------
-- VERIFICACIÓN FINAL
-- ------------------------------------------------------------
SHOW INDEX FROM canciones;
SHOW INDEX FROM direcciones;
SHOW INDEX FROM favoritas;
SHOW INDEX FROM usuarios;