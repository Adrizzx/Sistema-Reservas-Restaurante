<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../conexion/db.php';

try {
    $query = "SELECT id, nombre, apellido, cedula, telefono, email FROM clientes ORDER BY nombre ASC";
    $stmt = $pdo->query($query);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'clientes' => $clientes
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener clientes',
        'error' => $e->getMessage()
    ]);
}
?>
