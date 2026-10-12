<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    //                    servidor      usuario                       contraseña              base de datos
    $mysqli = new mysqli('localhost', 'remarkable-environment-coy', 'lb7G31-F+4h-6eXOJq', 'remarkable-environment-coy');
    $mysqli->set_charset('utf8mb4'); 
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    // getMessage() te dice la causa exacta. En un sitio real se quita.
    die('No se pudo conectar a la base de datos: ' . $e->getMessage());
}