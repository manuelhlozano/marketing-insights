<?php
// Autenticación real de servidor para el Panel Admin de Marketing Insights.
// Usuarios reales en la tabla admin_users (contraseñas con password_hash/bcrypt).

// El servidor tiene uopz.exit=0, que desactiva exit()/die() globalmente y
// deja seguir ejecutando el código posterior (ver data.php para el detalle).
// Aquí es crítico: sin esto, un login con credenciales inválidas caía en el
// bloque de error pero igual llegaba a crear la sesión de admin más abajo.
if (function_exists('uopz_allow_exit')) {
    uopz_allow_exit(true);
}

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function mkt_is_authenticated(): bool {
    return !empty($_SESSION['mkt_admin_user_id']);
}

function mkt_require_auth(): void {
    if (!mkt_is_authenticated()) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "No autenticado."]);
        exit();
    }
}

// Usuario actual completo (sin password_hash). null si no hay sesión o el
// usuario fue borrado/desactivado después de que se creó la sesión.
function mkt_current_user(PDO $pdo): ?array {
    if (empty($_SESSION['mkt_admin_user_id'])) return null;
    $stmt = $pdo->prepare("SELECT id, nombre, email, rol, activo FROM admin_users WHERE id = ? AND activo = 1");
    $stmt->execute([$_SESSION['mkt_admin_user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

// ─────────────────────────────────────────────────────────────────────────
// PERMISOS
//
// Los permisos se resuelven SIEMPRE en el servidor. Esconder una pestaña en
// el panel no es seguridad: cualquiera con el enlace del endpoint entraría
// igual. Por eso cada API llama a estos helpers antes de responder.
//
// Roles:
//   superadmin    → la agencia. Todo, todas las empresas y módulos.
//   admin_cliente → solo sus empresas y módulos asignados, con escritura.
//   operador      → solo sus empresas y módulos asignados, sin gestionar
//                   estructura (no crea ni borra empresas, dashboards,
//                   ruletas, concursos ni usuarios).
// ─────────────────────────────────────────────────────────────────────────

const MKT_MODULOS = ['empresas', 'dashboards', 'indicadores', 'concursos', 'leads', 'sorteos', 'ruletas', 'ajustes', 'usuarios'];
const MKT_ROLES = ['superadmin', 'admin_cliente', 'operador'];

function mkt_json_error(int $code, string $mensaje): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode(["status" => "error", "message" => $mensaje], JSON_UNESCAPED_UNICODE);
    exit();
}

function mkt_rol(PDO $pdo): string {
    $u = mkt_current_user($pdo);
    return $u ? (string) $u['rol'] : '';
}

function mkt_es_superadmin(PDO $pdo): bool {
    return mkt_rol($pdo) === 'superadmin';
}

// Empresas a las que el usuario puede acceder.
// Devuelve null cuando el usuario puede ver TODAS (superadmin): es distinto
// de [] (un usuario sin ninguna empresa asignada, que no debe ver nada).
function mkt_empresas_permitidas(PDO $pdo): ?array {
    $u = mkt_current_user($pdo);
    if (!$u) return [];
    if ($u['rol'] === 'superadmin') return null;
    $stmt = $pdo->prepare("SELECT empresa_id FROM admin_user_empresas WHERE user_id = ?");
    $stmt->execute([$u['id']]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function mkt_puede_empresa(PDO $pdo, int $empresaId): bool {
    $permitidas = mkt_empresas_permitidas($pdo);
    if ($permitidas === null) return true;
    return in_array($empresaId, $permitidas, true);
}

function mkt_require_empresa(PDO $pdo, int $empresaId): void {
    if (!mkt_puede_empresa($pdo, $empresaId)) {
        mkt_json_error(403, 'No tienes acceso a esta empresa.');
    }
}

function mkt_modulos_permitidos(PDO $pdo): array {
    $u = mkt_current_user($pdo);
    if (!$u) return [];
    if ($u['rol'] === 'superadmin') return MKT_MODULOS;
    $stmt = $pdo->prepare("SELECT modulo FROM admin_user_modulos WHERE user_id = ?");
    $stmt->execute([$u['id']]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function mkt_require_modulo(PDO $pdo, string $modulo): void {
    if (!in_array($modulo, mkt_modulos_permitidos($pdo), true)) {
        mkt_json_error(403, 'No tienes acceso a este módulo.');
    }
}

// Gestionar estructura (crear/editar/borrar empresas, dashboards, concursos,
// ruletas, usuarios). El operador puede operar el día a día pero no esto.
function mkt_require_escritura(PDO $pdo): void {
    if (!in_array(mkt_rol($pdo), ['superadmin', 'admin_cliente'], true)) {
        mkt_json_error(403, 'Tu rol no permite crear o modificar esta información.');
    }
}

// Acciones reservadas a la agencia (usuarios del panel y ajustes globales).
function mkt_require_superadmin(PDO $pdo): void {
    if (!mkt_es_superadmin($pdo)) {
        mkt_json_error(403, 'Solo un superadministrador puede hacer esto.');
    }
}

// Empresa dueña de un dashboard/concurso/ruleta, para validar el acceso a
// recursos que no reciben el empresa_id directamente en la petición.
function mkt_empresa_de(PDO $pdo, string $tabla, int $id): ?int {
    $tablasPermitidas = ['dashboards', 'concursos', 'ruletas'];
    if (!in_array($tabla, $tablasPermitidas, true)) return null;
    $stmt = $pdo->prepare("SELECT empresa_id FROM `$tabla` WHERE id = ?");
    $stmt->execute([$id]);
    $val = $stmt->fetchColumn();
    return $val === false ? null : (int) $val;
}

function mkt_require_empresa_de(PDO $pdo, string $tabla, int $id): void {
    $empresaId = mkt_empresa_de($pdo, $tabla, $id);
    if ($empresaId === null) mkt_json_error(404, 'Recurso no encontrado.');
    mkt_require_empresa($pdo, $empresaId);
}
