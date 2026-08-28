<?php
// Sincroniza los KPIs agregados de Concursos & Sorteos hacia metricas_canal
// para que aparezcan como tarjeta en el informe mensual del cliente (data.php).

function mkt_sync_concurso_metricas(PDO $pdo, int $concursoId): void {
    $empStmt = $pdo->prepare("SELECT empresa_id FROM concursos WHERE id = ?");
    $empStmt->execute([$concursoId]);
    $empresaId = $empStmt->fetchColumn();
    if (!$empresaId) return;

    // Dashboard vigente de la empresa (el periodo más reciente por fecha, no por id)
    $dashStmt = $pdo->prepare("SELECT id FROM dashboards WHERE empresa_id = ? ORDER BY fecha_inicio DESC, id DESC LIMIT 1");
    $dashStmt->execute([$empresaId]);
    $dashboardId = $dashStmt->fetchColumn();
    if (!$dashboardId) return;

    $leadsStmt = $pdo->prepare("SELECT COUNT(*) FROM concurso_leads WHERE concurso_id IN (SELECT id FROM concursos WHERE empresa_id = ?)");
    $leadsStmt->execute([$empresaId]);
    $totalLeads = (int) $leadsStmt->fetchColumn();

    $premiosStmt = $pdo->prepare("SELECT COUNT(*) FROM concurso_sorteos s JOIN concursos c ON c.id = s.concurso_id WHERE c.empresa_id = ?");
    $premiosStmt->execute([$empresaId]);
    $totalPremios = (int) $premiosStmt->fetchColumn();

    $activosStmt = $pdo->prepare("SELECT COUNT(*) FROM concursos WHERE empresa_id = ? AND estado = 'activo'");
    $activosStmt->execute([$empresaId]);
    $totalActivos = (int) $activosStmt->fetchColumn();

    $upsert = $pdo->prepare("INSERT INTO metricas_canal (dashboard_id, canal, clave, etiqueta, valor_numerico, orden)
                              VALUES (?, 'concursos', ?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE valor_numerico = VALUES(valor_numerico)");
    $upsert->execute([$dashboardId, 'leads_captados', 'Leads Captados', $totalLeads, 1]);
    $upsert->execute([$dashboardId, 'premios_entregados', 'Premios Entregados', $totalPremios, 2]);
    $upsert->execute([$dashboardId, 'concursos_activos', 'Concursos Activos', $totalActivos, 3]);
}
