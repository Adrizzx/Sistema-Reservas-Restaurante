<?php
/**
 * API: Estado de Mesas (actualizado para MVC)
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../controllers/MesaController.php';

try {
    $mesaController = new MesaController();
    $mesas = $mesaController->getEstadoMesas();
    
    echo json_encode([
        'success' => true,
        'mesas' => $mesas
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener mesas: ' . $e->getMessage()
    ]);
}
?>
