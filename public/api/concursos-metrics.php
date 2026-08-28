<?php
// Sincroniza los KPIs agregados de Concursos & Sorteos hacia metricas_canal
// para que aparezcan como tarjeta en el informe mensual del cliente (data.php).
// Solo sincroniza si el concurso tiene un dashboard_id asignado explícitamente
// (nunca adivina el periodo "más reciente": eso mezclaría datos de meses distintos).

function mkt_sync_concurso_metricas(PDO $pdo, int $concursoId): void {
    $cStmt = $pdo->prepare("SELECT empresa_id, dashboard_id FROM concursos WHERE id = ?");
    $cStmt->execute([$concursoId]);
    $concurso = $cStmt->fetch();
    if (!$concurso || !$concurso['dashboard_id']) return;

    $dashboardId = (int) $concurso['dashboard_id'];
    $empresaId = (int) $concurso['empresa_id'];

    $leadsStmt = $pdo->prepare("SELECT COUNT(*) FROM concurso_leads WHERE concurso_id IN
                                 (SELECT id FROM concursos WHERE dashboard_id = ?)");
    $leadsStmt->execute([$dashboardId]);
    $totalLeads = (int) $leadsStmt->fetchColumn();

    $premiosStmt = $pdo->prepare("SELECT COUNT(*) FROM concurso_sorteos s JOIN concursos c ON c.id = s.concurso_id
                                   WHERE c.dashboard_id = ?");
    $premiosStmt->execute([$dashboardId]);
    $totalPremios = (int) $premiosStmt->fetchColumn();

    $activosStmt = $pdo->prepare("SELECT COUNT(*) FROM concursos WHERE dashboard_id = ? AND estado = 'activo'");
    $activosStmt->execute([$dashboardId]);
    $totalActivos = (int) $activosStmt->fetchColumn();

    $upsert = $pdo->prepare("INSERT INTO metricas_canal (dashboard_id, canal, clave, etiqueta, valor_numerico, orden)
                              VALUES (?, 'concursos', ?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE valor_numerico = VALUES(valor_numerico)");
    $upsert->execute([$dashboardId, 'leads_captados', 'Leads Captados', $totalLeads, 1]);
    $upsert->execute([$dashboardId, 'premios_entregados', 'Premios Entregados', $totalPremios, 2]);
    $upsert->execute([$dashboardId, 'concursos_activos', 'Concursos Activos', $totalActivos, 3]);
}
