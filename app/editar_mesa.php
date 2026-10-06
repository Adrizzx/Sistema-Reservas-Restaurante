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
    $numero_mesa = $data['numero_mesa'] ?? '';
    $capacidad_minima = $data['capacidad_minima'] ?? 1;
    $capacidad_maxima = $data['capacidad_maxima'] ?? null;
    $ubicacion = $data['ubicacion'] ?? 'interior';
    $estado = $data['estado'] ?? 'disponible';
    $descripcion = $data['descripcion'] ?? null;
    
    if (empty($id) || empty($numero_mesa) || empty($capacidad_maxima)) {
        throw new Exception('ID, número de mesa y capacidad máxima son requeridos');
    }
    
    // Verificar si el número de mesa ya existe en otra mesa
    $stmt = $pdo->prepare("SELECT id FROM mesas WHERE numero_mesa = ? AND id != ?");
    $stmt->execute([$numero_mesa, $id]);
    if ($stmt->fetch()) {
        throw new Exception('El número de mesa ya existe en otra mesa');
    }
    
    $query = "UPDATE mesas SET 
              numero_mesa = ?, 
              capacidad_minima = ?, 
              capacidad_maxima = ?, 
              ubicacion = ?, 
              estado = ?, 
              descripcion = ?
              WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$numero_mesa, $capacidad_minima, $capacidad_maxima, $ubicacion, $estado, $descripcion, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mesa actualizada exitosamente'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
