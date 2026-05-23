-- ============================================================
-- CONSULTAS SQL OPTIMIZADAS — spotify_cm
-- Fase 3: Reescritura de sentencias
-- ============================================================
USE spotify_cm;


-- ------------------------------------------------------------
-- C1 · Artista más favoriteado en una población
-- Índice: idx_fav_pob_artist (poblacion, artist_name)
-- Sustituye el JOIN complejo de 4 tablas por 1 sola tabla
-- ------------------------------------------------------------
SELECT artist_name, COUNT(*) AS veces_favorita
FROM favoritas
WHERE poblacion = 'Altea'
GROUP BY artist_name
ORDER BY veces_favorita DESC
LIMIT 1;


-- ------------------------------------------------------------
-- C2 · Búsqueda de texto en nombre de artista
-- Índice: ft_canciones_artist (FULLTEXT)
-- Sustituye LIKE '%texto%' por MATCH/AGAINST
-- ------------------------------------------------------------
SELECT COUNT(DISTINCT artist_name) AS artistas
FROM canciones
WHERE MATCH(artist_name) AGAINST('Boyce' IN BOOLEAN MODE);


-- ------------------------------------------------------------
-- C3 · Número de usuarios en una ciudad
-- Índice: idx_usr_poblacion (poblacion, dni)
-- Elimina el JOIN con direcciones gracias a la desnormalización
-- ------------------------------------------------------------
SELECT COUNT(*) AS usuarios
FROM usuarios
WHERE poblacion = 'Elche';


-- ------------------------------------------------------------
-- C4 · Nombre de usuario más repetido
-- Sin cambios respecto al original
-- ------------------------------------------------------------
SELECT nombre, COUNT(*) AS repeticiones
FROM usuarios
GROUP BY nombre
ORDER BY repeticiones DESC
LIMIT 1;


-- ------------------------------------------------------------
-- C5 · Género más frecuente de un artista
-- Índice: idx_canciones_artist (artist_name)
-- Sin cambios respecto al original
-- ------------------------------------------------------------
SELECT genre, COUNT(*) AS total
FROM canciones
WHERE artist_name = 'AJJ'
GROUP BY genre
ORDER BY total DESC
LIMIT 1;


-- ------------------------------------------------------------
-- C6 · Top N canciones más populares
-- Índice: idx_canciones_popularity (popularity DESC)
-- Evita filesort gracias al índice descendente
-- ------------------------------------------------------------
SELECT track_id, artist_name, track_name, popularity
FROM canciones
ORDER BY popularity DESC
LIMIT 10;


-- ============================================================
-- PROTOCOLO DE VERIFICACIÓN (Fase 4)
-- Ejecutar antes de cada iteración del test de estrés
-- ============================================================
-- FLUSH TABLES;
-- FLUSH STATUS;
