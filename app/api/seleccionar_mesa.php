<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cliente_id']) || !isset($_SESSION['cliente_authenticated'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../../conexion/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $mesa_id = $data['mesa_id'] ?? null;
    
    if (!$mesa_id) {
        echo json_encode(['success' => false, 'message' => 'ID de mesa no proporcionado']);
        exit;
    }
    
    // Verificar que la mesa existe y está disponible
    $stmt = $pdo->prepare("SELECT * FROM mesas WHERE id = ? AND estado = 'disponible'");
    $stmt->execute([$mesa_id]);
    $mesa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mesa) {
        echo json_encode(['success' => false, 'message' => 'Mesa no disponible']);
        exit;
    }
    
    // Guardar la mesa seleccionada en la sesión
    $_SESSION['mesa_seleccionada_id'] = $mesa_id;
    $_SESSION['mesa_seleccionada_numero'] = $mesa['numero_mesa'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Mesa seleccionada exitosamente',
        'mesa' => $mesa
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
