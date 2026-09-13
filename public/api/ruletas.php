<?php
// Gestión admin del módulo de Ruleta de Concursos: ruletas, premios y
// estaciones (taquilla/cajero) con URL fija, más la analítica agregada
// que alimenta el informe mensual del cliente. Solo-admin.

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (function_exists('uopz_allow_exit')) { uopz_allow_exit(true); }

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ruleta-metrics.php';

function jsonOut($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

mkt_require_auth();

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT r.id, r.empresa_id, r.nombre, r.slug, r.titulo_publico, r.duracion_segundos,
                                 r.imagen_fondo_url, r.imagen_centro_url, r.activa, e.nombre AS empresa_nombre,
                                 (SELECT COUNT(*) FROM ruleta_premios WHERE ruleta_id = r.id) AS total_premios,
                                 (SELECT COUNT(*) FROM ruleta_estaciones WHERE ruleta_id = r.id) AS total_estaciones
                          FROM ruletas r JOIN empresas e ON e.id = r.empresa_id
                          ORDER BY e.nombre, r.nombre");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['activa'] = (bool) $r['activa'];
        $r['duracion_segundos'] = (float) $r['duracion_segundos'];
        $r['total_premios'] = (int) $r['total_premios'];
        $r['total_estaciones'] = (int) $r['total_estaciones'];
    }
    unset($r);
    jsonOut(["status" => "success", "ruletas" => $rows]);
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresaId = (int) ($_POST['empresa_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $tituloPublico = trim($_POST['titulo_publico'] ?? '') ?: '¡Gira y gana!';
    $duracion = (float) ($_POST['duracion_segundos'] ?? 5);
    $imagenFondo = trim($_POST['imagen_fondo_url'] ?? '') ?: null;
    $imagenCentro = trim($_POST['imagen_centro_url'] ?? '') ?: null;

    if (!$empresaId || !$nombre || !$slug) {
        jsonOut(["status" => "error", "message" => "Empresa, nombre y slug son obligatorios."], 400);
    }

    $dup = $pdo->prepare("SELECT id FROM ruletas WHERE empresa_id = ? AND slug = ?");
    $dup->execute([$empresaId, $slug]);
    if ($dup->fetch()) {
        jsonOut(["status" => "error", "message" => "Ya existe una ruleta con ese slug para esta empresa."], 409);
    }

    $ins = $pdo->prepare("INSERT INTO ruletas (empresa_id, nombre, slug, titulo_publico, duracion_segundos, imagen_fondo_url, imagen_centro_url, activa)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $ins->execute([$empresaId, $nombre, $slug, $tituloPublico, $duracion, $imagenFondo, $imagenCentro]);
    jsonOut(["status" => "success", "id" => (int) $pdo->lastInsertId()]);
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $tituloPublico = trim($_POST['titulo_publico'] ?? '') ?: '¡Gira y gana!';
    $duracion = (float) ($_POST['duracion_segundos'] ?? 5);
    $imagenFondo = trim($_POST['imagen_fondo_url'] ?? '') ?: null;
    $imagenCentro = trim($_POST['imagen_centro_url'] ?? '') ?: null;

    if (!$id || !$nombre || !$slug) {
        jsonOut(["status" => "error", "message" => "Datos incompletos."], 400);
    }

    $upd = $pdo->prepare("UPDATE ruletas SET nombre = ?, slug = ?, titulo_publico = ?, duracion_segundos = ?, imagen_fondo_url = ?, imagen_centro_url = ? WHERE id = ?");
    $upd->execute([$nombre, $slug, $tituloPublico, $duracion, $imagenFondo, $imagenCentro, $id]);
    jsonOut(["status" => "success"]);
}

if ($action === 'toggle_active' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) jsonOut(["status" => "error", "message" => "Ruleta inválida."], 400);
    $stmt = $pdo->prepare("SELECT activa FROM ruletas WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonOut(["status" => "error", "message" => "Ruleta no encontrada."], 404);
    $newState = (int) $row['activa'] === 1 ? 0 : 1;
    $pdo->prepare("UPDATE ruletas SET activa = ? WHERE id = ?")->execute([$newState, $id]);
    jsonOut(["status" => "success", "activa" => (bool) $newState]);
}

// ── Premios ──────────────────────────────────────────────────────────
if ($action === 'premios_list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $ruletaId = (int) ($_GET['ruleta_id'] ?? 0);
    if (!$ruletaId) jsonOut(["status" => "error", "message" => "Falta la ruleta."], 400);
    $stmt = $pdo->prepare("SELECT id, nombre, color, icono, probabilidad, es_perdedor, orden FROM ruleta_premios WHERE ruleta_id = ? ORDER BY orden, id");
    $stmt->execute([$ruletaId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['probabilidad'] = (float) $r['probabilidad'];
        $r['es_perdedor'] = (bool) $r['es_perdedor'];
    }
    unset($r);
    jsonOut(["status" => "success", "premios" => $rows]);
}

if ($action === 'premio_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $ruletaId = (int) ($_POST['ruleta_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#0284C7';
    // El icono puede ser un slug de Tabler Icons (ej. "ticket") o la ruta de una
    // imagen propia del sitio (ej. "assets/images/icono-crispetas.svg"). Se
    // rechaza cualquier otra cosa para que nunca entre texto libre ni una URL
    // externa a la clase CSS / al src del <img>.
    $icono = trim($_POST['icono'] ?? '');
    $esSlugTabler = preg_match('/^[a-z0-9-]{1,60}$/', $icono);
    $esRutaImagen = preg_match('#^assets/[A-Za-z0-9/_-]+\.(svg|png|webp)$#', $icono);
    $icono = ($esSlugTabler || $esRutaImagen) ? $icono : null;
    $probabilidad = (float) ($_POST['probabilidad'] ?? 0);
    $esPerdedor = !empty($_POST['es_perdedor']) ? 1 : 0;

    if (!$ruletaId || !$nombre) jsonOut(["status" => "error", "message" => "Nombre del premio obligatorio."], 400);

    if ($id) {
        $upd = $pdo->prepare("UPDATE ruleta_premios SET nombre = ?, color = ?, icono = ?, probabilidad = ?, es_perdedor = ? WHERE id = ? AND ruleta_id = ?");
        $upd->execute([$nombre, $color, $icono, $probabilidad, $esPerdedor, $id, $ruletaId]);
        jsonOut(["status" => "success", "id" => $id]);
    } else {
        $ordenStmt = $pdo->prepare("SELECT COALESCE(MAX(orden), 0) + 1 FROM ruleta_premios WHERE ruleta_id = ?");
        $ordenStmt->execute([$ruletaId]);
        $orden = (int) $ordenStmt->fetchColumn();
        $ins = $pdo->prepare("INSERT INTO ruleta_premios (ruleta_id, nombre, color, icono, probabilidad, es_perdedor, orden) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$ruletaId, $nombre, $color, $icono, $probabilidad, $esPerdedor, $orden]);
        jsonOut(["status" => "success", "id" => (int) $pdo->lastInsertId()]);
    }
}

if ($action === 'premio_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) jsonOut(["status" => "error", "message" => "Premio inválido."], 400);
    $pdo->prepare("DELETE FROM ruleta_premios WHERE id = ?")->execute([$id]);
    jsonOut(["status" => "success"]);
}

// ── Estaciones (taquilla / cajero, con URL fija) ────────────────────
if ($action === 'estaciones_list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $ruletaId = (int) ($_GET['ruleta_id'] ?? 0);
    if (!$ruletaId) jsonOut(["status" => "error", "message" => "Falta la ruleta."], 400);
    $stmt = $pdo->prepare("SELECT id, nombre, tipo, token, ancho_ticket, activa FROM ruleta_estaciones WHERE ruleta_id = ? ORDER BY tipo, nombre");
    $stmt->execute([$ruletaId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['activa'] = (bool) $r['activa'];
        $r['url'] = '/ruleta.html?e=' . $r['token'];
    }
    unset($r);
    jsonOut(["status" => "success", "estaciones" => $rows]);
}

if ($action === 'estacion_create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruletaId = (int) ($_POST['ruleta_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = ($_POST['tipo'] ?? 'taquilla') === 'cajero' ? 'cajero' : 'taquilla';
    $anchoTicket = ($_POST['ancho_ticket'] ?? '80mm') === '58mm' ? '58mm' : '80mm';

    if (!$ruletaId || !$nombre) jsonOut(["status" => "error", "message" => "Nombre de la estación obligatorio."], 400);

    $token = bin2hex(random_bytes(20));
    $ins = $pdo->prepare("INSERT INTO ruleta_estaciones (ruleta_id, nombre, tipo, token, ancho_ticket, activa) VALUES (?, ?, ?, ?, ?, 1)");
    $ins->execute([$ruletaId, $nombre, $tipo, $token, $anchoTicket]);
    jsonOut(["status" => "success", "id" => (int) $pdo->lastInsertId(), "token" => $token, "url" => '/ruleta.html?e=' . $token]);
}

if ($action === 'estacion_toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) jsonOut(["status" => "error", "message" => "Estación inválida."], 400);
    $stmt = $pdo->prepare("SELECT activa FROM ruleta_estaciones WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonOut(["status" => "error", "message" => "Estación no encontrada."], 404);
    $newState = (int) $row['activa'] === 1 ? 0 : 1;
    $pdo->prepare("UPDATE ruleta_estaciones SET activa = ? WHERE id = ?")->execute([$newState, $id]);
    jsonOut(["status" => "success", "activa" => (bool) $newState]);
}

if ($action === 'estacion_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (!$id) jsonOut(["status" => "error", "message" => "Estación inválida."], 400);
    $pdo->prepare("DELETE FROM ruleta_estaciones WHERE id = ?")->execute([$id]);
    jsonOut(["status" => "success"]);
}

// ── Analítica ────────────────────────────────────────────────────────
if ($action === 'analitica' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $ruletaId = (int) ($_GET['ruleta_id'] ?? 0);
    if (!$ruletaId) jsonOut(["status" => "error", "message" => "Falta la ruleta."], 400);

    $desde = trim($_GET['desde'] ?? '') ?: date('Y-m-01');
    $hasta = trim($_GET['hasta'] ?? '') ?: date('Y-m-t');
    $desdeDt = $desde . ' 00:00:00';
    $hastaDt = $hasta . ' 23:59:59';

    $baseSql = "FROM ruleta_giros g
                 JOIN ruleta_estaciones e ON e.id = g.estacion_id
                 JOIN ruleta_premios p ON p.id = g.premio_id
                 WHERE g.ruleta_id = ? AND g.fecha_hora BETWEEN ? AND ?";

    $totalStmt = $pdo->prepare("SELECT COUNT(*) $baseSql");
    $totalStmt->execute([$ruletaId, $desdeDt, $hastaDt]);
    $totalGiros = (int) $totalStmt->fetchColumn();

    $taquillaStmt = $pdo->prepare("SELECT COUNT(*) $baseSql AND e.tipo = 'taquilla'");
    $taquillaStmt->execute([$ruletaId, $desdeDt, $hastaDt]);
    $girosTaquilla = (int) $taquillaStmt->fetchColumn();

    $cajeroStmt = $pdo->prepare("SELECT COUNT(*) $baseSql AND e.tipo = 'cajero'");
    $cajeroStmt->execute([$ruletaId, $desdeDt, $hastaDt]);
    $girosCajero = (int) $cajeroStmt->fetchColumn();

    $premiosStmt = $pdo->prepare("SELECT COUNT(*) $baseSql AND p.es_perdedor = 0");
    $premiosStmt->execute([$ruletaId, $desdeDt, $hastaDt]);
    $premiosEntregados = (int) $premiosStmt->fetchColumn();

    $horaStmt = $pdo->prepare("SELECT HOUR(g.fecha_hora) AS hora, COUNT(*) AS total $baseSql
                                GROUP BY hora ORDER BY total DESC LIMIT 1");
    $horaStmt->execute([$ruletaId, $desdeDt, $hastaDt]);
    $horaRow = $horaStmt->fetch(PDO::FETCH_ASSOC);
    $horaPico = $horaRow ? sprintf('%02d:00 - %02d:00', (int) $horaRow['hora'], ((int) $horaRow['hora'] + 1) % 24) : '—';

    $topStmt = $pdo->prepare("SELECT p.nombre AS nombre, COUNT(*) AS total $baseSql AND p.es_perdedor = 0
                               GROUP BY p.id, p.nombre ORDER BY total DESC LIMIT 10");
    $topStmt->execute([$ruletaId, $desdeDt, $hastaDt]);
    $topPremios = $topStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($topPremios as &$tp) { $tp['total'] = (int) $tp['total']; }
    unset($tp);

    $porEstacionStmt = $pdo->prepare("SELECT e.nombre AS nombre, e.tipo AS tipo, COUNT(*) AS total $baseSql
                                       GROUP BY e.id, e.nombre, e.tipo ORDER BY total DESC");
    $porEstacionStmt->execute([$ruletaId, $desdeDt, $hastaDt]);
    $porEstacion = $porEstacionStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($porEstacion as &$pe) { $pe['total'] = (int) $pe['total']; }
    unset($pe);

    jsonOut([
        "status" => "success",
        "desde" => $desde,
        "hasta" => $hasta,
        "total_giros" => $totalGiros,
        "giros_taquilla" => $girosTaquilla,
        "giros_cajero" => $girosCajero,
        "premios_entregados" => $premiosEntregados,
        "hora_pico" => $horaPico,
        "top_premios" => $topPremios,
        "por_estacion" => $porEstacion,
    ]);
}

jsonOut(["status" => "error", "message" => "Acción no reconocida."], 400);

} catch (PDOException $e) {
    error_log('[Ruletas API] DB Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error de base de datos."], 500);
} catch (Throwable $e) {
    error_log('[Ruletas API] Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error interno del servidor."], 500);
}
