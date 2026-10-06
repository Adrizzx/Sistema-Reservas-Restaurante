<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cliente_id']) || !isset($_SESSION['cliente_authenticated'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Limpiar la mesa seleccionada de la sesión
unset($_SESSION['mesa_seleccionada_id']);
unset($_SESSION['mesa_seleccionada_numero']);

echo json_encode([
    'success' => true,
    'message' => 'Mesa deseleccionada exitosamente'
]);
?>
