<?php
/* ════════════════════════════════════════════════════════
   borrar.php · AgendaWeb
   ════════════════════════════════════════════════════════ */

// 1. Solo procesar por método POST (abrir borrar.php?id=3 directamente en el navegador no hace nada)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 2. El id SIEMPRE como número entero estricto para evitar inyecciones SQL
$id = (int) ($_POST['id'] ?? 0);

require 'conexion.php';

// 3. Ejecutar DELETE con sentencia preparada WHERE id = ?
$stmt = $mysqli->prepare('DELETE FROM eventos WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$borrados = $stmt->affected_rows; // Valida si realmente se borró la fila (1 o 0)
$stmt->close();
$mysqli->close();

// 4. Redirigir de vuelta a index.php con el parámetro PRG correspondiente
if ($borrados === 1) {
    header('Location: index.php?ok=borrado');
} else {
    header('Location: index.php?error=noexiste');
}
exit;