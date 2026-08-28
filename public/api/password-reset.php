<?php
// Recuperación de contraseña por correo. Dos acciones, ambas públicas
// (no requieren sesión, es justo el caso de "olvidé mi contraseña"):
//   - request: recibe un email, genera un token de un solo uso (1h) y envía
//     el enlace por correo. Siempre responde "success" exista o no la
//     cuenta, para no filtrar qué correos están registrados.
//   - confirm: recibe token + nueva contraseña, la aplica si el token es
//     válido y no ha expirado, y lo invalida de inmediato (un solo uso).

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

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {

if ($action === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting por IP (máximo 5 solicitudes por 10 minutos, evita abuso del envío de correo)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $rateLimitFile = sys_get_temp_dir() . '/mkt_pwreset_rate_' . md5($ip);
    $now = time();
    if (file_exists($rateLimitFile)) {
        $rateData = json_decode(file_get_contents($rateLimitFile), true);
        if ($rateData && ($now - $rateData['time']) < 600) {
            if ($rateData['count'] >= 5) {
                jsonOut(["status" => "error", "message" => "Demasiadas solicitudes. Intenta más tarde."], 429);
            }
            $rateData['count']++;
            file_put_contents($rateLimitFile, json_encode($rateData));
        } else {
            file_put_contents($rateLimitFile, json_encode(['time' => $now, 'count' => 1]));
        }
    } else {
        file_put_contents($rateLimitFile, json_encode(['time' => $now, 'count' => 1]));
    }

    $email = trim($_POST['email'] ?? '');
    if ($email) {
        $stmt = $pdo->prepare("SELECT id, nombre, email FROM admin_users WHERE email = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $expires = date('Y-m-d H:i:s', time() + 3600);

            $upd = $pdo->prepare("UPDATE admin_users SET reset_token_hash = ?, reset_token_expires = ? WHERE id = ?");
            $upd->execute([$tokenHash, $expires, $user['id']]);

            $resetUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'insights.cibergenios.com') . '/reset-password.html?token=' . urlencode($rawToken);

            $subject = 'Recuperar contraseña — Marketing Insights';
            $body = "Hola " . $user['nombre'] . ",\n\n"
                  . "Recibimos una solicitud para restablecer tu contraseña del Panel de Administración de Marketing Insights.\n\n"
                  . "Restablécela aquí (válido por 1 hora):\n" . $resetUrl . "\n\n"
                  . "Si no fuiste tú, ignora este correo — tu contraseña actual sigue funcionando.\n\n"
                  . "— Marketing Insights / Cibergenios";
            $headers = "From: Marketing Insights <no-reply@" . ($_SERVER['HTTP_HOST'] ?? 'insights.cibergenios.com') . ">\r\n"
                     . "Content-Type: text/plain; charset=UTF-8\r\n";
            @mail($user['email'], $subject, $body, $headers);
        }
    }

    // Siempre "success": no revelamos si el correo existe o no en el sistema.
    jsonOut(["status" => "success", "message" => "Si el correo existe, te enviamos un enlace para restablecer la contraseña."]);
}

if ($action === 'confirm' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $newPass = (string) ($_POST['password'] ?? '');

    if (!$token || strlen($newPass) < 8) {
        jsonOut(["status" => "error", "message" => "El enlace es inválido o la contraseña debe tener al menos 8 caracteres."], 400);
    }

    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE reset_token_hash = ? AND reset_token_expires > NOW() AND activo = 1 LIMIT 1");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonOut(["status" => "error", "message" => "El enlace expiró o ya fue usado. Solicita uno nuevo."], 400);
    }

    $upd = $pdo->prepare("UPDATE admin_users SET password_hash = ?, reset_token_hash = NULL, reset_token_expires = NULL WHERE id = ?");
    $upd->execute([password_hash($newPass, PASSWORD_DEFAULT), $user['id']]);

    jsonOut(["status" => "success", "message" => "Contraseña actualizada. Ya puedes iniciar sesión."]);
}

jsonOut(["status" => "error", "message" => "Acción no reconocida."], 400);

} catch (PDOException $e) {
    error_log('[PasswordReset API] DB Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error de base de datos."], 500);
} catch (Throwable $e) {
    error_log('[PasswordReset API] Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error interno del servidor."], 500);
}
