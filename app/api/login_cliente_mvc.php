<?php
/**
 * API: Login Cliente (actualizado para MVC)
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../controllers/AuthController.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email y contraseña requeridos']);
        exit;
    }
    
    $authController = new AuthController();
    $resultado = $authController->loginCliente($email, $password);
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
