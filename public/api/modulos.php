<?php
// Módulos/indicadores visibles en el dashboard público, por dashboard
// específico (tabla modulos_indicadores). Solo-admin.

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
    $dashboardId = (int) ($_GET['dashboard_id'] ?? 0);
    if (!$dashboardId) jsonOut(["status" => "error", "message" => "Falta el dashboard."], 400);

    $stmt = $pdo->prepare("SELECT id, nombre, codigo, tipo_visualizacion, orden, activo
                            FROM modulos_indicadores WHERE dashboard_id = ? ORDER BY orden");
    $stmt->execute([$dashboardId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['id'] = (int) $r['id'];
        $r['orden'] = (int) $r['orden'];
        $r['activo'] = (bool) $r['activo'];
    }
    unset($r);
    jsonOut(["status" => "success", "modulos" => $rows]);
}

if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) jsonOut(["status" => "error", "message" => "Módulo inválido."], 400);

    $stmt = $pdo->prepare("SELECT activo FROM modulos_indicadores WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonOut(["status" => "error", "message" => "Módulo no encontrado."], 404);

    $newState = (int) $row['activo'] === 1 ? 0 : 1;
    $pdo->prepare("UPDATE modulos_indicadores SET activo = ? WHERE id = ?")->execute([$newState, $id]);

    jsonOut(["status" => "success", "activo" => (bool) $newState]);
}

if ($action === 'reorder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dashboardId = (int) ($_POST['dashboard_id'] ?? 0);
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (!$dashboardId || !is_array($ids) || empty($ids)) {
        jsonOut(["status" => "error", "message" => "Datos de reordenamiento inválidos."], 400);
    }

    $upd = $pdo->prepare("UPDATE modulos_indicadores SET orden = ? WHERE id = ? AND dashboard_id = ?");
    $orden = 1;
    foreach ($ids as $id) {
        $upd->execute([$orden, (int) $id, $dashboardId]);
        $orden++;
    }

    jsonOut(["status" => "success"]);
}

jsonOut(["status" => "error", "message" => "Acción no reconocida."], 400);

} catch (PDOException $e) {
    error_log('[Modulos API] DB Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error de base de datos."], 500);
} catch (Throwable $e) {
    error_log('[Modulos API] Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error interno del servidor."], 500);
}
