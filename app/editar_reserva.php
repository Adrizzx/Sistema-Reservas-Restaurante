<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../conexion/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $data['id'] ?? null;
    
    if (empty($id)) {
        throw new Exception('ID de reserva requerido');
    }
    
    // Construir query dinámicamente según los campos enviados
    $campos = [];
    $valores = [];
    
    if (isset($data['cliente_id'])) {
        $campos[] = "cliente_id = ?";
        $valores[] = $data['cliente_id'];
    }
    
    if (isset($data['mesa_id'])) {
        $campos[] = "mesa_id = ?";
        $valores[] = $data['mesa_id'];
    }
    
    if (isset($data['fecha_reserva'])) {
        $campos[] = "fecha_reserva = ?";
        $valores[] = $data['fecha_reserva'];
    }
    
    if (isset($data['hora_reserva'])) {
        $campos[] = "hora_reserva = ?";
        $valores[] = $data['hora_reserva'];
    }
    
    if (isset($data['numero_personas'])) {
        $campos[] = "numero_personas = ?";
        $valores[] = $data['numero_personas'];
    }
    
    if (isset($data['estado'])) {
        $campos[] = "estado = ?";
        $valores[] = $data['estado'];
    }
    
    if (isset($data['observaciones'])) {
        $campos[] = "observaciones = ?";
        $valores[] = $data['observaciones'];
    }
    
    if (empty($campos)) {
        throw new Exception('No hay campos para actualizar');
    }
    
    $campos[] = "fecha_actualizacion = CURRENT_TIMESTAMP";
    
    $valores[] = $id;
    
    $query = "UPDATE reservas SET " . implode(', ', $campos) . " WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute($valores);
    
    echo json_encode([
        'success' => true,
        'message' => 'Reserva actualizada exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
