<?php
/**
 * API: Seleccionar Mesa (actualizado para MVC)
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cliente_authenticated'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../controllers/MesaController.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $mesa_id = $data['mesa_id'] ?? null;
    
    if (!$mesa_id) {
        echo json_encode(['success' => false, 'message' => 'ID de mesa no proporcionado']);
        exit;
    }
    
    $mesaController = new MesaController();
    $resultado = $mesaController->seleccionarMesa($mesa_id);
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
