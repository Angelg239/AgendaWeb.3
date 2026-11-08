<?php
require __DIR__ . '/conexion.php';

CREATE TABLE IF NOT EXISTS usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(80)  NOT NULL,
  correo     VARCHAR(120) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  creado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

if ($mysqli->query($sql) === TRUE) {
    echo "¡Tabla 'usuarios' creada con éxito!";
} else {
    echo "Error al crear la tabla: " . $mysqli->error;
}
?>