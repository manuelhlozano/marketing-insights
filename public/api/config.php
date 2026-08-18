<?php
// Configuración de Conexión a la Base de Datos dedicada wwcibe_mktinsights
$db_host = 'localhost';
$db_name = 'wwcibe_mktinsights';
$db_user = 'wwcibe_mktinsightsR00t';
$db_pass = 'jnLvx.I^AaMf59L%';

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}
