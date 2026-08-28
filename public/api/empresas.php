<?php
// Gestión de empresas/clientes (tabla empresas). Solo-admin.

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

function mkt_slugify(string $text): string {
    $text = trim($text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// Guarda un logo subido en assets/images/empresas/ y devuelve la URL relativa
// pública, o null si no se envió archivo para ese campo.
function mkt_save_logo(string $fieldName, string $slug, string $kind): ?string {
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonOut(["status" => "error", "message" => "Error al subir el logo ($kind)."], 400);
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        jsonOut(["status" => "error", "message" => "El logo ($kind) supera el límite de 3MB."], 400);
    }
    $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/svg+xml' => 'svg', 'image/webp' => 'webp'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        jsonOut(["status" => "error", "message" => "Formato de logo ($kind) no soportado. Usa PNG, JPG, SVG o WEBP."], 400);
    }
    $ext = $allowed[$mime];
    $dir = __DIR__ . '/../assets/images/empresas';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = $slug . '-' . $kind . '-' . time() . '.' . $ext;
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonOut(["status" => "error", "message" => "No se pudo guardar el logo ($kind)."], 500);
    }
    return 'assets/images/empresas/' . $filename;
}

mkt_require_auth();

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT e.id, e.nombre, e.slug, e.sector, e.ciudad, e.pais,
                                 e.logo_light_url, e.logo_dark_url, e.activo, e.created_at,
                                 (SELECT COUNT(*) FROM dashboards d WHERE d.empresa_id = e.id) AS total_dashboards
                          FROM empresas e ORDER BY e.nombre");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['activo'] = (bool) $r['activo'];
        $r['total_dashboards'] = (int) $r['total_dashboards'];
    }
    unset($r);
    jsonOut(["status" => "success", "empresas" => $rows]);
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $slug = trim($_POST['slug'] ?? '') ?: mkt_slugify($nombre);
    $sector = trim($_POST['sector'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $pais = trim($_POST['pais'] ?? '') ?: 'Colombia';

    if (!$nombre || !$slug) {
        jsonOut(["status" => "error", "message" => "El nombre de la empresa es obligatorio."], 400);
    }
    $slug = mkt_slugify($slug);

    $dup = $pdo->prepare("SELECT id FROM empresas WHERE slug = ?");
    $dup->execute([$slug]);
    if ($dup->fetch()) {
        jsonOut(["status" => "error", "message" => "Ya existe una empresa con ese slug."], 409);
    }

    $logoLight = mkt_save_logo('logo_light', $slug, 'light');
    $logoDark = mkt_save_logo('logo_dark', $slug, 'dark');
    $token = bin2hex(random_bytes(16));

    $ins = $pdo->prepare("INSERT INTO empresas (nombre, slug, sector, ciudad, pais, logo_light_url, logo_dark_url, token_acceso_maestro, activo)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $ins->execute([$nombre, $slug, $sector ?: null, $ciudad ?: null, $pais, $logoLight, $logoDark, $token]);

    jsonOut(["status" => "success", "id" => (int) $pdo->lastInsertId()]);
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $pais = trim($_POST['pais'] ?? '') ?: 'Colombia';

    if (!$id || !$nombre || !$slug) {
        jsonOut(["status" => "error", "message" => "Nombre y slug son obligatorios."], 400);
    }
    $slug = mkt_slugify($slug);

    $dup = $pdo->prepare("SELECT id FROM empresas WHERE slug = ? AND id != ?");
    $dup->execute([$slug, $id]);
    if ($dup->fetch()) {
        jsonOut(["status" => "error", "message" => "Ya existe otra empresa con ese slug."], 409);
    }

    $logoLight = mkt_save_logo('logo_light', $slug, 'light');
    $logoDark = mkt_save_logo('logo_dark', $slug, 'dark');

    $fields = "nombre = ?, slug = ?, sector = ?, ciudad = ?, pais = ?";
    $params = [$nombre, $slug, $sector ?: null, $ciudad ?: null, $pais];
    if ($logoLight !== null) { $fields .= ", logo_light_url = ?"; $params[] = $logoLight; }
    if ($logoDark !== null)  { $fields .= ", logo_dark_url = ?";  $params[] = $logoDark; }
    $params[] = $id;

    $upd = $pdo->prepare("UPDATE empresas SET {$fields} WHERE id = ?");
    $upd->execute($params);

    jsonOut(["status" => "success"]);
}

if ($action === 'toggle_active' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) jsonOut(["status" => "error", "message" => "Empresa inválida."], 400);

    $stmt = $pdo->prepare("SELECT activo FROM empresas WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonOut(["status" => "error", "message" => "Empresa no encontrada."], 404);

    $newState = (int) $row['activo'] === 1 ? 0 : 1;
    $pdo->prepare("UPDATE empresas SET activo = ? WHERE id = ?")->execute([$newState, $id]);

    jsonOut(["status" => "success", "activo" => (bool) $newState]);
}

jsonOut(["status" => "error", "message" => "Acción no reconocida."], 400);

} catch (PDOException $e) {
    error_log('[Empresas API] DB Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error de base de datos."], 500);
} catch (Throwable $e) {
    error_log('[Empresas API] Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error interno del servidor."], 500);
}
