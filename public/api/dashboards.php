<?php
// Listado de dashboards (informes mensuales) de todas las empresas, para el
// panel admin. Solo-admin.

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
    $stmt = $pdo->query("SELECT d.id, d.titulo, d.slug, d.periodo, d.public_token, d.es_publico,
                                 d.empresa_id, e.nombre AS empresa_nombre, e.slug AS empresa_slug
                          FROM dashboards d
                          JOIN empresas e ON e.id = d.empresa_id
                          ORDER BY e.nombre, d.fecha_inicio DESC, d.id DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['es_publico'] = (bool) $r['es_publico'];
        $r['empresa_id'] = (int) $r['empresa_id'];
    }
    unset($r);
    jsonOut(["status" => "success", "dashboards" => $rows]);
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresaId = (int) ($_POST['empresa_id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $periodo = trim($_POST['periodo'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $fechaInicio = trim($_POST['fecha_inicio'] ?? '') ?: null;
    $fechaFin = trim($_POST['fecha_fin'] ?? '') ?: null;

    if (!$empresaId || !$titulo || !$periodo || !$slug) {
        jsonOut(["status" => "error", "message" => "Empresa, título, periodo y slug son obligatorios."], 400);
    }

    $dup = $pdo->prepare("SELECT id FROM dashboards WHERE empresa_id = ? AND slug = ?");
    $dup->execute([$empresaId, $slug]);
    if ($dup->fetch()) {
        jsonOut(["status" => "error", "message" => "Ya existe un dashboard con ese slug para esta empresa."], 409);
    }

    $token = 'mkt_live_' . bin2hex(random_bytes(8));
    $ins = $pdo->prepare("INSERT INTO dashboards (empresa_id, titulo, slug, periodo, fecha_inicio, fecha_fin, public_token, es_publico)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $ins->execute([$empresaId, $titulo, $slug, $periodo, $fechaInicio, $fechaFin, $token]);

    jsonOut(["status" => "success", "id" => (int) $pdo->lastInsertId(), "public_token" => $token]);
}

if ($action === 'toggle_visibility' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) jsonOut(["status" => "error", "message" => "Dashboard inválido."], 400);

    $stmt = $pdo->prepare("SELECT es_publico FROM dashboards WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonOut(["status" => "error", "message" => "Dashboard no encontrado."], 404);

    $newState = (int) $row['es_publico'] === 1 ? 0 : 1;
    $pdo->prepare("UPDATE dashboards SET es_publico = ? WHERE id = ?")->execute([$newState, $id]);

    jsonOut(["status" => "success", "es_publico" => (bool) $newState]);
}

jsonOut(["status" => "error", "message" => "Acción no reconocida."], 400);

} catch (PDOException $e) {
    error_log('[Dashboards API] DB Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error de base de datos."], 500);
} catch (Throwable $e) {
    error_log('[Dashboards API] Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error interno del servidor."], 500);
}
