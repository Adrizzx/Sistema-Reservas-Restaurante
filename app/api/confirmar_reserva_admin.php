<?php
/**
 * API para confirmar una reserva pendiente (ADMIN)
 * Cambia el estado de pendiente a confirmada y envía WhatsApp
 */

// Limpiar cualquier output previo
ob_start();

session_start();

header('Content-Type: application/json; charset=UTF-8');

require_once '../../conexion/db.php';
require_once '../../controllers/AuditoriaController.php';

// Limpiar buffer
ob_end_clean();

// Verificar que sea admin
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acceso denegado. Solo administradores pueden confirmar reservas.'
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $reserva_id = $data['reserva_id'] ?? null;
    
    if (!$reserva_id) {
        throw new Exception('ID de reserva requerido');
    }
    
    // Verificar que la reserva exista y esté pendiente
    $stmt = $pdo->prepare("
        SELECT r.*, c.nombre, c.apellido, c.telefono, m.numero_mesa 
        FROM reservas r
        INNER JOIN clientes c ON r.cliente_id = c.id
        INNER JOIN mesas m ON r.mesa_id = m.id
        WHERE r.id = :id
    ");
    $stmt->bindParam(':id', $reserva_id, PDO::PARAM_INT);
    $stmt->execute();
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        throw new Exception('Reserva no encontrada');
    }
    
    if ($reserva['estado'] === 'confirmada') {
        throw new Exception('Esta reserva ya está confirmada');
    }
    
    if ($reserva['estado'] === 'cancelada') {
        throw new Exception('No se puede confirmar una reserva cancelada');
    }
    
    // Actualizar estado a confirmada
    $stmt = $pdo->prepare("UPDATE reservas SET estado = 'confirmada' WHERE id = :id");
    $stmt->bindParam(':id', $reserva_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // REGISTRAR EN AUDITORÍA
    $auditoriaController = new AuditoriaController($pdo);
    $auditoriaController->registrarAccionReserva(
        $reserva_id,
        $_SESSION['admin_id'] ?? null,
        'confirmar',
        $reserva['estado'],
        'confirmada',
        ['estado' => $reserva['estado']],
        ['estado' => 'confirmada'],
        'Confirmada por administrador desde dashboard'
    );
    
    // Enviar WhatsApp de confirmación
    $whatsapp_enviado = false;
    $whatsapp_error = null;
    
    try {
        $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . '/PRY_PROYECTO/app/api/enviar_whatsapp.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['reserva_id' => $reserva_id]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $whatsapp_enviado = true;
        } else {
            $whatsapp_error = "Error HTTP: $http_code";
        }
    } catch (Exception $e) {
        $whatsapp_error = $e->getMessage();
        error_log("Error al enviar WhatsApp: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Reserva confirmada exitosamente',
        'reserva' => [
            'id' => $reserva_id,
            'cliente' => $reserva['nombre'] . ' ' . $reserva['apellido'],
            'telefono' => $reserva['telefono'],
            'mesa' => $reserva['numero_mesa'],
            'estado' => 'confirmada'
        ],
        'whatsapp' => [
            'enviado' => $whatsapp_enviado,
            'error' => $whatsapp_error
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
