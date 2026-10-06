<?php
/**
 * API: Cambiar Estado de Mesa(s)
 * Permite cambiar el estado de una o varias mesas
 */
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Verificar sesión de admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'No autorizado - Sesión no válida'
    ]);
    exit;
}

require_once __DIR__ . '/../../conexion/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['mesas']) || !isset($data['estado'])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    
    $mesas = $data['mesas']; // Array de IDs de mesas o 'todas'
    $nuevoEstado = $data['estado'];
    
    // Validar estado
    $estadosValidos = ['disponible', 'ocupada', 'reservada', 'mantenimiento'];
    if (!in_array($nuevoEstado, $estadosValidos)) {
        echo json_encode(['success' => false, 'message' => 'Estado no válido']);
        exit;
    }
    
    $mesasActualizadas = 0;
    
    if ($mesas === 'todas') {
        // Cambiar estado de TODAS las mesas usando PDO
        $stmt = $pdo->prepare("UPDATE mesas SET estado = :estado, fecha_actualizacion = NOW()");
        $stmt->bindParam(':estado', $nuevoEstado, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            $mesasActualizadas = $stmt->rowCount();
        }
        
    } else if (is_array($mesas) && count($mesas) > 0) {
        // Cambiar estado de mesas específicas usando PDO
        $placeholders = str_repeat('?,', count($mesas) - 1) . '?';
        $sql = "UPDATE mesas SET estado = ?, fecha_actualizacion = NOW() WHERE id IN ($placeholders)";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind parameters: primero el estado, luego los IDs
        $params = array_merge([$nuevoEstado], $mesas);
        
        if ($stmt->execute($params)) {
            $mesasActualizadas = $stmt->rowCount();
        }
    }
    
    if ($mesasActualizadas > 0) {
        echo json_encode([
            'success' => true,
            'message' => "$mesasActualizadas mesa(s) actualizada(s) a estado: $nuevoEstado",
            'mesas_actualizadas' => $mesasActualizadas
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se actualizaron mesas. Verifica que las mesas existan.',
            'debug' => [
                'mesas_recibidas' => $mesas,
                'estado_nuevo' => $nuevoEstado
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log('Error en cambiar_estado_mesa.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al cambiar estado de mesas: ' . $e->getMessage()
    ]);
}
?>
