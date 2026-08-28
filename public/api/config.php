<?php
// El servidor tiene uopz.exit=0, que desactiva exit()/die() globalmente
// (ver data.php para el detalle). config.php es de los primeros archivos
// que se requieren en casi todos los endpoints, así que se reactiva aquí
// también como defensa adicional.
if (function_exists('uopz_allow_exit')) {
    uopz_allow_exit(true);
}

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
