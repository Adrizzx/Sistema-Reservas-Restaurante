<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../conexion/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
    exit;
}

// Crear directorio de logs si no existe
$logDir = '../../storage/logs/';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}
$logFile = $logDir . 'excel_upload.log';

function writeLog($message) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

try {
    // Verificar que se subió un archivo
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió ningún archivo o hubo un error en la subida');
    }
    
    $file = $_FILES['excel_file'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validar extensión
    if (!in_array($fileExt, ['xlsx', 'xls'])) {
        throw new Exception('El archivo debe ser un Excel (.xlsx o .xls)');
    }
    
    writeLog("Archivo recibido: {$file['name']} ({$file['size']} bytes)");
    
    // Crear directorio uploads si no existe
    $uploadDir = '../../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generar nombre único
    $fileName = 'menu_' . date('YmdHis') . '.' . $fileExt;
    $filePath = $uploadDir . $fileName;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    $absolutePath = realpath($filePath);
    writeLog("Archivo guardado en: $absolutePath");
    
    // Configuración de Python - Detectar automáticamente
    $pythonBin = trim(shell_exec('which python3 2>/dev/null')) ?: '/usr/bin/python3';
    writeLog("Python detectado: $pythonBin");
    
    $pythonScript = realpath(__DIR__ . '/update_from_excel.py');
    
    // Obtener credenciales de db.php (se espera que db.php exponga $host, $dbname, $username, $password)
    global $host, $dbname, $username, $password;
    
    // Verificar si se debe limpiar las tablas antes de cargar
    $clearBefore = isset($_POST['clear_before']) && $_POST['clear_before'] === 'true' ? '--clear-before' : '';
    
    // Comando Python con credenciales
    // IMPORTANTE: Limpiar LD_LIBRARY_PATH para evitar conflictos con librerías de LAMPP
    $command = sprintf(
        'unset LD_LIBRARY_PATH && %s %s --input %s --mysql-host %s --mysql-db %s --mysql-user %s --mysql-pass %s %s 2>&1',
        escapeshellarg($pythonBin),
        escapeshellarg($pythonScript),
        escapeshellarg($absolutePath),
        escapeshellarg($host),
        escapeshellarg($dbname),
        escapeshellarg($username),
        escapeshellarg($password),
        $clearBefore
    );
    
    writeLog("Ejecutando comando: $command");
    
    // Ejecutar con entorno limpio
    exec($command, $output, $returnCode);
    
    $stdout = implode("\n", $output);
    writeLog("Exit code: $returnCode");
    writeLog("Output: $stdout");
    
    // Limpiar archivo temporal
    @unlink($filePath);
    writeLog("Archivo temporal eliminado");
    
    if ($returnCode === 0) {
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'message' => 'Excel procesado exitosamente',
            'stdout' => $stdout,
            'stderr' => ''
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'message' => 'Error al procesar el Excel',
            'stdout' => $stdout,
            'stderr' => $stdout
        ]);
    }
    
} catch (Exception $e) {
    writeLog("ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => $e->getMessage(),
        'stdout' => '',
        'stderr' => $e->getMessage()
    ]);
}

?>
