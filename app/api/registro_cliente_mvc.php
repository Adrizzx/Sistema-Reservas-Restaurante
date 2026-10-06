<?php
/**
 * API: Registro Cliente (actualizado para MVC)
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../controllers/AuthController.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validaciones básicas
    if (empty($data['nombre']) || empty($data['apellido']) || empty($data['email']) || empty($data['password'])) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
        exit;
    }
    
    $authController = new AuthController();
    $resultado = $authController->registroCliente($data);
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
