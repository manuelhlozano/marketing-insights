<?php
/**
 * Enrutador de los enlaces bonitos: /{empresa} y /{empresa}/{informe}
 *
 * Antes esas dos formas las reescribía Apache directamente a index.html, y
 * como Apache no sabe qué empresas existen, CUALQUIER ruta de uno o dos tramos
 * respondía 200 con la página del panel: /wp-admin, /server-status, lo que
 * fuera. Sin datos, porque el token se valida aparte, pero el sitio no
 * devolvía 404 jamás, y eso enturbia los registros y despista a cualquier
 * monitor que vigile el estado del sitio.
 *
 * Apache no puede consultar la base de datos, así que la comprobación se hace
 * aquí: si la empresa (y el informe, cuando viene) existen de verdad, se
 * entrega index.html con 200; si no, un 404 real y escueto.
 *
 * Esto no decide quién ve qué: el token del informe lo sigue validando
 * data.php. Aquí solo se responde si la dirección corresponde a algo.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (function_exists('uopz_allow_exit')) { uopz_allow_exit(true); }

$empresaSlug  = (string) ($_GET['empresa'] ?? '');
$informeSlug  = (string) ($_GET['dashboard'] ?? '');

function mkt_entregar_panel(): void {
    $pagina = __DIR__ . '/index.html';
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    readfile($pagina);
    exit;
}

function mkt_no_encontrado(): void {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<meta name="robots" content="noindex, nofollow"><title>Página no encontrada</title>'
       . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
       . 'font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f4f6fb;color:#1f2937}'
       . 'div{text-align:center;padding:24px}h1{font-size:54px;margin:0 0 6px;color:#0f56d1}'
       . 'p{margin:0;font-size:15px;color:#6b7280}</style></head><body><div>'
       . '<h1>404</h1><p>Esta dirección no corresponde a ningún informe.</p>'
       . '</div></body></html>';
    exit;
}

// Un slug solo puede tener letras, números, guion y guion bajo. Se comprueba
// antes de tocar la base de datos para que una ruta rara ni siquiera llegue a
// consultarla.
$formatoValido = '/^[A-Za-z0-9_-]{1,191}$/';
if (!preg_match($formatoValido, $empresaSlug)) mkt_no_encontrado();
if ($informeSlug !== '' && !preg_match($formatoValido, $informeSlug)) mkt_no_encontrado();

require_once __DIR__ . '/api/config.php';

try {
    $stmt = $pdo->prepare("SELECT id FROM empresas WHERE slug = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$empresaSlug]);
    $empresaId = $stmt->fetchColumn();
    if ($empresaId === false) mkt_no_encontrado();

    if ($informeSlug !== '') {
        $stmt = $pdo->prepare("SELECT id FROM dashboards WHERE empresa_id = ? AND slug = ? LIMIT 1");
        $stmt->execute([(int) $empresaId, $informeSlug]);
        if ($stmt->fetchColumn() === false) mkt_no_encontrado();
    }

    mkt_entregar_panel();
} catch (Throwable $e) {
    // Si la base de datos falla, se entrega la página igual: el informe dirá
    // que no pudo cargar, que es mejor que responder 404 a un cliente cuyo
    // enlace sí es correcto.
    error_log('[MktInsights Ruta] ' . $e->getMessage());
    mkt_entregar_panel();
}
