<?php
/**
 * API: Menú Gastronómico (actualizado para MVC)
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../controllers/MenuController.php';

try {
    $menuController = new MenuController();
    $menu = $menuController->getMenuCompleto();
    
    echo json_encode([
        'success' => true,
        'categorias' => $menu
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener menú: ' . $e->getMessage()
    ]);
}
?>
