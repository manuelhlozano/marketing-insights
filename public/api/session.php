<?php
require_once __DIR__ . '/auth.php';
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

$user = mkt_current_user($pdo);
echo json_encode([
    "authenticated" => $user !== null,
    "user" => $user ? ["id" => (int) $user['id'], "nombre" => $user['nombre'], "email" => $user['email']] : null,
]);
