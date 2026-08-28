<?php
require_once __DIR__ . '/auth.php';

header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit();
}

// Rate limiting por IP (máximo 8 intentos por 5 minutos)
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateLimitFile = sys_get_temp_dir() . '/mkt_admin_login_' . md5($ip);
$now = time();
if (file_exists($rateLimitFile)) {
    $rateData = json_decode(file_get_contents($rateLimitFile), true);
    if ($rateData && ($now - $rateData['time']) < 300) {
        if ($rateData['count'] >= 8) {
            http_response_code(429);
            echo json_encode(["status" => "error", "message" => "Demasiados intentos. Intenta nuevamente en unos minutos."]);
            exit();
        }
        $rateData['count']++;
        file_put_contents($rateLimitFile, json_encode($rateData));
    } else {
        file_put_contents($rateLimitFile, json_encode(['time' => $now, 'count' => 1]));
    }
} else {
    file_put_contents($rateLimitFile, json_encode(['time' => $now, 'count' => 1]));
}

$user = trim($_POST['user'] ?? '');
$pass = (string) ($_POST['pass'] ?? '');

$valid = false;
foreach (MKT_ADMIN_ACCOUNTS as $accUser => $accPass) {
    if (hash_equals($accUser, $user) && hash_equals($accPass, $pass)) {
        $valid = true;
        break;
    }
}

if (!$valid) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Usuario o contraseña incorrectos."]);
    exit();
}

session_regenerate_id(true);
$_SESSION['mkt_admin_user'] = $user;

echo json_encode(["status" => "success", "user" => $user]);
