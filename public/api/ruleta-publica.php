<?php
// API pública del kiosko de la Ruleta (taquilla/cajero). Sin autenticación de
// panel: el acceso está controlado por el token fijo de la estación (parte
// de la URL que el cine deja abierta en el punto de venta). El resultado del
// giro SIEMPRE se calcula en el servidor con un generador aleatorio seguro,
// nunca en el navegador — así ningún cliente puede forzar el premio.

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (function_exists('uopz_allow_exit')) { uopz_allow_exit(true); }

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ruleta-metrics.php';

function jsonOut($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function mkt_estacion_por_token(PDO $pdo, string $token): ?array {
    $stmt = $pdo->prepare("SELECT e.id AS estacion_id, e.nombre AS estacion_nombre, e.tipo, e.ancho_ticket, e.activa AS estacion_activa,
                                   r.id AS ruleta_id, r.nombre AS ruleta_nombre, r.titulo_publico, r.duracion_segundos, r.imagen_fondo_url, r.imagen_centro_url, r.activa AS ruleta_activa,
                                   emp.id AS empresa_id, emp.nombre AS empresa_nombre, emp.logo_light_url
                            FROM ruleta_estaciones e
                            JOIN ruletas r ON r.id = e.ruleta_id
                            JOIN empresas emp ON emp.id = r.empresa_id
                            WHERE e.token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {

if ($action === 'detalle' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = trim($_GET['token'] ?? '');
    if (!$token) jsonOut(["status" => "error", "message" => "Falta el token de la estación."], 400);

    $info = mkt_estacion_por_token($pdo, $token);
    if (!$info) jsonOut(["status" => "error", "message" => "Estación no encontrada."], 404);
    if (!$info['estacion_activa'] || !$info['ruleta_activa']) {
        jsonOut(["status" => "error", "message" => "Esta ruleta está desactivada temporalmente."], 403);
    }

    $premiosStmt = $pdo->prepare("SELECT id, nombre, color, probabilidad, es_perdedor, orden FROM ruleta_premios WHERE ruleta_id = ? ORDER BY orden, id");
    $premiosStmt->execute([$info['ruleta_id']]);
    $premios = $premiosStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($premios as &$p) {
        $p['id'] = (int) $p['id'];
        $p['probabilidad'] = (float) $p['probabilidad'];
        $p['es_perdedor'] = (bool) $p['es_perdedor'];
    }
    unset($p);

    if (empty($premios)) jsonOut(["status" => "error", "message" => "Esta ruleta todavía no tiene premios configurados."], 409);

    jsonOut([
        "status" => "success",
        "ruleta" => [
            "id" => (int) $info['ruleta_id'],
            "nombre" => $info['ruleta_nombre'],
            "titulo_publico" => $info['titulo_publico'],
            "duracion_segundos" => (float) $info['duracion_segundos'],
            "imagen_fondo_url" => $info['imagen_fondo_url'],
            "imagen_centro_url" => $info['imagen_centro_url'],
        ],
        "empresa" => [
            "nombre" => $info['empresa_nombre'],
            "logo_url" => $info['logo_light_url'],
        ],
        "estacion" => [
            "nombre" => $info['estacion_nombre'],
            "tipo" => $info['tipo'],
            "ancho_ticket" => $info['ancho_ticket'],
        ],
        "premios" => $premios,
    ]);
}

if ($action === 'girar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    if (!$token) jsonOut(["status" => "error", "message" => "Falta el token de la estación."], 400);

    $info = mkt_estacion_por_token($pdo, $token);
    if (!$info) jsonOut(["status" => "error", "message" => "Estación no encontrada."], 404);
    if (!$info['estacion_activa'] || !$info['ruleta_activa']) {
        jsonOut(["status" => "error", "message" => "Esta ruleta está desactivada temporalmente."], 403);
    }

    $premiosStmt = $pdo->prepare("SELECT id, nombre, color, probabilidad, es_perdedor FROM ruleta_premios WHERE ruleta_id = ? ORDER BY orden, id");
    $premiosStmt->execute([$info['ruleta_id']]);
    $premios = $premiosStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($premios)) jsonOut(["status" => "error", "message" => "Esta ruleta todavía no tiene premios configurados."], 409);

    // Selección aleatoria ponderada por probabilidad, con random_int() (CSPRNG del SO).
    // Se ejecuta exclusivamente en el servidor: el cliente solo recibe el resultado ya decidido.
    $pesoTotal = array_sum(array_map(fn($p) => (float) $p['probabilidad'], $premios));
    if ($pesoTotal <= 0) {
        $ganador = $premios[array_rand($premios)];
    } else {
        $tiro = random_int(1, (int) round($pesoTotal * 1000)) / 1000;
        $acumulado = 0;
        $ganador = $premios[count($premios) - 1];
        foreach ($premios as $p) {
            $acumulado += (float) $p['probabilidad'];
            if ($tiro <= $acumulado) { $ganador = $p; break; }
        }
    }

    $ins = $pdo->prepare("INSERT INTO ruleta_giros (ruleta_id, estacion_id, premio_id) VALUES (?, ?, ?)");
    $ins->execute([$info['ruleta_id'], $info['estacion_id'], $ganador['id']]);
    $giroId = (int) $pdo->lastInsertId();

    mkt_sync_ruleta_metricas($pdo, (int) $info['empresa_id']);

    jsonOut([
        "status" => "success",
        "giro_id" => $giroId,
        "premio" => [
            "id" => (int) $ganador['id'],
            "nombre" => $ganador['nombre'],
            "color" => $ganador['color'],
            "es_perdedor" => (bool) $ganador['es_perdedor'],
        ],
    ]);
}

if ($action === 'ticket' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = trim($_GET['token'] ?? '');
    $giroId = (int) ($_GET['giro_id'] ?? 0);
    if (!$token || !$giroId) jsonOut(["status" => "error", "message" => "Datos incompletos."], 400);

    $info = mkt_estacion_por_token($pdo, $token);
    if (!$info) jsonOut(["status" => "error", "message" => "Estación no encontrada."], 404);

    $stmt = $pdo->prepare("SELECT g.id, g.fecha_hora, p.nombre AS premio_nombre, p.es_perdedor
                            FROM ruleta_giros g JOIN ruleta_premios p ON p.id = g.premio_id
                            WHERE g.id = ? AND g.estacion_id = ?");
    $stmt->execute([$giroId, $info['estacion_id']]);
    $giro = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$giro) jsonOut(["status" => "error", "message" => "Giro no encontrado."], 404);
    if ((int) $giro['es_perdedor'] === 1) {
        jsonOut(["status" => "error", "message" => "Este giro no tuvo premio; no hay ficha para imprimir."], 409);
    }

    $pdo->prepare("UPDATE ruleta_giros SET ticket_impreso = 1 WHERE id = ?")->execute([$giroId]);

    $fecha = new DateTime($giro['fecha_hora']);
    jsonOut([
        "status" => "success",
        "giro_id" => (int) $giro['id'],
        "premio_nombre" => $giro['premio_nombre'],
        "fecha" => $fecha->format('d/m/Y'),
        "hora" => $fecha->format('H:i:s'),
        "empresa_nombre" => $info['empresa_nombre'],
        "logo_url" => $info['logo_light_url'],
        "estacion_nombre" => $info['estacion_nombre'],
        "estacion_tipo" => $info['tipo'],
        "ancho_ticket" => $info['ancho_ticket'],
    ]);
}

jsonOut(["status" => "error", "message" => "Acción no reconocida."], 400);

} catch (PDOException $e) {
    error_log('[RuletaPublica API] DB Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error de base de datos."], 500);
} catch (Throwable $e) {
    error_log('[RuletaPublica API] Error: ' . $e->getMessage());
    jsonOut(["status" => "error", "message" => "Error interno del servidor."], 500);
}
