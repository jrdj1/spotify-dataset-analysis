-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema spotify_cm
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema spotify_cm
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `spotify_cm` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci ;
USE `spotify_cm` ;

-- -----------------------------------------------------
-- Table `spotify_cm`.`canciones`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `spotify_cm`.`canciones` (
  `track_id` VARCHAR(50) NOT NULL,
  `artist_name` VARCHAR(255) NOT NULL,
  `track_name` VARCHAR(255) NOT NULL,
  `popularity` INT NULL DEFAULT '0',
  `year` INT NULL DEFAULT NULL,
  `genre` VARCHAR(100) NOT NULL,
  `danceability` DECIMAL(10,4) NULL DEFAULT NULL,
  `energy` DECIMAL(10,4) NULL DEFAULT NULL,
  `key` INT NULL DEFAULT NULL,
  `loudness` DECIMAL(10,4) NULL DEFAULT NULL,
  `mode` TINYINT NULL DEFAULT NULL,
  `speechiness` DECIMAL(10,4) NULL DEFAULT NULL,
  `acousticness` DECIMAL(10,4) NULL DEFAULT NULL,
  `instrumentalness` DECIMAL(10,4) NULL DEFAULT NULL,
  `liveness` DECIMAL(10,4) NULL DEFAULT NULL,
  `valence` DECIMAL(10,4) NULL DEFAULT NULL,
  `tempo` DECIMAL(10,4) NULL DEFAULT NULL,
  `duration_ms` BIGINT NULL DEFAULT NULL,
  `time_signature` INT NULL DEFAULT NULL,
  PRIMARY KEY (`track_id`),
  INDEX `idx_canciones_artist` (`artist_name` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_spanish_ci;


-- -----------------------------------------------------
-- Table `spotify_cm`.`direcciones`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `spotify_cm`.`direcciones` (
  `id_direccion` INT NOT NULL,
  `direccion` VARCHAR(150) NOT NULL,
  `numero` VARCHAR(20) NULL DEFAULT NULL,
  `poblacion` VARCHAR(100) NULL DEFAULT NULL,
  `cpostal` VARCHAR(10) NULL DEFAULT NULL,
  `provincia` VARCHAR(50) NULL DEFAULT NULL,
  PRIMARY KEY (`id_direccion`),
  INDEX `idx_dir_poblacion` (`poblacion` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_spanish_ci;


-- -----------------------------------------------------
-- Table `spotify_cm`.`usuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `spotify_cm`.`usuarios` (
  `dni` VARCHAR(15) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(150) NOT NULL,
  `id_direccion` INT NOT NULL,
  `fecha_nacimiento` DATE NOT NULL,
  `poblacion` VARCHAR(100) NULL DEFAULT NULL,
  `provincia` VARCHAR(50) NULL DEFAULT NULL,
  PRIMARY KEY (`dni`),
  INDEX `fk_usuarios_direcciones` (`id_direccion` ASC) VISIBLE,
  INDEX `idx_usr_poblacion` (`poblacion` ASC, `dni` ASC) VISIBLE,
  INDEX `idx_usr_provincia` (`provincia` ASC, `dni` ASC) VISIBLE,
  CONSTRAINT `fk_usuarios_direcciones`
    FOREIGN KEY (`id_direccion`)
    REFERENCES `spotify_cm`.`direcciones` (`id_direccion`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_spanish_ci;


-- -----------------------------------------------------
-- Table `spotify_cm`.`favoritas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `spotify_cm`.`favoritas` (
  `dni` VARCHAR(15) NOT NULL,
  `track_id` VARCHAR(50) NOT NULL,
  `poblacion` VARCHAR(100) NULL DEFAULT NULL,
  `artist_name` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`dni`, `track_id`),
  INDEX `idx_fav_dni` (`dni` ASC) VISIBLE,
  INDEX `idx_fav_track` (`track_id` ASC) VISIBLE,
  CONSTRAINT `fk_favoritas_canciones`
    FOREIGN KEY (`track_id`)
    REFERENCES `spotify_cm`.`canciones` (`track_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_favoritas_usuarios`
    FOREIGN KEY (`dni`)
    REFERENCES `spotify_cm`.`usuarios` (`dni`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_spanish_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
