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
    $stmt = $pdo->prepare("SELECT id, nombre, email, activo FROM admin_users WHERE id = ? AND activo = 1");
    $stmt->execute([$_SESSION['mkt_admin_user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}
