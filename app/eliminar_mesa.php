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
        throw new Exception('ID de mesa requerido');
    }
    
    // Verificar si hay reservas activas para esta mesa
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reservas WHERE mesa_id = ? AND estado IN ('pendiente', 'confirmada', 'en_curso')");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        throw new Exception('No se puede eliminar la mesa porque tiene reservas activas');
    }
    
    $query = "DELETE FROM mesas WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mesa eliminada exitosamente'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
