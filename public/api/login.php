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

// Si el bloqueo sigue vigente y cuánto le falta lo decide MySQL, no PHP: el
// servidor de base de datos y el de PHP no están en el mismo huso horario, así
// que comparar aquí una fecha escrita allá daba el bloqueo por vencido nada
// más ponerlo, y la cuenta nunca quedaba bloqueada de verdad.
$stmt = $pdo->prepare("SELECT id, nombre, email, password_hash, intentos_fallidos,
                              (bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW()) AS esta_bloqueado,
                              TIMESTAMPDIFF(MINUTE, NOW(), bloqueado_hasta) AS minutos_restantes,
                              bloqueado_hasta
                       FROM admin_users WHERE email = ? AND activo = 1 LIMIT 1");
$stmt->execute([$emailInput]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// Bloqueo por cuenta, además del límite por IP. El límite por IP solo frena a
// quien ataca desde una dirección; probar una misma contraseña contra una
// cuenta desde muchas direcciones lo esquivaba por completo. Aquí el contador
// viaja con la cuenta, no con el atacante.
const MKT_INTENTOS_MAX = 5;
const MKT_BLOQUEO_MINUTOS = 15;

if ($account && (int) $account['esta_bloqueado'] === 1) {
    $restan = max(1, (int) $account['minutos_restantes']);
    http_response_code(429);
    echo json_encode(["status" => "error",
                      "message" => "Cuenta bloqueada temporalmente por intentos fallidos. Vuelve a intentar en {$restan} minuto(s) o restablece tu contraseña."]);
    exit();
}

if (!$account || !password_verify($pass, $account['password_hash'])) {
    if ($account) {
        $fallidos = ((int) $account['intentos_fallidos']) + 1;
        if ($fallidos >= MKT_INTENTOS_MAX) {
            $pdo->prepare("UPDATE admin_users SET intentos_fallidos = 0,
                           bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?")
                ->execute([MKT_BLOQUEO_MINUTOS, $account['id']]);
            http_response_code(429);
            echo json_encode(["status" => "error",
                              "message" => "Demasiados intentos fallidos. La cuenta queda bloqueada " . MKT_BLOQUEO_MINUTOS . " minutos."]);
            exit();
        }
        $pdo->prepare("UPDATE admin_users SET intentos_fallidos = ? WHERE id = ?")
            ->execute([$fallidos, $account['id']]);
    }
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Usuario o contraseña incorrectos."]);
    exit();
}

// Entrada correcta: el contador vuelve a cero para que los fallos sueltos de
// un día normal no acaben bloqueando a nadie.
if ((int) $account['intentos_fallidos'] !== 0 || $account['bloqueado_hasta'] !== null) {
    $pdo->prepare("UPDATE admin_users SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?")
        ->execute([$account['id']]);
}

// Rehash transparente si el costo de bcrypt cambió (buena práctica estándar).
if (password_needs_rehash($account['password_hash'], PASSWORD_DEFAULT)) {
    $upd = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
    $upd->execute([password_hash($pass, PASSWORD_DEFAULT), $account['id']]);
}

session_regenerate_id(true);
$_SESSION['mkt_admin_user_id'] = (int) $account['id'];

echo json_encode(["status" => "success", "user" => ["id" => (int) $account['id'], "nombre" => $account['nombre'], "email" => $account['email']]]);
