CREATE DATABASE IF NOT EXISTS agenda CHARACTER SET utf8mb4;
USE agenda;

CREATE TABLE IF NOT EXISTS eventos (
  id          INT AUTO_INCREMENT PRIMARY KEY,   -- número único de cada evento
  titulo      VARCHAR(120) NOT NULL,            -- obligatorio
  fecha       DATE         NOT NULL,            -- obligatorio
  hora        TIME         NULL,                -- opcional (reto extra)
  categoria   VARCHAR(20)  NOT NULL,            -- obligatorio
  descripcion VARCHAR(500) NULL,                -- opcional (reto extra)
  creado_en   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO categorias (id, nombre) VALUES
  (1, 'Trabajo'),
  (2, 'Personal'),
  (3, 'Estudio'),
  (4, 'Ocio / Deporte');

SELECT * FROM categorias;