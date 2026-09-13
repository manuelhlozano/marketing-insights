<?php
// Gestión de usuarios del panel admin (tabla admin_users) junto con su rol,
// las empresas que tiene asignadas y los módulos habilitados.
// Todo este endpoint es exclusivo del rol superadmin: quien puede crear
// usuarios puede darse permisos a sí mismo, así que no se delega.

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

// Reemplaza por completo las empresas y módulos asignados a un usuario.
// El superadmin no lleva asignaciones: su acceso es total por definición, y
// guardar filas para él solo abriría la puerta a que queden desincronizadas.
function mkt_guardar_asignaciones(PDO $pdo, int $userId, string $rol, array $empresas, array $modulos): void {
    $pdo->prepare("DELETE FROM admin_user_empresas WHERE user_id = ?")->execute([$userId]);
    $pdo->prepare("DELETE FROM admin_user_modulos WHERE user_id = ?")->execute([$userId]);
    if ($rol === 'superadmin') return;

    $empresasValidas = $pdo->query("SELECT id FROM empresas")->fetchAll(PDO::FETCH_COLUMN);
    $empresasValidas = array_map('intval', $empresasValidas);
    $insEmp = $pdo->prepare("INSERT INTO admin_user_empresas (user_id, empresa_id) VALUES (?, ?)");
    foreach (array_unique(array_map('intval', $empresas)) as $empresaId) {
        if (in_array($empresaId, $empresasValidas, true)) $insEmp->execute([$userId, $empresaId]);
    }

    $insMod = $pdo->prepare("INSERT INTO admin_user_modulos (user_id, modulo) VALUES (?, ?)");
    foreach (array_unique($modulos) as $modulo) {
        if (in_array($modulo, MKT_MODULOS, true)) $insMod->execute([$userId, $modulo]);
    }
}

function mkt_leer_asignaciones(PDO $pdo, int $userId): array {
    $e = $pdo->prepare("SELECT empresa_id FROM admin_user_empresas WHERE user_id = ?");
    $e->execute([$userId]);
    $m = $pdo->prepare("SELECT modulo FROM admin_user_modulos WHERE user_id = ?");
    $m->execute([$userId]);
    return [
        'empresas' => array_map('intval', $e->fetchAll(PDO::FETCH_COLUMN)),
        'modulos' => $m->fetchAll(PDO::FETCH_COLUMN),
    ];
}

function mkt_rol_valido(string $rol): string {
    return in_array($rol, MKT_ROLES, true) ? $rol : 'operador';
}

