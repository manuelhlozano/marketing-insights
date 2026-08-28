<?php
require_once __DIR__ . '/auth.php';
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");
echo json_encode([
    "authenticated" => mkt_is_authenticated(),
    "user" => $_SESSION['mkt_admin_user'] ?? null,
]);
