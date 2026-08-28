<?php
// API del Módulo de Concursos & Sorteos - Marketing Insights
// El sorteo (selección aleatoria) siempre se ejecuta en el servidor.
// Las acciones públicas nunca devuelven documento/teléfono/correo.

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (function_exists('uopz_allow_exit')) { uopz_allow_exit(true); }

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/concursos-metrics.php';

function jsonOut($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fullName(array $lead): string {
    return trim($lead['nombre'] . ' ' . $lead['apellido']);
}

// Cuántas veces (en TODOS los concursos) ha ganado un premio quien tiene este documento.
// Es la señal principal anti-fraude: un mismo documento ganando repetidamente en distintos
// concursos/mecánicas es la sospecha que el admin debe poder ver de un vistazo.
function mkt_wins_totales(PDO $pdo, string $documento): int {
    if ($documento === '') return 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM concurso_sorteos s
                            JOIN concurso_leads l ON l.id = s.lead_id
                            WHERE l.documento = ?");
    $stmt->execute([$documento]);
    return (int) $stmt->fetchColumn();
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {

// ─────────────────────────────────────────────────────────────────
// PÚBLICO: detalle de un concurso (premios + sorteos ya jugados)
// ─────────────────────────────────────────────────────────────────
if ($action === 'detalle_publico' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $slug = $_GET['slug'] ?? '';
    if (!$slug) jsonOut(["status" => "error", "message" => "Falta el slug del concurso."], 400);

    $stmt = $pdo->prepare("SELECT * FROM concursos WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $concurso = $stmt->fetch();
    if (!$concurso) jsonOut(["status" => "error", "message" => "Concurso no encontrado."], 404);

    $empStmt = $pdo->prepare("SELECT nombre, logo_light_url, logo_dark_url FROM empresas WHERE id = ?");
    $empStmt->execute([$concurso['empresa_id']]);
    $empresa = $empStmt->fetch() ?: null;

    $premios = $pdo->prepare("SELECT kit, nombre, detalle FROM concurso_premios WHERE concurso_id = ? ORDER BY orden");
    $premios->execute([$concurso['id']]);
    $premiosRows = $premios->fetchAll();

    $draws = $pdo->prepare("SELECT s.kit, s.created_at, l.nombre, l.apellido
                             FROM concurso_sorteos s JOIN concurso_leads l ON l.id = s.lead_id
                             WHERE s.concurso_id = ? ORDER BY s.created_at");
    $draws->execute([$concurso['id']]);
    $drawsRows = $draws->fetchAll();

    $drawsOut = array_map(function ($d) {
        return ["kit" => $d['kit'], "ganador" => trim($d['nombre'] . ' ' . $d['apellido']), "fecha" => $d['created_at']];
    }, $drawsRows);

    jsonOut([
        "status" => "success",
        "concurso" => [
            "id" => (int) $concurso['id'],
            "nombre" => $concurso['nombre'],
            "slug" => $concurso['slug'],
            "premios" => $premiosRows,
        ],
        "empresa" => $empresa,
        "draws" => $drawsOut,
        "completed" => count($drawsOut) >= count($premiosRows),
    ]);
}

// ─────────────────────────────────────────────────────────────────
// PÚBLICO: ejecutar un sorteo (CSPRNG en servidor, bloqueo de una sola vez)
// ─────────────────────────────────────────────────────────────────
if ($action === 'draw' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = $_POST['slug'] ?? '';
    $kit = (string) ($_POST['kit'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM concursos WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $concurso = $stmt->fetch();
    if (!$concurso) jsonOut(["status" => "error", "message" => "Concurso no encontrado."], 404);
    $concursoId = (int) $concurso['id'];

    $premioStmt = $pdo->prepare("SELECT * FROM concurso_premios WHERE concurso_id = ? AND kit = ?");
    $premioStmt->execute([$concursoId, $kit]);
    $premio = $premioStmt->fetch();
    if (!$premio) jsonOut(["status" => "error", "message" => "Premio inválido."], 400);

    // Bloqueo pesimista con transacción para evitar doble sorteo por carrera
    $pdo->beginTransaction();
    try {
        $pdo->prepare("SELECT id FROM concursos WHERE id = ? FOR UPDATE")->execute([$concursoId]);

        $already = $pdo->prepare("SELECT COUNT(*) FROM concurso_sorteos WHERE concurso_id = ? AND kit = ?");
        $already->execute([$concursoId, $kit]);
        if ((int) $already->fetchColumn() > 0) {
            $pdo->rollBack();
            jsonOut(["status" => "error", "message" => "Este premio ya fue sorteado."], 409);
        }

        // Orden secuencial de premios
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM concurso_sorteos WHERE concurso_id = ?");
        $countStmt->execute([$concursoId]);
        $doneCount = (int) $countStmt->fetchColumn();

        $ordenStmt = $pdo->prepare("SELECT kit FROM concurso_premios WHERE concurso_id = ? ORDER BY orden");
        $ordenStmt->execute([$concursoId]);
        $ordenKits = $ordenStmt->fetchAll(PDO::FETCH_COLUMN);
        if (($ordenKits[$doneCount] ?? null) !== $kit) {
            $pdo->rollBack();
            jsonOut(["status" => "error", "message" => "Debes sortear los premios en orden."], 409);
        }

        $winnerIdsStmt = $pdo->prepare("SELECT lead_id FROM concurso_sorteos WHERE concurso_id = ?");
        $winnerIdsStmt->execute([$concursoId]);
        $winnerIds = $winnerIdsStmt->fetchAll(PDO::FETCH_COLUMN);

        $placeholders = empty($winnerIds) ? '' : ' AND id NOT IN (' . implode(',', array_fill(0, count($winnerIds), '?')) . ')';
        $eligibleStmt = $pdo->prepare("SELECT id, nombre, apellido, documento FROM concurso_leads WHERE concurso_id = ?{$placeholders}");
        $eligibleStmt->execute(array_merge([$concursoId], $winnerIds));
        $eligible = $eligibleStmt->fetchAll();

        if (empty($eligible)) {
            $pdo->rollBack();
            jsonOut(["status" => "error", "message" => "No quedan participantes elegibles."], 409);
        }

        $winner = $eligible[random_int(0, count($eligible) - 1)];

        $insert = $pdo->prepare("INSERT INTO concurso_sorteos (concurso_id, kit, lead_id) VALUES (?, ?, ?)");
        $insert->execute([$concursoId, $kit, $winner['id']]);

        $pdo->commit();
        mkt_sync_concurso_metricas($pdo, $concursoId);

        $totalPremios = $pdo->prepare("SELECT COUNT(*) FROM concurso_premios WHERE concurso_id = ?");
        $totalPremios->execute([$concursoId]);

        jsonOut([
            "status" => "success",
            "kit" => $kit,
            "premio" => $premio['nombre'],
            "ganador" => fullName($winner),
            "completed" => ($doneCount + 1) >= (int) $totalPremios->fetchColumn(),
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

// ─────────────────────────────────────────────────────────────────
// A PARTIR DE AQUÍ: requiere sesión de administrador
// ─────────────────────────────────────────────────────────────────
mkt_require_auth();

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT c.id, c.nombre, c.slug, c.estado, c.created_at, c.empresa_id, c.dashboard_id,
                                 e.nombre AS empresa_nombre,
                                 d.titulo AS dashboard_titulo, d.periodo AS dashboard_periodo,
                                 (SELECT COUNT(*) FROM concurso_premios p WHERE p.concurso_id = c.id) AS total_premios,
                                 (SELECT COUNT(*) FROM concurso_sorteos s WHERE s.concurso_id = c.id) AS premios_sorteados,
                                 (SELECT COUNT(*) FROM concurso_leads l WHERE l.concurso_id = c.id) AS total_leads
                          FROM concursos c
                          JOIN empresas e ON e.id = c.empresa_id
                          LEFT JOIN dashboards d ON d.id = c.dashboard_id
                          ORDER BY c.created_at DESC");
    $rows = $stmt->fetchAll();
    jsonOut(["status" => "success", "concursos" => $rows]);
}

if ($action === 'empresas' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT id, nombre, slug FROM empresas WHERE activo = 1 ORDER BY nombre");
    jsonOut(["status" => "success", "empresas" => $stmt->fetchAll()]);
}

if ($action === 'dashboards_by_empresa' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $empresaId = (int) ($_GET['empresa_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, titulo, periodo, slug FROM dashboards WHERE empresa_id = ? ORDER BY fecha_inicio DESC, id DESC");
    $stmt->execute([$empresaId]);
    jsonOut(["status" => "success", "dashboards" => $stmt->fetchAll()]);
}

if ($action === 'asignar_dashboard' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $concursoId = (int) ($_POST['concurso_id'] ?? 0);
    $dashboardId = $_POST['dashboard_id'] ?? '';
    $dashboardId = $dashboardId === '' ? null : (int) $dashboardId;

    $prevStmt = $pdo->prepare("SELECT dashboard_id FROM concursos WHERE id = ?");
    $prevStmt->execute([$concursoId]);
    $prevDashboardId = $prevStmt->fetchColumn();

    $upd = $pdo->prepare("UPDATE concursos SET dashboard_id = ? WHERE id = ?");
    $upd->execute([$dashboardId, $concursoId]);

    if ($dashboardId) mkt_sync_concurso_metricas($pdo, $concursoId);

    // Si cambió de periodo, recalcular también el periodo anterior con los concursos que le queden
    if ($prevDashboardId && (int) $prevDashboardId !== $dashboardId) {
        $otherStmt = $pdo->prepare("SELECT id FROM concursos WHERE dashboard_id = ? LIMIT 1");
        $otherStmt->execute([$prevDashboardId]);
        $otherConcursoId = $otherStmt->fetchColumn();
        if ($otherConcursoId) {
            mkt_sync_concurso_metricas($pdo, (int) $otherConcursoId);
        } else {
            $pdo->prepare("DELETE FROM metricas_canal WHERE dashboard_id = ? AND canal = 'concursos'")->execute([$prevDashboardId]);
        }
    }

    jsonOut(["status" => "success"]);
}

if ($action === 'crear_concurso' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $empresaId = (int) ($_POST['empresa_id'] ?? 0);
    $dashboardId = ($_POST['dashboard_id'] ?? '') !== '' ? (int) $_POST['dashboard_id'] : null;
    $nombre = trim($_POST['nombre'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $metodologia = trim($_POST['metodologia'] ?? '');
    $claimHours = (float) ($_POST['claim_hours'] ?? 24);
    $premios = json_decode($_POST['premios'] ?? '[]', true);

    if (!$empresaId || !$nombre || !$slug || empty($premios)) {
        jsonOut(["status" => "error", "message" => "Faltan datos obligatorios (empresa, nombre, slug o premios)."], 400);
    }

    $webhookToken = bin2hex(random_bytes(20));

    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare("INSERT INTO concursos (empresa_id, dashboard_id, nombre, slug, metodologia, claim_hours, webhook_token, estado)
                               VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')");
        $ins->execute([$empresaId, $dashboardId, $nombre, $slug, $metodologia, $claimHours, $webhookToken]);
        $concursoId = (int) $pdo->lastInsertId();

        $orden = 1;
        $insPremio = $pdo->prepare("INSERT INTO concurso_premios (concurso_id, kit, nombre, detalle, orden) VALUES (?, ?, ?, ?, ?)");
        foreach ($premios as $p) {
            $insPremio->execute([$concursoId, (string) $orden, $p['nombre'] ?? ('Premio ' . $orden), $p['detalle'] ?? '', $orden]);
            $orden++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    jsonOut(["status" => "success", "id" => $concursoId, "slug" => $slug, "webhook_token" => $webhookToken]);
}

if ($action === 'leads' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $concursoId = (int) ($_GET['concurso_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT l.id, l.nombre, l.apellido, l.documento, l.telefono, l.correo, l.origen, l.created_at,
                                   (SELECT COUNT(*) FROM concurso_sorteos s
                                    JOIN concurso_leads l2 ON l2.id = s.lead_id
                                    WHERE l2.documento = l.documento) AS wins_totales
                            FROM concurso_leads l WHERE l.concurso_id = ? ORDER BY l.created_at DESC");
    $stmt->execute([$concursoId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) $row['wins_totales'] = (int) $row['wins_totales'];
    unset($row);
    jsonOut(["status" => "success", "leads" => $rows]);
}

if ($action === 'lead_manual' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $concursoId = (int) ($_POST['concurso_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    if (!$concursoId || !$nombre || !$documento) {
        jsonOut(["status" => "error", "message" => "Nombre y documento son obligatorios."], 400);
    }

    $stmt = $pdo->prepare("INSERT INTO concurso_leads (concurso_id, nombre, apellido, documento, telefono, correo, origen)
                            VALUES (?, ?, ?, ?, ?, ?, 'manual')
                            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido),
                                telefono = VALUES(telefono), correo = VALUES(correo)");
    $stmt->execute([$concursoId, $nombre, $apellido, $documento, $telefono, $correo]);
    mkt_sync_concurso_metricas($pdo, $concursoId);
    jsonOut(["status" => "success"]);
}

if ($action === 'auditoria' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $concursoId = (int) ($_GET['concurso_id'] ?? 0);

    $cStmt = $pdo->prepare("SELECT c.*, e.nombre AS empresa_nombre, d.titulo AS dashboard_titulo, d.periodo AS dashboard_periodo
                             FROM concursos c
                             JOIN empresas e ON e.id = c.empresa_id
                             LEFT JOIN dashboards d ON d.id = c.dashboard_id
                             WHERE c.id = ?");
    $cStmt->execute([$concursoId]);
    $concurso = $cStmt->fetch();
    if (!$concurso) jsonOut(["status" => "error", "message" => "Concurso no encontrado."], 404);

    $premiosStmt = $pdo->prepare("SELECT kit, nombre, detalle FROM concurso_premios WHERE concurso_id = ? ORDER BY orden");
    $premiosStmt->execute([$concursoId]);
    $premios = $premiosStmt->fetchAll();
    $premiosByKit = [];
    foreach ($premios as $p) $premiosByKit[$p['kit']] = $p;

    $drawsStmt = $pdo->prepare("SELECT s.kit, s.created_at, l.id AS lead_id, l.nombre, l.apellido, l.documento, l.telefono, l.correo,
                                       (SELECT COUNT(*) FROM concurso_sorteos s2
                                        JOIN concurso_leads l2 ON l2.id = s2.lead_id
                                        WHERE l2.documento = l.documento) AS wins_totales
                                 FROM concurso_sorteos s JOIN concurso_leads l ON l.id = s.lead_id
                                 WHERE s.concurso_id = ? ORDER BY s.created_at");
    $drawsStmt->execute([$concursoId]);
    $drawsRows = $drawsStmt->fetchAll();

    $claimHours = (float) $concurso['claim_hours'];
    $draws = [];
    foreach ($drawsRows as $d) {
        $drawTime = strtotime($d['created_at']);
        $deadline = $drawTime + (int) round($claimHours * 3600);

        $backupsStmt = $pdo->prepare("SELECT b.created_at, l.nombre, l.apellido, l.documento, l.telefono, l.correo
                                       FROM concurso_suplentes b JOIN concurso_leads l ON l.id = b.lead_id
                                       WHERE b.concurso_id = ? AND b.kit = ? ORDER BY b.created_at");
        $backupsStmt->execute([$concursoId, $d['kit']]);
        $backups = array_map(function ($b) {
            return [
                "nombre" => trim($b['nombre'] . ' ' . $b['apellido']),
                "documento" => $b['documento'], "telefono" => $b['telefono'], "correo" => $b['correo'],
                "fecha" => $b['created_at'],
            ];
        }, $backupsStmt->fetchAll());

        $draws[] = [
            "kit" => $d['kit'],
            "premio" => $premiosByKit[$d['kit']] ?? null,
            "ganador" => [
                "nombre" => trim($d['nombre'] . ' ' . $d['apellido']),
                "documento" => $d['documento'], "telefono" => $d['telefono'], "correo" => $d['correo'],
                "wins_totales" => (int) $d['wins_totales'],
            ],
            "fecha_sorteo" => $d['created_at'],
            "deadline_reclamo" => date('c', $deadline),
            "claim_hours" => $claimHours,
            "backups" => $backups,
        ];
    }

    $totalLeadsStmt = $pdo->prepare("SELECT COUNT(*) FROM concurso_leads WHERE concurso_id = ?");
    $totalLeadsStmt->execute([$concursoId]);

    jsonOut([
        "status" => "success",
        "concurso" => [
            "id" => (int) $concurso['id'],
            "nombre" => $concurso['nombre'],
            "slug" => $concurso['slug'],
            "metodologia" => $concurso['metodologia'],
            "claim_hours" => $claimHours,
            "premios" => $premios,
            "public_url" => "/concursos/index.html?slug=" . urlencode($concurso['slug']),
            "webhook_token" => $concurso['webhook_token'],
            "empresa_id" => (int) $concurso['empresa_id'],
            "empresa_nombre" => $concurso['empresa_nombre'],
            "dashboard_id" => $concurso['dashboard_id'] ? (int) $concurso['dashboard_id'] : null,
            "dashboard_periodo" => $concurso['dashboard_periodo'],
        ],
        "draws" => $draws,
        "total_leads" => (int) $totalLeadsStmt->fetchColumn(),
    ]);
}

if ($action === 'pick_backup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $concursoId = (int) ($_POST['concurso_id'] ?? 0);
    $kit = (string) ($_POST['kit'] ?? '');

    $pdo->beginTransaction();
    try {
        $drawCheck = $pdo->prepare("SELECT COUNT(*) FROM concurso_sorteos WHERE concurso_id = ? AND kit = ?");
        $drawCheck->execute([$concursoId, $kit]);
        if ((int) $drawCheck->fetchColumn() === 0) {
            $pdo->rollBack();
            jsonOut(["status" => "error", "message" => "Ese premio aún no ha sido sorteado."], 409);
        }

        $excludeStmt = $pdo->prepare("SELECT lead_id FROM concurso_sorteos WHERE concurso_id = ?
                                       UNION SELECT lead_id FROM concurso_suplentes WHERE concurso_id = ?");
        $excludeStmt->execute([$concursoId, $concursoId]);
        $exclude = $excludeStmt->fetchAll(PDO::FETCH_COLUMN);

        $placeholders = empty($exclude) ? '' : ' AND id NOT IN (' . implode(',', array_fill(0, count($exclude), '?')) . ')';
        $eligibleStmt = $pdo->prepare("SELECT id, nombre, apellido, documento, telefono, correo FROM concurso_leads WHERE concurso_id = ?{$placeholders}");
        $eligibleStmt->execute(array_merge([$concursoId], $exclude));
        $eligible = $eligibleStmt->fetchAll();

        if (empty($eligible)) {
            $pdo->rollBack();
            jsonOut(["status" => "error", "message" => "No quedan participantes elegibles para suplente."], 409);
        }

        $chosen = $eligible[random_int(0, count($eligible) - 1)];
        $ins = $pdo->prepare("INSERT INTO concurso_suplentes (concurso_id, kit, lead_id) VALUES (?, ?, ?)");
        $ins->execute([$concursoId, $kit, $chosen['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    jsonOut(["status" => "success", "kit" => $kit, "suplente" => [
        "nombre" => fullName($chosen), "documento" => $chosen['documento'],
        "telefono" => $chosen['telefono'], "correo" => $chosen['correo'],
    ]]);
}

if ($action === 'registrar_ganador_manual' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Para mecánicas oficiales externas (ej. sorteo por comentarios de Instagram/Facebook):
    // el equipo de marketing registra aquí al ganador ya anunciado en redes, para que quede
    // en la auditoría, el conteo de premios entregados y el PDF, aunque la selección aleatoria
    // no haya ocurrido dentro de este sistema.
    $concursoId = (int) ($_POST['concurso_id'] ?? 0);
    $kit = (string) ($_POST['kit'] ?? '');
    $documento = trim($_POST['documento'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    if (!$concursoId || !$kit || !$documento || !$nombre) {
        jsonOut(["status" => "error", "message" => "Concurso, premio, nombre y documento son obligatorios."], 400);
    }

    $premioStmt = $pdo->prepare("SELECT id FROM concurso_premios WHERE concurso_id = ? AND kit = ?");
    $premioStmt->execute([$concursoId, $kit]);
    if (!$premioStmt->fetch()) jsonOut(["status" => "error", "message" => "Premio inválido."], 400);

    $pdo->beginTransaction();
    try {
        $already = $pdo->prepare("SELECT COUNT(*) FROM concurso_sorteos WHERE concurso_id = ? AND kit = ?");
        $already->execute([$concursoId, $kit]);
        if ((int) $already->fetchColumn() > 0) {
            $pdo->rollBack();
            jsonOut(["status" => "error", "message" => "Ese premio ya tiene un ganador registrado."], 409);
        }

        $upsertLead = $pdo->prepare("INSERT INTO concurso_leads (concurso_id, nombre, apellido, documento, telefono, correo, origen)
                                      VALUES (?, ?, ?, ?, ?, ?, 'manual')
                                      ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido),
                                          telefono = COALESCE(NULLIF(VALUES(telefono), ''), telefono),
                                          correo = COALESCE(NULLIF(VALUES(correo), ''), correo)");
        $upsertLead->execute([$concursoId, $nombre, $apellido, $documento, $telefono, $correo ?: null]);

        $leadStmt = $pdo->prepare("SELECT id FROM concurso_leads WHERE concurso_id = ? AND documento = ?");
        $leadStmt->execute([$concursoId, $documento]);
        $leadId = $leadStmt->fetchColumn();

        $ins = $pdo->prepare("INSERT INTO concurso_sorteos (concurso_id, kit, lead_id) VALUES (?, ?, ?)");
        $ins->execute([$concursoId, $kit, $leadId]);

        $pdo->commit();
        mkt_sync_concurso_metricas($pdo, $concursoId);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }

    jsonOut([
        "status" => "success",
        "kit" => $kit,
        "ganador" => trim("$nombre $apellido"),
        "wins_totales" => mkt_wins_totales($pdo, $documento),
    ]);
}

if ($action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $concursoId = (int) ($_POST['concurso_id'] ?? 0);
    $pdo->prepare("DELETE FROM concurso_suplentes WHERE concurso_id = ?")->execute([$concursoId]);
    $pdo->prepare("DELETE FROM concurso_sorteos WHERE concurso_id = ?")->execute([$concursoId]);
    jsonOut(["status" => "success", "message" => "El sorteo fue reiniciado."]);
}

jsonOut(["status" => "error", "message" => "Acción no reconocida."], 400);

} catch (PDOException $e) {
    error_log('[Concursos API] DB Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error de base de datos."], 500);
} catch (Throwable $e) {
    error_log('[Concursos API] Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error interno del servidor."], 500);
}
