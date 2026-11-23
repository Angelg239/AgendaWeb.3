CREATE DATABASE IF NOT EXISTS agenda CHARACTER SET utf8mb4;
USE agenda;

CREATE TABLE IF NOT EXISTS categorias (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO categorias (id, nombre) VALUES
  (1, 'Trabajo'),
  (2, 'Personal'),
  (3, 'Estudio'),
  (4, 'Ocio / Deporte')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

CREATE TABLE IF NOT EXISTS eventos (
  id          INT AUTO_INCREMENT PRIMARY KEY,   -- número único de cada evento
  usuario_id  INT NOT NULL,                     -- relación con el usuario (obligatorio)
  titulo      VARCHAR(120) NOT NULL,            -- obligatorio
  fecha       DATE         NOT NULL,            -- obligatorio
  hora        TIME         NULL,                -- opcional (reto extra)
  categoria_id INT NOT NULL,
  descripcion VARCHAR(500) NULL,                -- opcional (reto extra)
  creado_en   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_eventos_categorias FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);
