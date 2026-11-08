<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

// Datos del usuario actual en sesión
$usuarioId     = (int) $_SESSION['uid'];
$usuarioNombre = $_SESSION['nombre'] ?? '';
?>