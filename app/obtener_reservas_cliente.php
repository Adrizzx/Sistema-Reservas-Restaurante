<?php
session_start();

// Configurar encabezados para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Verificar si el cliente está autenticado
if (!isset($_SESSION['cliente_id']) || !$_SESSION['cliente_authenticated']) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

// Conectar a la base de datos
require_once '../conexion/db.php';

try {
    $cliente_id = $_SESSION['cliente_id'];
    
    // Obtener reservas del cliente
    $query = "SELECT r.*, m.numero_mesa, m.capacidad 
              FROM reservas r 
              LEFT JOIN mesas m ON r.mesa_id = m.id 
              WHERE r.cliente_id = ? 
              ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC 
              LIMIT 10";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception("Error en la consulta: " . $mysqli->error);
    }
    
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservas = [];
    while ($row = $result->fetch_assoc()) {
        $reservas[] = [
            'id' => $row['id'],
            'mesa_numero' => $row['numero_mesa'] ?? 'Sin asignar',
            'fecha_reserva' => date('d/m/Y', strtotime($row['fecha_reserva'])),
            'hora_reserva' => date('H:i', strtotime($row['hora_reserva'])),
            'numero_personas' => $row['numero_personas'],
            'estado' => $row['estado']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'reservas' => $reservas,
        'total' => count($reservas)
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error en obtener_reservas_cliente.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}

$mysqli->close();
?>