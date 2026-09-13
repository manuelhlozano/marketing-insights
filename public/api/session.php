<?php
require_once __DIR__ . '/auth.php';
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

$user = mkt_current_user($pdo);
// Se devuelven rol, empresas y módulos para que el panel muestre solo lo que
// corresponde. Es únicamente presentación: cada API valida por su cuenta, así
// que manipular esta respuesta en el navegador no da acceso a nada.
echo json_encode([
    "authenticated" => $user !== null,
    "user" => $user ? [
        "id" => (int) $user['id'],
        "nombre" => $user['nombre'],
        "email" => $user['email'],
        "rol" => $user['rol'],
        "empresas" => mkt_empresas_permitidas($pdo),
        "modulos" => mkt_modulos_permitidos($pdo),
    ] : null,
]);
