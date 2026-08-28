<?php
require_once __DIR__ . '/config.php';
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

// reCAPTCHA (si está activo desde Ajustes del admin)
$recaptchaRow = $pdo->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ('recaptcha_enabled', 'recaptcha_secret_key')");
$recaptchaRow->execute();
$recaptchaSettings = [];
foreach ($recaptchaRow->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $recaptchaSettings[$row['setting_key']] = $row['setting_value'];
}
if (($recaptchaSettings['recaptcha_enabled'] ?? '0') === '1' && !empty($recaptchaSettings['recaptcha_secret_key'])) {
    $recaptchaToken = $_POST['g-recaptcha-response'] ?? '';
    if (!$recaptchaToken) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Completa el reCAPTCHA."]);
        exit();
    }
    $verify = @file_get_contents('https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
        'secret' => $recaptchaSettings['recaptcha_secret_key'],
        'response' => $recaptchaToken,
        'remoteip' => $ip,
    ]));
    $verifyData = $verify ? json_decode($verify, true) : null;
    if (empty($verifyData['success'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Verificación reCAPTCHA fallida. Intenta de nuevo."]);
        exit();
    }
}

$emailInput = trim($_POST['user'] ?? '');
$pass = (string) ($_POST['pass'] ?? '');

$stmt = $pdo->prepare("SELECT id, nombre, email, password_hash FROM admin_users WHERE email = ? AND activo = 1 LIMIT 1");
$stmt->execute([$emailInput]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account || !password_verify($pass, $account['password_hash'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Usuario o contraseña incorrectos."]);
    exit();
}

// Rehash transparente si el costo de bcrypt cambió (buena práctica estándar).
if (password_needs_rehash($account['password_hash'], PASSWORD_DEFAULT)) {
    $upd = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
    $upd->execute([password_hash($pass, PASSWORD_DEFAULT), $account['id']]);
}

session_regenerate_id(true);
$_SESSION['mkt_admin_user_id'] = (int) $account['id'];

echo json_encode(["status" => "success", "user" => ["id" => (int) $account['id'], "nombre" => $account['nombre'], "email" => $account['email']]]);
