-- CREACIÓN DE LA BASE DE DATOS
-- Solo se crea si no existe previamente
CREATE DATABASE IF NOT EXISTS spotify_cm
CHARACTER SET utf8mb4 
COLLATE utf8mb4_spanish_ci;

-- Seleccionamos la base de datos para las siguientes instrucciones
USE spotify_cm;

-- TABLA DE DIRECCIONES
-- Contiene los datos de ubicación de los usuarios
CREATE TABLE IF NOT EXISTS direcciones (
    id_direccion INT NOT NULL,
    direccion VARCHAR(150) NOT NULL,
    numero VARCHAR(20),
    poblacion VARCHAR(100),
    cpostal VARCHAR(10),
    provincia VARCHAR(50),
    PRIMARY KEY (id_direccion)
) ENGINE=InnoDB;

-- TABLA DE USUARIOS
-- Relacionada con la tabla direcciones
CREATE TABLE IF NOT EXISTS usuarios (
    dni VARCHAR(15) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    id_direccion INT NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    PRIMARY KEY (dni),
    CONSTRAINT fk_usuarios_direcciones FOREIGN KEY (id_direccion) 
        REFERENCES direcciones (id_direccion) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- TABLA DE CANCIONES (SPOTIFY DATA)
-- Almacena las métricas analíticas de cada track
CREATE TABLE IF NOT EXISTS canciones (
    track_id VARCHAR(50) NOT NULL,
    artist_name VARCHAR(255) NOT NULL,
    track_name VARCHAR(255) NOT NULL,
    popularity INT DEFAULT 0,
    year INT,
    genre VARCHAR(100) NOT NULL,
    danceability DECIMAL(10,4),
    energy DECIMAL(10,4),
    `key` INT,
    loudness DECIMAL(10,4),
    `mode` TINYINT,
    speechiness DECIMAL(10,4),
    acousticness DECIMAL(10,4),
    instrumentalness DECIMAL(10,4),
    liveness DECIMAL(10,4),
    valence DECIMAL(10,4),
    tempo DECIMAL(10,4),
    duration_ms BIGINT,
    time_signature INT,
    -- Nota: Si el track_id se repite por género, usar: PRIMARY KEY (track_id, genre)
    PRIMARY KEY (track_id)
) ENGINE=InnoDB;

-- TABLA INTERMEDIA: FAVORITAS
-- Conecta usuarios con sus canciones preferidas
CREATE TABLE IF NOT EXISTS favoritas (
    dni VARCHAR(15) NOT NULL,
    track_id VARCHAR(50) NOT NULL,
    PRIMARY KEY (dni, track_id),
    CONSTRAINT fk_favoritas_usuarios FOREIGN KEY (dni) 
        REFERENCES usuarios (dni) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_favoritas_canciones FOREIGN KEY (track_id) 
        REFERENCES canciones (track_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