// El último superadmin activo no se puede degradar ni desactivar: si se
// permitiera, el panel quedaría sin nadie capaz de gestionar usuarios.
function mkt_es_ultimo_superadmin(PDO $pdo, int $userId): bool {
    $stmt = $pdo->prepare("SELECT rol, activo FROM admin_users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u || $u['rol'] !== 'superadmin' || (int) $u['activo'] !== 1) return false;
    $otros = (int) $pdo->query("SELECT COUNT(*) FROM admin_users WHERE rol = 'superadmin' AND activo = 1")->fetchColumn();
    return $otros <= 1;
}

mkt_require_auth();
mkt_require_superadmin($pdo);

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT id, nombre, email, rol, activo, created_at FROM admin_users ORDER BY created_at ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as &$u) {
        $u['id'] = (int) $u['id'];
        $u['activo'] = (bool) $u['activo'];
        $asig = mkt_leer_asignaciones($pdo, $u['id']);
        $u['empresas'] = $asig['empresas'];
        $u['modulos'] = $asig['modulos'];
    }
    unset($u);
    jsonOut(["status" => "success", "users" => $users, "modulos_disponibles" => MKT_MODULOS, "roles" => MKT_ROLES]);
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $rol = mkt_rol_valido(trim($_POST['rol'] ?? ''));
    $empresas = json_decode($_POST['empresas'] ?? '[]', true) ?: [];
    $modulos = json_decode($_POST['modulos'] ?? '[]', true) ?: [];

    if (!$nombre || !$email || strlen($password) < 8) {
        jsonOut(["status" => "error", "message" => "Nombre, correo y una contraseña de al menos 8 caracteres son obligatorios."], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonOut(["status" => "error", "message" => "El correo no es válido."], 400);
    }
    if ($rol !== 'superadmin' && empty($empresas)) {
        jsonOut(["status" => "error", "message" => "Asigna al menos una empresa a este usuario, o hazlo superadministrador."], 400);
    }
    if ($rol !== 'superadmin' && empty($modulos)) {
        jsonOut(["status" => "error", "message" => "Habilita al menos un módulo para este usuario."], 400);
    }

    $dup = $pdo->prepare("SELECT id FROM admin_users WHERE email = ?");
    $dup->execute([$email]);
    if ($dup->fetch()) {
        jsonOut(["status" => "error", "message" => "Ya existe un usuario con ese correo."], 409);
    }

    $ins = $pdo->prepare("INSERT INTO admin_users (nombre, email, rol, password_hash, activo) VALUES (?, ?, ?, ?, 1)");
    $ins->execute([$nombre, $email, $rol, password_hash($password, PASSWORD_DEFAULT)]);
    $userId = (int) $pdo->lastInsertId();
    mkt_guardar_asignaciones($pdo, $userId, $rol, $empresas, $modulos);

    jsonOut(["status" => "success", "id" => $userId]);
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = mkt_rol_valido(trim($_POST['rol'] ?? ''));
    $empresas = json_decode($_POST['empresas'] ?? '[]', true) ?: [];
    $modulos = json_decode($_POST['modulos'] ?? '[]', true) ?: [];

    if (!$id || !$nombre || !$email) {
        jsonOut(["status" => "error", "message" => "Nombre y correo son obligatorios."], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonOut(["status" => "error", "message" => "El correo no es válido."], 400);
    }
    if ($rol !== 'superadmin' && mkt_es_ultimo_superadmin($pdo, $id)) {
        jsonOut(["status" => "error", "message" => "Este es el último superadministrador activo: si le cambias el rol nadie podría volver a gestionar usuarios."], 400);
    }
    if ($rol !== 'superadmin' && empty($empresas)) {
        jsonOut(["status" => "error", "message" => "Asigna al menos una empresa a este usuario, o hazlo superadministrador."], 400);
    }
    if ($rol !== 'superadmin' && empty($modulos)) {
        jsonOut(["status" => "error", "message" => "Habilita al menos un módulo para este usuario."], 400);
    }

    $dup = $pdo->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
    $dup->execute([$email, $id]);
    if ($dup->fetch()) {
        jsonOut(["status" => "error", "message" => "Ya existe otro usuario con ese correo."], 409);
    }

    $upd = $pdo->prepare("UPDATE admin_users SET nombre = ?, email = ?, rol = ? WHERE id = ?");
    $upd->execute([$nombre, $email, $rol, $id]);
    mkt_guardar_asignaciones($pdo, $id, $rol, $empresas, $modulos);

    jsonOut(["status" => "success"]);
}

if ($action === 'set_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $password = (string) ($_POST['password'] ?? '');

    if (!$id || strlen($password) < 8) {
        jsonOut(["status" => "error", "message" => "La contraseña debe tener al menos 8 caracteres."], 400);
    }

    // Ponerle contraseña nueva a alguien también lo desbloquea: es la vía por
    // la que la agencia rescata a un usuario bloqueado por intentos fallidos.
    $upd = $pdo->prepare("UPDATE admin_users SET password_hash = ?, reset_token_hash = NULL,
                          reset_token_expires = NULL, intentos_fallidos = 0, bloqueado_hasta = NULL
                          WHERE id = ?");
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
    if ((int) $row['activo'] === 1 && mkt_es_ultimo_superadmin($pdo, $id)) {
        jsonOut(["status" => "error", "message" => "Este es el último superadministrador activo: si lo desactivas nadie podría volver a gestionar usuarios."], 400);
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
