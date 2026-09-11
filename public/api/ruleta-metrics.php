<?php
// Sincroniza los KPIs agregados de la Ruleta de Concursos hacia metricas_canal
// para que aparezcan como sección de analítica en el informe mensual del cliente
// (data.php). La ruleta pertenece a la empresa (no a un dashboard específico,
// porque corre de forma continua), así que se recalculan los giros dentro del
// rango de fechas (fecha_inicio/fecha_fin) de cada dashboard de esa empresa.

function mkt_sync_ruleta_metricas(PDO $pdo, int $empresaId): void {
    $dashStmt = $pdo->prepare("SELECT id, fecha_inicio, fecha_fin FROM dashboards
                                WHERE empresa_id = ? AND fecha_inicio IS NOT NULL AND fecha_fin IS NOT NULL");
    $dashStmt->execute([$empresaId]);
    $dashboards = $dashStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dashboards as $dash) {
        $dashboardId = (int) $dash['id'];
        $desde = $dash['fecha_inicio'] . ' 00:00:00';
        $hasta = $dash['fecha_fin'] . ' 23:59:59';

        $baseSql = "FROM ruleta_giros g
                     JOIN ruletas r ON r.id = g.ruleta_id
                     JOIN ruleta_estaciones e ON e.id = g.estacion_id
                     JOIN ruleta_premios p ON p.id = g.premio_id
                     WHERE r.empresa_id = ? AND g.fecha_hora BETWEEN ? AND ?";

        $totalStmt = $pdo->prepare("SELECT COUNT(*) $baseSql");
        $totalStmt->execute([$empresaId, $desde, $hasta]);
        $totalGiros = (int) $totalStmt->fetchColumn();

        $taquillaStmt = $pdo->prepare("SELECT COUNT(*) $baseSql AND e.tipo = 'taquilla'");
        $taquillaStmt->execute([$empresaId, $desde, $hasta]);
        $girosTaquilla = (int) $taquillaStmt->fetchColumn();

        $cajeroStmt = $pdo->prepare("SELECT COUNT(*) $baseSql AND e.tipo = 'cajero'");
        $cajeroStmt->execute([$empresaId, $desde, $hasta]);
        $girosCajero = (int) $cajeroStmt->fetchColumn();

        $premiosStmt = $pdo->prepare("SELECT COUNT(*) $baseSql AND p.es_perdedor = 0");
        $premiosStmt->execute([$empresaId, $desde, $hasta]);
        $premiosEntregados = (int) $premiosStmt->fetchColumn();

        $horaStmt = $pdo->prepare("SELECT HOUR(g.fecha_hora) AS hora, COUNT(*) AS total $baseSql
                                    GROUP BY hora ORDER BY total DESC LIMIT 1");
        $horaStmt->execute([$empresaId, $desde, $hasta]);
        $horaRow = $horaStmt->fetch(PDO::FETCH_ASSOC);
        $horaPico = $horaRow ? sprintf('%02d:00 - %02d:00', (int) $horaRow['hora'], ((int) $horaRow['hora'] + 1) % 24) : '—';

        $topStmt = $pdo->prepare("SELECT p.nombre AS nombre, COUNT(*) AS total $baseSql AND p.es_perdedor = 0
                                   GROUP BY p.id, p.nombre ORDER BY total DESC LIMIT 10");
        $topStmt->execute([$empresaId, $desde, $hasta]);
        $topPremios = $topStmt->fetchAll(PDO::FETCH_ASSOC);

        $upsert = $pdo->prepare("INSERT INTO metricas_canal (dashboard_id, canal, clave, etiqueta, valor_numerico, valor_texto, orden)
                                  VALUES (?, 'ruleta', ?, ?, ?, ?, ?)
                                  ON DUPLICATE KEY UPDATE valor_numerico = VALUES(valor_numerico), valor_texto = VALUES(valor_texto), etiqueta = VALUES(etiqueta)");
        $upsert->execute([$dashboardId, 'total_giros', 'Giros Totales', $totalGiros, null, 1]);
        $upsert->execute([$dashboardId, 'giros_taquilla', 'Giros por Taquilla', $girosTaquilla, null, 2]);
        $upsert->execute([$dashboardId, 'giros_cajero', 'Giros por Cajero', $girosCajero, null, 3]);
        $upsert->execute([$dashboardId, 'premios_entregados', 'Premios Entregados', $premiosEntregados, null, 4]);
        $upsert->execute([$dashboardId, 'hora_pico', 'Hora de Mayor Incidencia', null, $horaPico, 5]);

        // Limpia el top-10 previo antes de reinsertar (puede haber menos de 10 premios distintos este periodo)
        $del = $pdo->prepare("DELETE FROM metricas_canal WHERE dashboard_id = ? AND canal = 'ruleta' AND clave LIKE 'top_premio_%'");
        $del->execute([$dashboardId]);
        $ordenTop = 10;
        foreach ($topPremios as $i => $tp) {
            $ordenTop++;
            $insTop = $pdo->prepare("INSERT INTO metricas_canal (dashboard_id, canal, clave, etiqueta, valor_numerico, orden)
                                      VALUES (?, 'ruleta', ?, ?, ?, ?)");
            $insTop->execute([$dashboardId, 'top_premio_' . ($i + 1), $tp['nombre'], (int) $tp['total'], $ordenTop]);
        }
    }
}
