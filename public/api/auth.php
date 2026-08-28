<?php
// Autenticación real de servidor para el Panel Admin de Marketing Insights.
// Reemplaza el gate de solo-localStorage que había en login.html/admin.html.

// El servidor tiene uopz.exit=0, que desactiva exit()/die() globalmente y
// deja seguir ejecutando el código posterior (ver data.php para el detalle).
// Aquí es crítico: sin esto, un login con credenciales inválidas caía en el
// bloque de error pero igual llegaba a crear la sesión de admin más abajo.
if (function_exists('uopz_allow_exit')) {
    uopz_allow_exit(true);
}

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

define('MKT_ADMIN_ACCOUNTS', [
    'webmaster@cibergenios.com' => 'Dieg0$m1',
]);

function mkt_is_authenticated(): bool {
    return !empty($_SESSION['mkt_admin_user']) && array_key_exists($_SESSION['mkt_admin_user'], MKT_ADMIN_ACCOUNTS);
}

function mkt_require_auth(): void {
    if (!mkt_is_authenticated()) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "No autenticado."]);
        exit();
    }
}
