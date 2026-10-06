<?php
/**
 * API: Cancelar Reserva con Notificación
 * Cancela una reserva y envía notificación por WhatsApp
 */
session_start();
header('Content-Type: application/json');

// Verificar sesión de admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../conexion/db.php';
require_once __DIR__ . '/../../config/whatsapp_config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['reserva_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de reserva no proporcionado']);
        exit;
    }
    
    $reserva_id = (int)$data['reserva_id'];
    $motivo = isset($data['motivo']) ? trim($data['motivo']) : 'Decisión administrativa';
    
    // Obtener información de la reserva antes de cancelar
    $stmt = $mysqli->prepare("
        SELECT 
            r.id,
            r.fecha_reserva,
            r.hora_reserva,
            r.numero_personas,
            r.estado,
            r.mesa_id,
            c.nombre,
            c.apellido,
            c.telefono,
            c.email,
            m.numero_mesa
        FROM reservas r
        INNER JOIN clientes c ON r.cliente_id = c.id
        INNER JOIN mesas m ON r.mesa_id = m.id
        WHERE r.id = ?
    ");
    
    $stmt->bind_param('i', $reserva_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit;
    }
    
    $reserva = $result->fetch_assoc();
    $stmt->close();
    
    // Verificar que la reserva se pueda cancelar
    if ($reserva['estado'] === 'cancelada') {
        echo json_encode(['success' => false, 'message' => 'La reserva ya está cancelada']);
        exit;
    }
    
    if ($reserva['estado'] === 'finalizada') {
        echo json_encode(['success' => false, 'message' => 'No se puede cancelar una reserva finalizada']);
        exit;
    }
    
    // Iniciar transacción
    $mysqli->begin_transaction();
    
    try {
        // 1. Cancelar la reserva
        $stmt = $mysqli->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id = ?");
        $stmt->bind_param('i', $reserva_id);
        $stmt->execute();
        $stmt->close();
        
        // 2. Liberar la mesa si estaba reservada
        $stmt = $mysqli->prepare("UPDATE mesas SET estado = 'disponible' WHERE id = ? AND estado = 'reservada'");
        $stmt->bind_param('i', $reserva['mesa_id']);
        $stmt->execute();
        $stmt->close();
        
        // 3. Enviar notificación por WhatsApp
        $whatsappEnviado = false;
        $mensajeWhatsApp = '';
        
        if (!empty($reserva['telefono']) && WHATSAPP_ENABLED) {
            $telefono = limpiarTelefono($reserva['telefono']);
            
            $fechaFormat = date('d/m/Y', strtotime($reserva['fecha_reserva']));
            $horaFormat = date('H:i', strtotime($reserva['hora_reserva']));
            
            $mensaje = "🚫 *RESERVA CANCELADA* 🚫\n\n";
            $mensaje .= "Estimado/a *{$reserva['nombre']} {$reserva['apellido']}*,\n\n";
            $mensaje .= "Lamentamos informarle que su reserva ha sido *CANCELADA*:\n\n";
            $mensaje .= "📅 *Fecha:* $fechaFormat\n";
            $mensaje .= "🕐 *Hora:* $horaFormat\n";
            $mensaje .= "🪑 *Mesa:* {$reserva['numero_mesa']}\n";
            $mensaje .= "👥 *Personas:* {$reserva['numero_personas']}\n\n";
            $mensaje .= "💬 *Motivo:* $motivo\n\n";
            $mensaje .= "Si desea realizar una nueva reserva, puede contactarnos o visitar nuestro sistema de reservas.\n\n";
            $mensaje .= "Disculpe las molestias.\n\n";
            $mensaje .= "🍽️ *Le Salon de Lumière*";
            
            $whatsappEnviado = enviarWhatsApp($telefono, $mensaje);
            $mensajeWhatsApp = $mensaje;
            
            // Registrar envío en base de datos
            $stmt = $mysqli->prepare("
                INSERT INTO notificaciones_whatsapp 
                (reserva_id, telefono, mensaje, enviado, fecha_envio) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param('issi', $reserva_id, $telefono, $mensajeWhatsApp, $whatsappEnviado);
            $stmt->execute();
            $stmt->close();
        }
        
        // Confirmar transacción
        $mysqli->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Reserva cancelada exitosamente',
            'whatsapp_enviado' => $whatsappEnviado,
            'reserva' => [
                'id' => $reserva['id'],
                'cliente' => $reserva['nombre'] . ' ' . $reserva['apellido'],
                'fecha' => $reserva['fecha_reserva'],
                'hora' => $reserva['hora_reserva'],
                'mesa' => $reserva['numero_mesa']
            ]
        ]);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Error en cancelar_reserva_admin.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cancelar reserva: ' . $e->getMessage()]);
}

$mysqli->close();


// Función para limpiar y formatear teléfono
function limpiarTelefono($telefono) {
    $telefono = preg_replace('/[^0-9]/', '', $telefono);
    if (strlen($telefono) === 10 && substr($telefono, 0, 1) === '0') {
        return '593' . substr($telefono, 1);
    }
    if (strlen($telefono) === 9) {
        return '593' . $telefono;
    }
    return $telefono;
}

// Función para enviar WhatsApp
function enviarWhatsApp($telefono, $mensaje) {
    if (!WHATSAPP_ENABLED) {
        return false;
    }
    
    try {
        $url = WHATSAPP_API_URL . '/messages';
        
        $data = [
            'phone' => $telefono,
            'message' => $mensaje
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . WHATSAPP_API_TOKEN
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
        
    } catch (Exception $e) {
        error_log('Error enviando WhatsApp: ' . $e->getMessage());
        return false;
    }
}
?>
