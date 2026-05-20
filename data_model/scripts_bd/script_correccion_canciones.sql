USE spotify_cm;

-- ─────────────────────────────────────────────────────────────────────────────
-- Fila 1: track_name = '\\\'  (track_id cargado erróneamente como '52')
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE canciones
SET track_id         = '6eKSIGhYDpuaqRssFGclTs',
    track_name       = '\\\\\\',
    popularity       = 52,
    year             = 2021,
    genre            = 'chill',
    danceability     = 0.6760,
    energy           = 0.4490,
    `key`            = 1,
    loudness         = -12.2240,
    `mode`           = 0,
    speechiness      = 0.0764,
    acousticness     = 0.0012,
    instrumentalness = 0.9170,
    liveness         = 0.1630,
    valence          = 0.5110,
    tempo            = 159.9760,
    duration_ms      = 93723,
    time_signature   = 4
WHERE track_id = '52';

-- ─────────────────────────────────────────────────────────────────────────────
-- Fila 2: track_name = 'Easy Filter Part\'  (track_id cargado erróneamente como '2')
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE canciones
SET track_id         = '4I9ln0pwa76P2YBvxsMvkd',
    track_name       = 'Easy Filter Part',
    popularity       = 2,
    year             = 2003,
    genre            = 'techno',
    danceability     = 0.7960,
    energy           = 1.0000,
    `key`            = 10,
    loudness         = -5.5850,
    `mode`           = 0,
    speechiness      = 0.0736,
    acousticness     = 0.0761,
    instrumentalness = 0.8240,
    liveness         = 0.1050,
    valence          = 0.8120,
    tempo            = 136.7480,
    duration_ms      = 194800,
    time_signature   = 4
WHERE track_id = '2';

-- ─────────────────────────────────────────────────────────────────────────────
-- Verificación
-- ─────────────────────────────────────────────────────────────────────────────
SELECT track_id, artist_name, track_name, popularity, year, genre
FROM canciones
WHERE track_id IN ('6eKSIGhYDpuaqRssFGclTs', '4I9ln0pwa76P2YBvxsMvkd', '52', '2');