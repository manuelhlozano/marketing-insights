<?php
// Webhook público para recibir leads desde los formularios de landing pages
// del cine (ej. streetoak/upload.php) hacia un concurso de Marketing Insights.

// El servidor tiene uopz.exit=0, que desactiva exit()/die() globalmente
// (ver data.php para el detalle). Este archivo tiene varios exit() antes
// de requerir ningún otro archivo, así que se reactiva aquí primero que nada.
if (function_exists('uopz_allow_exit')) {
    uopz_allow_exit(true);
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit();
}

// Rate limiting por IP (máximo 15 peticiones por minuto)
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateLimitFile = sys_get_temp_dir() . '/mkt_webhook_rate_' . md5($ip);
$now = time();
if (file_exists($rateLimitFile)) {
    $rateData = json_decode(file_get_contents($rateLimitFile), true);
    if ($rateData && ($now - $rateData['time']) < 60) {
        if ($rateData['count'] >= 15) {
            http_response_code(429);
            echo json_encode(["status" => "error", "message" => "Demasiadas peticiones."]);
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

// Anti-bot honeypot
if (!empty($_POST['website_url_hp'])) {
    echo json_encode(["status" => "success"]);
    exit();
}

function sanitize_text($str, $maxLen = 150) {
    if (!$str) return '';
    $clean = trim(strip_tags($str));
    $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $clean);
    return mb_substr($clean, 0, $maxLen, 'UTF-8');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/concursos-metrics.php';

$token = $_POST['webhook_token'] ?? '';
$nombre = sanitize_text($_POST['nombre'] ?? '', 150);
$apellido = sanitize_text($_POST['apellido'] ?? '', 150);
$documento = sanitize_text($_POST['documento'] ?? ($_POST['cedula'] ?? ''), 30);
$telefono = sanitize_text($_POST['telefono'] ?? '', 30);
$correo = filter_var(trim($_POST['correo'] ?? ''), FILTER_VALIDATE_EMAIL) ?: null;

if (!$token || !$nombre || !$documento) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Datos obligatorios incompletos (token, nombre, documento)."]);
    exit();
}

$stmt = $pdo->prepare("SELECT id FROM concursos WHERE webhook_token = ? LIMIT 1");
$stmt->execute([$token]);
$concurso = $stmt->fetch();

if (!$concurso) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Token de concurso inválido."]);
    exit();
}

$ins = $pdo->prepare("INSERT INTO concurso_leads (concurso_id, nombre, apellido, documento, telefono, correo, origen, ip)
                       VALUES (?, ?, ?, ?, ?, ?, 'form_landing', ?)
                       ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido),
                           telefono = VALUES(telefono), correo = VALUES(correo)");
$ins->execute([$concurso['id'], $nombre, $apellido, $documento, $telefono, $correo, $ip]);
mkt_sync_concurso_metricas($pdo, (int) $concurso['id']);

echo json_encode(["status" => "success", "message" => "Registro recibido correctamente."]);
