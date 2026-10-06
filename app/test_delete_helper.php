<?php
/**
 * Helper para borrar datos de prueba
 * SOLO PARA TESTING - NO USAR EN PRODUCCIÓN
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$cedula = trim($_POST['cedula'] ?? '');
$email = trim($_POST['email'] ?? '');

if (empty($cedula) && empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Se requiere cédula o email']);
    exit;
}

try {
    $deleted = 0;
    
    if (!empty($cedula)) {
        $stmt = $mysqli->prepare("DELETE FROM clientes WHERE cedula = ?");
        $stmt->bind_param('s', $cedula);
        $stmt->execute();
        $deleted += $stmt->affected_rows;
        $stmt->close();
    }
    
    if (!empty($email)) {
        $stmt = $mysqli->prepare("DELETE FROM clientes WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $deleted += $stmt->affected_rows;
        $stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'deleted' => $deleted,
        'message' => "Eliminados $deleted registro(s)"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar: ' . $e->getMessage()
    ]);
}

$mysqli->close();
?>
