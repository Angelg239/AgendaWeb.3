<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli('localhost', 'remarkable-environment-coy', '12345678', 'remarkable-environment-coy');
    $mysqli->set_charset('utf8mb4'); 
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die('No se pudo conectar a la base de datos: ' . $e->getMessage());
}