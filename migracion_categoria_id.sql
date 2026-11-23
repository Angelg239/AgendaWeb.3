-- Respaldar la base de datos antes de ejecutar. Esta migración se ejecuta una sola vez.
USE agenda;

CREATE TABLE IF NOT EXISTS categorias (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO categorias (nombre) VALUES
  ('Trabajo'),
  ('Personal'),
  ('Estudio'),
  ('Ocio / Deporte')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

ALTER TABLE eventos ADD COLUMN categoria_id INT NULL;

INSERT INTO categorias (nombre)
SELECT DISTINCT
  CASE LOWER(TRIM(categoria))
    WHEN 'trabajo' THEN 'Trabajo'
    WHEN 'personal' THEN 'Personal'
    WHEN 'estudio' THEN 'Estudio'
    WHEN 'deporte' THEN 'Ocio / Deporte'
    WHEN 'ocio' THEN 'Ocio / Deporte'
    WHEN 'ocio / deporte' THEN 'Ocio / Deporte'
    ELSE TRIM(categoria)
  END
FROM eventos
WHERE TRIM(categoria) <> ''
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

UPDATE eventos e
JOIN categorias c
  ON c.nombre = CASE LOWER(TRIM(e.categoria))
    WHEN 'trabajo' THEN 'Trabajo'
    WHEN 'personal' THEN 'Personal'
    WHEN 'estudio' THEN 'Estudio'
    WHEN 'deporte' THEN 'Ocio / Deporte'
    WHEN 'ocio' THEN 'Ocio / Deporte'
    WHEN 'ocio / deporte' THEN 'Ocio / Deporte'
    ELSE TRIM(e.categoria)
  END
SET e.categoria_id = c.id;

ALTER TABLE eventos
  MODIFY categoria_id INT NOT NULL,
  ADD CONSTRAINT fk_eventos_categorias
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
  DROP COLUMN categoria;
