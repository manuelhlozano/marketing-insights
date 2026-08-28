<?php
// "Mi Cuenta" — self-service para el usuario autenticado sobre SU PROPIA
// cuenta únicamente (a diferencia de users.php, que administra cualquier
// usuario). Cambiar la contraseña propia exige la contraseña actual.

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

mkt_require_auth();
$userId = (int) $_SESSION['mkt_admin_user_id'];

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {

if ($action === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = mkt_current_user($pdo);
    if (!$user) jsonOut(["status" => "error", "message" => "No autenticado."], 401);
    jsonOut(["status" => "success", "user" => ["id" => (int) $user['id'], "nombre" => $user['nombre'], "email" => $user['email']]]);
}

if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!$nombre || !$email) {
        jsonOut(["status" => "error", "message" => "Nombre y correo son obligatorios."], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonOut(["status" => "error", "message" => "El correo no es válido."], 400);
    }

    $dup = $pdo->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
    $dup->execute([$email, $userId]);
    if ($dup->fetch()) {
        jsonOut(["status" => "error", "message" => "Ya existe otro usuario con ese correo."], 409);
    }

    $upd = $pdo->prepare("UPDATE admin_users SET nombre = ?, email = ? WHERE id = ?");
    $upd->execute([$nombre, $email, $userId]);

    jsonOut(["status" => "success", "user" => ["id" => $userId, "nombre" => $nombre, "email" => $email]]);
}

if ($action === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');

    if (strlen($new) < 8) {
        jsonOut(["status" => "error", "message" => "La nueva contraseña debe tener al menos 8 caracteres."], 400);
    }

    $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($current, $row['password_hash'])) {
        jsonOut(["status" => "error", "message" => "La contraseña actual no es correcta."], 401);
    }

    $upd = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
    $upd->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);

    jsonOut(["status" => "success"]);
}

jsonOut(["status" => "error", "message" => "Acción no reconocida."], 400);

} catch (PDOException $e) {
    error_log('[Account API] DB Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error de base de datos."], 500);
} catch (Throwable $e) {
    error_log('[Account API] Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error interno del servidor."], 500);
}
