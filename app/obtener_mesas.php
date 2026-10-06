<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once '../conexion/db.php';

try {
    $query = "SELECT * FROM mesas ORDER BY numero_mesa ASC";
    $stmt = $pdo->query($query);
    $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'mesas' => $mesas
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener mesas',
        'error' => $e->getMessage()
    ]);
}
?>
