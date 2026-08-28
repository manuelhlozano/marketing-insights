<?php
// Gestión de usuarios del panel admin (tabla admin_users). Solo-admin: cualquier
// sesión válida puede administrar cualquier cuenta, incluida la propia — no hay
// roles distintos todavía, todo autenticado es "superadmin".

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

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT id, nombre, email, activo, created_at FROM admin_users ORDER BY created_at ASC");
    jsonOut(["status" => "success", "users" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (!$nombre || !$email || strlen($password) < 8) {
        jsonOut(["status" => "error", "message" => "Nombre, correo y una contraseña de al menos 8 caracteres son obligatorios."], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonOut(["status" => "error", "message" => "El correo no es válido."], 400);
    }

    $dup = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
    $dup->execute([$email]);
    if ($dup->fetch()) {
        jsonOut(["status" => "error", "message" => "Ya existe un usuario con ese correo."], 409);
    }

    $ins = $pdo->prepare("INSERT INTO admin_users (nombre, email, password_hash, activo) VALUES (?, ?, ?, 1)");
    $ins->execute([$nombre, $email, password_hash($password, PASSWORD_DEFAULT)]);

    jsonOut(["status" => "success", "id" => (int) $pdo->lastInsertId()]);
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!$id || !$nombre || !$email) {
        jsonOut(["status" => "error", "message" => "Nombre y correo son obligatorios."], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonOut(["status" => "error", "message" => "El correo no es válido."], 400);
    }

    $dup = $pdo->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
    $dup->execute([$email, $id]);
    if ($dup->fetch()) {
        jsonOut(["status" => "error", "message" => "Ya existe otro usuario con ese correo."], 409);
    }

    $upd = $pdo->prepare("UPDATE admin_users SET nombre = ?, email = ? WHERE id = ?");
    $upd->execute([$nombre, $email, $id]);

    jsonOut(["status" => "success"]);
}

if ($action === 'set_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $password = (string) ($_POST['password'] ?? '');

    if (!$id || strlen($password) < 8) {
        jsonOut(["status" => "error", "message" => "La contraseña debe tener al menos 8 caracteres."], 400);
    }

    $upd = $pdo->prepare("UPDATE admin_users SET password_hash = ?, reset_token_hash = NULL, reset_token_expires = NULL WHERE id = ?");
    $upd->execute([password_hash($password, PASSWORD_DEFAULT), $id]);

    if ($upd->rowCount() === 0) {
        jsonOut(["status" => "error", "message" => "Usuario no encontrado."], 404);
    }

    jsonOut(["status" => "success"]);
}

if ($action === 'toggle_active' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) jsonOut(["status" => "error", "message" => "Usuario inválido."], 400);

    if ((int) ($_SESSION['mkt_admin_user_id'] ?? 0) === $id) {
        jsonOut(["status" => "error", "message" => "No puedes desactivar tu propia cuenta mientras tienes la sesión abierta."], 400);
    }

    $activeCount = (int) $pdo->query("SELECT COUNT(*) FROM admin_users WHERE activo = 1")->fetchColumn();
    $current = $pdo->prepare("SELECT activo FROM admin_users WHERE id = ?");
    $current->execute([$id]);
    $row = $current->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonOut(["status" => "error", "message" => "Usuario no encontrado."], 404);

    if ((int) $row['activo'] === 1 && $activeCount <= 1) {
        jsonOut(["status" => "error", "message" => "No puedes desactivar el último usuario activo."], 400);
    }

    $newState = (int) $row['activo'] === 1 ? 0 : 1;
    $pdo->prepare("UPDATE admin_users SET activo = ? WHERE id = ?")->execute([$newState, $id]);

    jsonOut(["status" => "success", "activo" => (bool) $newState]);
}

jsonOut(["status" => "error", "message" => "Acción no reconocida."], 400);

} catch (PDOException $e) {
    error_log('[Users API] DB Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error de base de datos."], 500);
} catch (Throwable $e) {
    error_log('[Users API] Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error interno del servidor."], 500);
}
