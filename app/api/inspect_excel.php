<?php
header('Content-Type: application/json');

require_once '../../conexion/db.php';

$logFile = __DIR__ . '/../../storage/logs/excel_upload.log';

function lastSavedFileFromLog($logFile) {
    if (!file_exists($logFile)) return null;
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    for ($i = count($lines)-1; $i >= 0; $i--) {
        if (strpos($lines[$i], 'Archivo guardado en:') !== false) {
            $parts = explode('Archivo guardado en: ', $lines[$i]);
            return trim(end($parts));
        }
    }
    return null;
}

$fileParam = isset($_GET['file']) ? $_GET['file'] : null;
$filePath = null;
if ($fileParam) {
    $candidate = __DIR__ . '/../../uploads/' . basename($fileParam);
    if (file_exists($candidate)) $filePath = $candidate;
}

if (!$filePath) {
    $filePath = lastSavedFileFromLog($logFile);
}

if (!$filePath || !file_exists($filePath)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'No se encontró archivo para inspeccionar', 'file' => $filePath]);
    exit;
}

$python = trim(shell_exec('which python3 2>/dev/null')) ?: '/usr/bin/python3';
$cmd = sprintf("unset LD_LIBRARY_PATH && %s -c %s %s 2>&1",
    escapeshellarg($python),
    escapeshellarg("import sys, pandas as pd; x=pd.ExcelFile(sys.argv[1]); print('\\n'.join(x.sheet_names))"),
    escapeshellarg($filePath)
);

$output = [];
exec($cmd, $output, $rc);

if ($rc !== 0) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Error al inspeccionar con Python', 'cmd' => $cmd, 'output' => implode("\n", $output)]);
    exit;
}

$sheets = array_values(array_filter(array_map('trim', $output), function($v){return $v !== ''; }));
echo json_encode(['ok' => true, 'file' => $filePath, 'sheets' => $sheets]);
exit;

?>
