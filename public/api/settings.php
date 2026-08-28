<?php
// Ajustes globales de la app (por ahora: reCAPTCHA en el login).
// action=public_recaptcha es la única acción sin sesión: el login la necesita
// ANTES de autenticar, y solo expone si está activo + la site key (pública
// por diseño en reCAPTCHA). La secret key nunca sale de aquí salvo al admin
// autenticado en action=get.

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (function_exists('uopz_allow_exit')) { uopz_allow_exit(true); }

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

function jsonOut($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function mkt_get_settings(PDO $pdo, array $keys): array {
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ({$placeholders})");
    $stmt->execute($keys);
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $out[$row['setting_key']] = $row['setting_value'];
    }
    return $out;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {

if ($action === 'public_recaptcha' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $s = mkt_get_settings($pdo, ['recaptcha_enabled', 'recaptcha_site_key']);
    $enabled = ($s['recaptcha_enabled'] ?? '0') === '1' && !empty($s['recaptcha_site_key']);
    jsonOut(["status" => "success", "enabled" => $enabled, "site_key" => $enabled ? $s['recaptcha_site_key'] : null]);
}

// ─────────────────────────────────────────────────────────────────
// A PARTIR DE AQUÍ: requiere sesión de administrador
// ─────────────────────────────────────────────────────────────────
mkt_require_auth();

if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $s = mkt_get_settings($pdo, ['recaptcha_enabled', 'recaptcha_site_key', 'recaptcha_secret_key']);
    jsonOut([
        "status" => "success",
        "recaptcha_enabled" => ($s['recaptcha_enabled'] ?? '0') === '1',
        "recaptcha_site_key" => $s['recaptcha_site_key'] ?? '',
        "recaptcha_secret_key" => $s['recaptcha_secret_key'] ?? '',
    ]);
}

if ($action === 'save_recaptcha' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled = !empty($_POST['recaptcha_enabled']) ? '1' : '0';
    $siteKey = trim($_POST['recaptcha_site_key'] ?? '');
    $secretKey = trim($_POST['recaptcha_secret_key'] ?? '');

    if ($enabled === '1' && (!$siteKey || !$secretKey)) {
        jsonOut(["status" => "error", "message" => "Para activar reCAPTCHA necesitas Site Key y Secret Key."], 400);
    }

    $upsert = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $upsert->execute(['recaptcha_enabled', $enabled]);
    $upsert->execute(['recaptcha_site_key', $siteKey]);
    $upsert->execute(['recaptcha_secret_key', $secretKey]);

    jsonOut(["status" => "success"]);
}

jsonOut(["status" => "error", "message" => "Acción no reconocida."], 400);

} catch (PDOException $e) {
    error_log('[Settings API] DB Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error de base de datos."], 500);
} catch (Throwable $e) {
    error_log('[Settings API] Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error interno del servidor."], 500);
}
