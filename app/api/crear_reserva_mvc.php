<?php
/**
 * API: Crear Reserva (actualizado para MVC)
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cliente_authenticated'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../controllers/ReservaController.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Agregar cliente_id de la sesión
    $data['cliente_id'] = $_SESSION['cliente_id'];
    
    $reservaController = new ReservaController();
    $resultado = $reservaController->create($data);
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
