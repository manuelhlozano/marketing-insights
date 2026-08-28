<?php
// Importación de leads desde una hoja de Google Sheets publicada como CSV.
// Solo-admin. Espera columnas: nombre, apellido, documento, telefono, correo
// (en cualquier orden; se detectan por encabezado, sin distinguir mayúsculas/acentos).

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/concursos-metrics.php';
mkt_require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit();
}

$concursoId = (int) ($_POST['concurso_id'] ?? 0);
$csvUrl = trim($_POST['sheet_csv_url'] ?? '');

if (!$concursoId || !$csvUrl || !filter_var($csvUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Concurso y URL de la hoja son obligatorios."]);
    exit();
}

$ctx = stream_context_create(['http' => ['timeout' => 12], 'https' => ['timeout' => 12]]);
$csvContent = @file_get_contents($csvUrl, false, $ctx);
if ($csvContent === false) {
    http_response_code(502);
    echo json_encode(["status" => "error", "message" => "No se pudo descargar la hoja. Verifica que esté publicada como CSV."]);
    exit();
}

$rows = array_map('str_getcsv', preg_split('/\r\n|\r|\n/', trim($csvContent)));
if (count($rows) < 2) {
    echo json_encode(["status" => "success", "importados" => 0, "message" => "La hoja no tiene filas de datos."]);
    exit();
}

function norm_header($h) {
    $h = mb_strtolower(trim($h), 'UTF-8');
    $h = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $h);
    return $h;
}

$header = array_map('norm_header', $rows[0]);
$colIndex = [];
foreach (['nombre', 'apellido', 'documento', 'cedula', 'telefono', 'celular', 'correo', 'email'] as $key) {
    $idx = array_search($key, $header, true);
    if ($idx !== false) $colIndex[$key] = $idx;
}

$get = function ($row, $key) use ($colIndex) {
    return isset($colIndex[$key]) && isset($row[$colIndex[$key]]) ? trim($row[$colIndex[$key]]) : '';
};

$ins = $pdo->prepare("INSERT INTO concurso_leads (concurso_id, nombre, apellido, documento, telefono, correo, origen)
                       VALUES (?, ?, ?, ?, ?, ?, 'google_sheet')
                       ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido),
                           telefono = VALUES(telefono), correo = VALUES(correo)");

$importados = 0;
for ($i = 1; $i < count($rows); $i++) {
    $row = $rows[$i];
    if (count($row) < 2 && trim(implode('', $row)) === '') continue;

    $nombre = $get($row, 'nombre');
    $documento = $get($row, 'documento') ?: $get($row, 'cedula');
    if (!$nombre || !$documento) continue;

    $apellido = $get($row, 'apellido');
    $telefono = $get($row, 'telefono') ?: $get($row, 'celular');
    $correo = filter_var($get($row, 'correo') ?: $get($row, 'email'), FILTER_VALIDATE_EMAIL) ?: null;

    $ins->execute([$concursoId, mb_substr($nombre, 0, 150), mb_substr($apellido, 0, 150), mb_substr($documento, 0, 30), mb_substr($telefono, 0, 30), $correo]);
    $importados++;
}

if ($importados > 0) mkt_sync_concurso_metricas($pdo, $concursoId);

echo json_encode(["status" => "success", "importados" => $importados]);
