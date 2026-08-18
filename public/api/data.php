<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? 'dashboard';
$empresaSlug = $_GET['empresa'] ?? 'cine-multiplex-villacentro';
$dashboardSlug = $_GET['dashboard'] ?? 'julio-2026';
$token = $_GET['token'] ?? '';

try {
    if ($action === 'empresas') {
        $stmt = $pdo->query("SELECT id, nombre, slug, sector, ciudad, pais, logo_light_url, logo_dark_url, activo FROM empresas WHERE activo = 1");
        echo json_encode($stmt->fetchAll());
        exit;
    }

    if ($action === 'dashboard') {
        // Consultar empresa
        $stmtEmpresa = $pdo->prepare("SELECT * FROM empresas WHERE slug = ? AND activo = 1");
        $stmtEmpresa->execute([$empresaSlug]);
        $empresa = $stmtEmpresa->fetch();

        if (!$empresa) {
            http_response_code(404);
            echo json_encode(['error' => 'Empresa no encontrada']);
            exit;
        }

        // Consultar dashboard
        $stmtDash = $pdo->prepare("SELECT * FROM dashboards WHERE empresa_id = ? AND slug = ?");
        $stmtDash->execute([$empresa['id'], $dashboardSlug]);
        $dashboard = $stmtDash->fetch();

        if (!$dashboard) {
            http_response_code(404);
            echo json_encode(['error' => 'Dashboard no encontrado']);
            exit;
        }

        // Consultar módulos
        $stmtMod = $pdo->prepare("SELECT * FROM modulos_indicadores WHERE dashboard_id = ? AND activo = 1 ORDER BY orden ASC");
        $stmtMod->execute([$dashboard['id']]);
        $modulos = $stmtMod->fetchAll();

        echo json_encode([
            'empresa' => $empresa,
            'dashboard' => $dashboard,
            'modulos' => $modulos
        ]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
