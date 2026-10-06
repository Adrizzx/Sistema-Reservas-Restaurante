<?php
/**
 * API: Confirmar/Rechazar Reserva de Zona
 * Cambia el estado de una reserva de zona
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../conexion/db.php';

// Cargar configuración de WhatsApp
$whatsapp_config = require __DIR__ . '/../../config/whatsapp_config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $reserva_id = $data['reserva_id'] ?? 0;
    $accion = $data['accion'] ?? ''; // 'confirmar' o 'rechazar'
    $motivo = $data['motivo'] ?? null;
    
    if (!$reserva_id) {
        echo json_encode(['success' => false, 'message' => 'ID de reserva inválido']);
        exit;
    }
    
    // Obtener datos de la reserva
    $stmt = $pdo->prepare("
        SELECT rz.*, CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre, c.telefono
        FROM reservas_zonas rz
        INNER JOIN clientes c ON rz.cliente_id = c.id
        WHERE rz.id = ?
    ");
    $stmt->execute([$reserva_id]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reserva) {
        echo json_encode(['success' => false, 'message' => 'Reserva no encontrada']);
        exit;
    }
    
    // Actualizar estado según la acción
    $whatsapp_result = ['enviado' => false];
    
    if ($accion === 'confirmar') {
        $stmt = $pdo->prepare("
            UPDATE reservas_zonas 
            SET estado = 'confirmada'
            WHERE id = ?
        ");
        $stmt->execute([$reserva_id]);
        
        $mensaje = 'Reserva de zona confirmada exitosamente';
        
        // Enviar WhatsApp usando el mismo método que las reservas normales
        if (!empty($reserva['telefono'])) {
            try {
                // Decodificar zonas y traducir nombres
                $zonas_array = json_decode($reserva['zonas'], true);
                $nombres_zonas = [
                    'interior' => 'Salón Principal',
                    'terraza' => 'Terraza',
                    'vip' => 'Área VIP',
                    'bar' => 'Bar & Lounge'
                ];
                
                $zonas_nombres = array_map(function($z) use ($nombres_zonas) {
                    return $nombres_zonas[$z] ?? $z;
                }, $zonas_array);
                
                $zonas_texto = implode(', ', $zonas_nombres);
                
                // Formatear fecha
                $fecha_obj = new DateTime($reserva['fecha_reserva']);
                $fecha_formateada = $fecha_obj->format('d/m/Y');
                
                // Crear mensaje para reserva de zona
                $mensaje_whatsapp = "¡Hola {$reserva['cliente_nombre']}! ✨\n\n";
                $mensaje_whatsapp .= "¡Su reserva de zona ha sido confirmada exitosamente! 🎉\n\n";
                $mensaje_whatsapp .= "📋 *DETALLES DE SU RESERVA*\n";
                $mensaje_whatsapp .= "━━━━━━━━━━━━━━━━━━━━\n";
                $mensaje_whatsapp .= "🏢 Zonas: {$zonas_texto}\n";
                $mensaje_whatsapp .= "📅 Fecha: {$fecha_formateada}\n";
                $mensaje_whatsapp .= "🕐 Hora: {$reserva['hora_reserva']}\n";
                $mensaje_whatsapp .= "👥 Personas: {$reserva['numero_personas']}\n";
                $mensaje_whatsapp .= "🪑 Mesas incluidas: {$reserva['cantidad_mesas']}\n";
                $mensaje_whatsapp .= "━━━━━━━━━━━━━━━━━━━━\n\n";
                $mensaje_whatsapp .= "💰 *VALOR DE RESERVA*\n";
                $mensaje_whatsapp .= "━━━━━━━━━━━━━━━━━━━━\n";
                $mensaje_whatsapp .= "Total: $" . number_format($reserva['precio_total'], 2) . "\n\n";
                $mensaje_whatsapp .= "📍 *Le Salon de Lumière*\n";
                $mensaje_whatsapp .= "Un placer servirle.\n\n";
                $mensaje_whatsapp .= "⚠️ *Importante:*\n";
                $mensaje_whatsapp .= "• Llegue 10 minutos antes de su hora\n";
                $mensaje_whatsapp .= "• En caso de cancelación, avise con 24h\n";
                $mensaje_whatsapp .= "• Mantenga esta confirmación\n\n";
                $mensaje_whatsapp .= "¡Le esperamos! 🌟";
                
                // Usar la misma función de envío que las reservas normales
                require_once __DIR__ . '/../../config/whatsapp_config.php';
                
                // Limpiar y formatear teléfono (igual que enviar_whatsapp.php)
                $telefonoLimpio = preg_replace('/\D/', '', $reserva['telefono']);
                
                // Si el número empieza con 0, quitarlo
                if (substr($telefonoLimpio, 0, 1) === '0') {
                    $telefonoLimpio = substr($telefonoLimpio, 1);
                }
                
                $telefonoCompleto = 'whatsapp:+' . $whatsapp_config['country_code'] . $telefonoLimpio;
                
                // Preparar datos para Twilio
                $data_whatsapp = [
                    'From' => $whatsapp_config['twilio_whatsapp_from'],
                    'To' => $telefonoCompleto,
                    'Body' => $mensaje_whatsapp
                ];
                
                // URL de la API de Twilio
                $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $whatsapp_config['twilio_account_sid'] . '/Messages.json';
                
                // Inicializar cURL (igual que enviar_whatsapp.php)
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data_whatsapp));
                curl_setopt($ch, CURLOPT_USERPWD, $whatsapp_config['twilio_account_sid'] . ':' . $whatsapp_config['twilio_auth_token']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $response_whatsapp = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                curl_close($ch);
                
                // Log para debugging
                error_log("WhatsApp Zona - HTTP Code: $http_code, Telefono: $telefonoCompleto");
                error_log("WhatsApp Zona - Response: " . $response_whatsapp);
                if ($curl_error) {
                    error_log("WhatsApp Zona - cURL Error: " . $curl_error);
                }
                
                $response_data = json_decode($response_whatsapp, true);
                
                if ($http_code >= 200 && $http_code < 300) {
                    $whatsapp_result = [
                        'enviado' => true,
                        'mensaje' => 'WhatsApp enviado correctamente',
                        'telefono' => $telefonoCompleto,
                        'http_code' => $http_code,
                        'sid' => $response_data['sid'] ?? null,
                        'status' => $response_data['status'] ?? 'sent'
                    ];
                } else {
                    $whatsapp_result = [
                        'enviado' => false,
                        'mensaje' => 'Error al enviar WhatsApp: ' . ($response_data['message'] ?? 'Error desconocido'),
                        'telefono' => $telefonoCompleto,
                        'http_code' => $http_code,
                        'error_code' => $response_data['code'] ?? null
                    ];
                }
            } catch (Exception $e) {
                $whatsapp_result = [
                    'enviado' => false,
                    'mensaje' => 'Excepción al enviar WhatsApp: ' . $e->getMessage()
                ];
            }
        }
        
    } elseif ($accion === 'rechazar') {
        $stmt = $pdo->prepare("
            UPDATE reservas_zonas 
            SET estado = 'cancelada', motivo_cancelacion = ?
            WHERE id = ?
        ");
        $stmt->execute([$motivo, $reserva_id]);
        
        $mensaje = 'Reserva de zona rechazada';
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción inválida']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'whatsapp' => $whatsapp_result
    ]);
    
} catch (Exception $e) {
    error_log('Error en gestionar_reserva_zona.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
