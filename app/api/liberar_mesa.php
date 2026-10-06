<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once '../../conexion/db.php';

// Verificar autenticación de administrador
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_authenticated']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $mesa_id = intval($input['mesa_id'] ?? 0);
    
    if ($mesa_id <= 0) {
        throw new Exception('ID de mesa inválido');
    }
    
    $pdo->beginTransaction();
    
    // Obtener información de la mesa
    $stmt = $pdo->prepare("SELECT numero_mesa, estado FROM mesas WHERE id = :id FOR UPDATE");
    $stmt->bindParam(':id', $mesa_id, PDO::PARAM_INT);
    $stmt->execute();
    $mesa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mesa) {
        throw new Exception('Mesa no encontrada');
    }
    
    // Cambiar estado a disponible
    $stmt = $pdo->prepare("UPDATE mesas SET estado = 'disponible' WHERE id = :id");
    $stmt->bindParam(':id', $mesa_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Mesa {$mesa['numero_mesa']} liberada correctamente",
        'mesa' => [
            'id' => $mesa_id,
            'numero' => $mesa['numero_mesa'],
            'estado_anterior' => $mesa['estado'],
            'estado_nuevo' => 'disponible'
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
