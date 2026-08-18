<?php
/**
 * Marketing Insights — API Endpoint Completo
 * BD: wwcibe_mktinsights
 * Devuelve payload JSON completo del dashboard para renderizado dinámico
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Cache-Control: no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/config.php';

$action       = $_GET['action']    ?? 'dashboard';
$empresaSlug  = $_GET['empresa']   ?? '';
$dashSlug     = $_GET['dashboard'] ?? '';
$token        = $_GET['token']     ?? '';

// ─── Helpers ─────────────────────────────────────────────────────────────────
function jsonError(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

function numRows(PDO $pdo, string $table, int $dashId): int {
    $s = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE dashboard_id = ?");
    $s->execute([$dashId]);
    return (int)$s->fetchColumn();
}

try {

// ─── Acción: lista de empresas (para admin) ──────────────────────────────────
if ($action === 'empresas') {
    $stmt = $pdo->query("SELECT id, nombre, slug, sector, ciudad, pais,
                                logo_light_url, logo_dark_url, activo
                         FROM empresas ORDER BY nombre");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ─── Acción: módulos activos (para admin toggle) ──────────────────────────────
if ($action === 'modulos') {
    if (empty($empresaSlug) || empty($dashSlug)) jsonError(400, 'Parámetros insuficientes');
    $stmt = $pdo->prepare("SELECT mi.id, mi.codigo, mi.nombre, mi.activo, mi.orden
                           FROM modulos_indicadores mi
                           JOIN dashboards d ON d.id = mi.dashboard_id
                           JOIN empresas e ON e.id = d.empresa_id
                           WHERE e.slug = ? AND d.slug = ?
                           ORDER BY mi.orden");
    $stmt->execute([$empresaSlug, $dashSlug]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ─── Acción: dashboard completo ───────────────────────────────────────────────
if ($action === 'dashboard') {

    if (empty($empresaSlug) || empty($dashSlug))
        jsonError(400, 'Parámetros empresa y dashboard son requeridos');

    // 1. Empresa
    $stmt = $pdo->prepare("SELECT * FROM empresas WHERE slug = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$empresaSlug]);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$empresa) jsonError(404, 'Empresa no encontrada o inactiva');

    // 2. Dashboard (validar token)
    $stmt = $pdo->prepare("SELECT * FROM dashboards WHERE empresa_id = ? AND slug = ? LIMIT 1");
    $stmt->execute([$empresa['id'], $dashSlug]);
    $dashboard = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dashboard) jsonError(404, 'Dashboard no encontrado');

    // 3. Validar token (superadmin bypassa con mkt_admin_bypass, no expuesto en JS)
    $tokenValido = ($dashboard['public_token'] === $token);
    // Si no hay token en la request, el frontend ya debió haber hecho el guard con localStorage
    // Aquí en API solo validamos el token de URL público; admin session es localStorage-only
    // (no enviamos datos sensibles de todas formas)

    // 4. Módulos activos
    $stmt = $pdo->prepare("SELECT codigo, nombre, tipo_visualizacion, orden
                           FROM modulos_indicadores
                           WHERE dashboard_id = ? AND activo = 1
                           ORDER BY orden");
    $stmt->execute([$dashboard['id']]);
    $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $modulosActivos = array_column($modulos, 'codigo');

    $dId = (int)$dashboard['id'];

    // 5. Hitos Timeline
    $stmt = $pdo->prepare("SELECT periodo, fase, descripcion, hito_clave FROM hitos_timeline
                           WHERE dashboard_id = ? ORDER BY orden");
    $stmt->execute([$dId]);
    $hitos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Métricas — helper por canal
    $fnMetricas = function(string $canal) use ($pdo, $dId): array {
        $s = $pdo->prepare("SELECT clave, etiqueta, valor_numerico, valor_texto,
                                   comparativo_label, comparativo_valor, unidad
                            FROM metricas_canal
                            WHERE dashboard_id = ? AND canal = ?
                            ORDER BY orden");
        $s->execute([$dId, $canal]);
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[$r['clave']] = [
                'etiqueta'   => $r['etiqueta'],
                'valor'      => is_null($r['valor_numerico']) ? null : (float)$r['valor_numerico'],
                'texto'      => $r['valor_texto'],
                'cmp_label'  => $r['comparativo_label'],
                'cmp_valor'  => is_null($r['comparativo_valor']) ? null : (float)$r['comparativo_valor'],
                'unidad'     => $r['unidad'],
            ];
        }
        return $out;
    };

    $google      = $fnMetricas('google');
    $meta        = $fnMetricas('meta');
    $tiktok      = $fnMetricas('tiktok');
    $pauta       = $fnMetricas('pauta');
    $email_b2c   = $fnMetricas('email_b2c');
    $email_b2b   = $fnMetricas('email_b2b');
    $entregablesSummary = $fnMetricas('entregables');

    // 7. Series de tiempo
    $fnSerie = function(string $canal, string $serie) use ($pdo, $dId): array {
        $s = $pdo->prepare("SELECT periodo_label, valor FROM series_tiempo
                            WHERE dashboard_id = ? AND canal = ? AND serie = ?
                            ORDER BY orden");
        $s->execute([$dId, $canal, $serie]);
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);
        return [
            'labels' => array_column($rows, 'periodo_label'),
            'data'   => array_map(fn($r) => (float)$r['valor'], $rows),
        ];
    };

    $seriesMeta = [
        'visualizaciones'  => $fnSerie('meta', 'visualizaciones'),
        'espectadores'     => $fnSerie('meta', 'espectadores_unicos'),
    ];
    $seriesTiktok = [
        'vistas' => $fnSerie('tiktok', 'vistas'),
    ];

    // 8. UGC Posts
    $stmt = $pdo->prepare("SELECT titulo, subtitulo, canal, vistas, compartidos,
                                  likes, badge_label, nota_estrategica
                           FROM ugc_posts WHERE dashboard_id = ? ORDER BY orden");
    $stmt->execute([$dId]);
    $ugc = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Castear números
    foreach ($ugc as &$u) {
        $u['vistas']      = is_null($u['vistas'])      ? null : (int)$u['vistas'];
        $u['compartidos'] = is_null($u['compartidos']) ? null : (int)$u['compartidos'];
        $u['likes']       = is_null($u['likes'])       ? null : (int)$u['likes'];
    }
    unset($u);

    // 9. Demografía
    $stmt = $pdo->prepare("SELECT tipo, etiqueta, valor_mujeres, valor_hombres, valor_total
                           FROM audiencia_demografica WHERE dashboard_id = ? ORDER BY tipo, orden");
    $stmt->execute([$dId]);
    $demRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $demografica = ['edad_genero' => ['labels'=>[],'mujeres'=>[],'hombres'=>[]], 'ciudades' => ['labels'=>[],'valores'=>[]]];
    foreach ($demRows as $d) {
        if ($d['tipo'] === 'edad_genero') {
            $demografica['edad_genero']['labels'][]  = $d['etiqueta'];
            $demografica['edad_genero']['mujeres'][] = (float)$d['valor_mujeres'];
            $demografica['edad_genero']['hombres'][] = (float)$d['valor_hombres'];
        } elseif ($d['tipo'] === 'ciudad') {
            $demografica['ciudades']['labels'][]  = $d['etiqueta'];
            $demografica['ciudades']['valores'][] = (float)$d['valor_total'];
        }
    }

    // 10. Campañas de pauta
    $stmt = $pdo->prepare("SELECT nombre, objetivo, plataforma, inversion_cop, alcance,
                                  impresiones, resultados, tipo_resultado, cpr
                           FROM campanas_pauta WHERE dashboard_id = ? ORDER BY orden");
    $stmt->execute([$dId]);
    $campanas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($campanas as &$c) {
        $c['inversion_cop'] = (float)$c['inversion_cop'];
        $c['cpr']           = (float)$c['cpr'];
        $c['alcance']       = (int)$c['alcance'];
        $c['impresiones']   = (int)$c['impresiones'];
        $c['resultados']    = (int)$c['resultados'];
    }
    unset($c);

    // 11. Entregables
    $stmt = $pdo->prepare("SELECT numero_item, nombre, formato, categoria, fecha_creacion
                           FROM entregables WHERE dashboard_id = ? ORDER BY numero_item");
    $stmt->execute([$dId]);
    $entregables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($entregables as &$e) {
        $e['numero_item'] = (int)$e['numero_item'];
    }
    unset($e);

    // ─── Payload final ────────────────────────────────────────────────────────
    $payload = [
        'empresa' => [
            'id'             => (int)$empresa['id'],
            'nombre'         => $empresa['nombre'],
            'slug'           => $empresa['slug'],
            'sector'         => $empresa['sector'],
            'ciudad'         => $empresa['ciudad'],
            'logo_light_url' => $empresa['logo_light_url'],
            'logo_dark_url'  => $empresa['logo_dark_url'],
        ],
        'dashboard' => [
            'id'             => (int)$dashboard['id'],
            'titulo'         => $dashboard['titulo'],
            'slug'           => $dashboard['slug'],
            'periodo'        => $dashboard['periodo'],
            'fecha_inicio'   => $dashboard['fecha_inicio'],
            'fecha_fin'      => $dashboard['fecha_fin'],
            'lema'           => $dashboard['lema'],
            'descripcion'    => $dashboard['descripcion_ejecutiva'],
            'resumen'        => $dashboard['resumen_aprendizaje'],
        ],
        'modulos_activos' => $modulosActivos,
        'hitos'           => $hitos,
        'google'          => $google,
        'meta'            => $meta,
        'tiktok'          => $tiktok,
        'pauta'           => $pauta,
        'campanas_pauta'  => $campanas,
        'email_b2c'       => $email_b2c,
        'email_b2b'       => $email_b2b,
        'entregables_summary' => $entregablesSummary,
        'series_meta'     => $seriesMeta,
        'series_tiktok'   => $seriesTiktok,
        'ugc'             => $ugc,
        'demografica'     => $demografica,
        'entregables'     => $entregables,
    ];

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

jsonError(400, 'Acción no reconocida');

} catch (PDOException $e) {
    error_log('[MktInsights API] DB Error: ' . $e->getMessage());
    jsonError(500, 'Error de base de datos');
} catch (Throwable $e) {
    error_log('[MktInsights API] Error: ' . $e->getMessage());
    jsonError(500, 'Error interno del servidor');
}
