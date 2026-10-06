<?php
/**
 * Controlador de Notificaciones WhatsApp
 * Gestiona el envío de notificaciones por WhatsApp a los clientes
 */

class NotificacionController {
    private $pdo;
    private $whatsappConfig;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->whatsappConfig = require __DIR__ . '/../config/whatsapp_config.php';
    }
    
    /**
     * Enviar notificación de cancelación por cambio de horarios
     */
    public function enviarNotificacionCancelacionHorarios($reservasAfectadas, $nuevosHorarios) {
        $resultados = [
            'total' => count($reservasAfectadas),
            'enviados' => 0,
            'fallidos' => 0,
            'detalles' => []
        ];
        
        foreach ($reservasAfectadas as $reserva) {
            try {
                // Cancelar la reserva
                $stmt = $this->pdo->prepare("
                    UPDATE reservas 
                    SET estado = 'cancelada',
                        motivo_cancelacion = 'Cambio de horarios de atención'
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $reserva['id']]);
                
                // Preparar mensaje personalizado
                $mensaje = $this->generarMensajeCancelacionHorarios($reserva, $nuevosHorarios);
                
                // Enviar WhatsApp
                $resultado = $this->enviarWhatsApp($reserva['telefono'], $mensaje);
                
                if ($resultado['success']) {
                    $resultados['enviados']++;
                    $resultados['detalles'][] = [
                        'reserva_id' => $reserva['id'],
                        'cliente' => $reserva['cliente'],
                        'telefono' => $reserva['telefono'],
                        'estado' => 'enviado'
                    ];
                } else {
                    $resultados['fallidos']++;
                    $resultados['detalles'][] = [
                        'reserva_id' => $reserva['id'],
                        'cliente' => $reserva['cliente'],
                        'telefono' => $reserva['telefono'],
                        'estado' => 'fallido',
                        'error' => $resultado['error']
                    ];
                }
                
                // Registrar en log de notificaciones
                $this->registrarNotificacion(
                    $reserva['id'],
                    $reserva['telefono'],
                    'cancelacion_horarios',
                    $mensaje,
                    $resultado['success'] ? 'enviado' : 'fallido'
                );
                
            } catch (Exception $e) {
                $resultados['fallidos']++;
                $resultados['detalles'][] = [
                    'reserva_id' => $reserva['id'],
                    'cliente' => $reserva['cliente'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $resultados;
    }
    
    /**
     * Generar mensaje personalizado de cancelación
     */
    private function generarMensajeCancelacionHorarios($reserva, $nuevosHorarios) {
        $restaurantName = $this->whatsappConfig['restaurant_name'];
        $restaurantPhone = $this->whatsappConfig['restaurant_phone'];
        
        $mensaje = "🔔 *{$restaurantName}*\n\n";
        $mensaje .= "Estimado/a *{$reserva['cliente']}*,\n\n";
        $mensaje .= "Lamentamos informarle que su reserva ha sido *CANCELADA* debido a un cambio en nuestros horarios de atención.\n\n";
        $mensaje .= "📅 *Reserva cancelada:*\n";
        $mensaje .= "• Fecha: {$reserva['fecha']}\n";
        $mensaje .= "• Hora: {$reserva['hora']}\n";
        $mensaje .= "• Mesa: {$reserva['mesa']}\n";
        $mensaje .= "• Personas: {$reserva['personas']}\n\n";
        $mensaje .= "⏰ *Nuevos horarios de atención:*\n";
        
        if (isset($nuevosHorarios['lunes_viernes'])) {
            $mensaje .= "• Lunes a Viernes: {$nuevosHorarios['lunes_viernes']}\n";
        }
        if (isset($nuevosHorarios['sabado'])) {
            $mensaje .= "• Sábado: {$nuevosHorarios['sabado']}\n";
        }
        if (isset($nuevosHorarios['domingo'])) {
            $mensaje .= "• Domingo: {$nuevosHorarios['domingo']}\n";
        }
        if (isset($nuevosHorarios['dias_cerrados']) && !empty($nuevosHorarios['dias_cerrados'])) {
            $mensaje .= "• Días cerrados: {$nuevosHorarios['dias_cerrados']}\n";
        }
        
        $mensaje .= "\n💡 *Puede realizar una nueva reserva* en nuestros nuevos horarios.\n\n";
        $mensaje .= "Para más información o realizar una nueva reserva, contáctenos al {$restaurantPhone}\n\n";
        $mensaje .= "Disculpe las molestias.\n";
        $mensaje .= "Equipo de {$restaurantName} 🍽️";
        
        return $mensaje;
    }
    
    /**
     * Enviar mensaje por WhatsApp usando Twilio
     */
    private function enviarWhatsApp($telefono, $mensaje) {
        try {
            // Formatear número de teléfono
            $telefono = $this->formatearTelefono($telefono);
            
            $accountSid = $this->whatsappConfig['twilio_account_sid'];
            $authToken = $this->whatsappConfig['twilio_auth_token'];
            $whatsappFrom = $this->whatsappConfig['twilio_whatsapp_from'];
            $whatsappTo = 'whatsapp:' . $telefono;
            
            // URL de la API de Twilio
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
            
            $data = [
                'From' => $whatsappFrom,
                'To' => $whatsappTo,
                'Body' => $mensaje
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return ['success' => true, 'response' => $response];
            } else {
                return ['success' => false, 'error' => "HTTP {$httpCode}: {$response}"];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Formatear número de teléfono
     */
    private function formatearTelefono($telefono) {
        // Eliminar espacios y caracteres especiales
        $telefono = preg_replace('/[^0-9+]/', '', $telefono);
        
        // Si no tiene código de país, agregar el de Ecuador
        if (substr($telefono, 0, 1) !== '+') {
            $countryCode = $this->whatsappConfig['country_code'];
            $telefono = '+' . $countryCode . ltrim($telefono, '0');
        }
        
        return $telefono;
    }
    
    /**
     * Enviar notificación de cancelación de reserva
     */
    public function enviarNotificacionCancelacion($reserva) {
        try {
            // Verificar si el envío automático está habilitado
            if (!$this->whatsappConfig['auto_send_enabled']) {
                return ['success' => false, 'error' => 'Envío automático deshabilitado'];
            }
            
            // Preparar mensaje de cancelación
            $mensaje = $this->generarMensajeCancelacion($reserva);
            
            // Enviar WhatsApp
            $resultado = $this->enviarWhatsApp($reserva['telefono'], $mensaje);
            
            // Registrar en log de notificaciones
            $this->registrarNotificacion(
                $reserva['id'],
                $reserva['telefono'],
                'cancelacion_cliente',
                $mensaje,
                $resultado['success'] ? 'enviado' : 'fallido'
            );
            
            return $resultado;
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Generar mensaje de cancelación de reserva
     */
    private function generarMensajeCancelacion($reserva) {
        $restaurantName = $this->whatsappConfig['restaurant_name'];
        $restaurantPhone = $this->whatsappConfig['restaurant_phone'];
        
        $mensaje = "🔔 *{$restaurantName}*\n\n";
        $mensaje .= "Estimado/a *{$reserva['nombre']} {$reserva['apellido']}*,\n\n";
        $mensaje .= "Le confirmamos que su reserva ha sido *CANCELADA* exitosamente.\n\n";
        $mensaje .= "📅 *Detalles de la reserva cancelada:*\n";
        $mensaje .= "• Fecha: {$reserva['fecha_formateada']}\n";
        $mensaje .= "• Hora: {$reserva['hora_formateada']}\n";
        $mensaje .= "• Mesa: #{$reserva['numero_mesa']}\n";
        $mensaje .= "• Personas: {$reserva['numero_personas']}\n\n";
        $mensaje .= "💡 Puede realizar una nueva reserva cuando lo desee visitando nuestro sitio web.\n\n";
        $mensaje .= "Para más información, contáctenos al {$restaurantPhone}\n\n";
        $mensaje .= "Esperamos verle pronto.\n";
        $mensaje .= "Equipo de {$restaurantName} 🍽️";
        
        return $mensaje;
    }
    
    /**
     * Enviar notificación de nueva reserva de zona
     */
    public function enviarNotificacionReservaZona($reserva) {
        try {
            // Verificar si el envío automático está habilitado
            if (!$this->whatsappConfig['auto_send_enabled']) {
                return ['success' => false, 'error' => 'Envío automático deshabilitado'];
            }
            
            // Preparar mensaje de reserva de zona
            $mensaje = $this->generarMensajeReservaZona($reserva);
            
            // Enviar WhatsApp
            $resultado = $this->enviarWhatsApp($reserva['telefono'], $mensaje);
            
            // Registrar en log de notificaciones
            $this->registrarNotificacion(
                $reserva['id'],
                $reserva['telefono'],
                'reserva_zona_creada',
                $mensaje,
                $resultado['success'] ? 'enviado' : 'fallido'
            );
            
            return $resultado;
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Generar mensaje de reserva de zona completa
     */
    private function generarMensajeReservaZona($reserva) {
        $restaurantName = $this->whatsappConfig['restaurant_name'];
        $restaurantPhone = $this->whatsappConfig['restaurant_phone'];
        
        $zonasTexto = is_array($reserva['zonas']) ? implode(', ', $reserva['zonas']) : $reserva['zonas'];
        
        $mensaje = "🎉 *{$restaurantName}*\n\n";
        $mensaje .= "Estimado/a *{$reserva['nombre']} {$reserva['apellido']}*,\n\n";
        $mensaje .= "¡Gracias por su solicitud de reserva de zona completa! ✨\n\n";
        $mensaje .= "📋 *Detalles de su solicitud:*\n";
        $mensaje .= "• Zonas: {$zonasTexto}\n";
        $mensaje .= "• Fecha: {$reserva['fecha_formateada']}\n";
        $mensaje .= "• Hora: {$reserva['hora_formateada']}\n";
        $mensaje .= "• Personas: {$reserva['numero_personas']}\n";
        $mensaje .= "• Cantidad de mesas: {$reserva['cantidad_mesas']}\n";
        $mensaje .= "• Precio total: \${$reserva['precio_total']}\n\n";
        $mensaje .= "⏳ *Estado:* PENDIENTE DE CONFIRMACIÓN\n\n";
        $mensaje .= "Nuestro equipo revisará su solicitud y le confirmará la disponibilidad a la brevedad.\n\n";
        $mensaje .= "Para cualquier consulta, contáctenos al {$restaurantPhone}\n\n";
        $mensaje .= "¡Esperamos confirmar su reserva pronto!\n";
        $mensaje .= "Equipo de {$restaurantName} 🍽️";
        
        return $mensaje;
    }
    
    /**
     * Registrar notificación en la base de datos
     */
    private function registrarNotificacion($reservaId, $telefono, $tipo, $mensaje, $estado) {

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notificaciones_whatsapp 
                (reserva_id, telefono, tipo_notificacion, mensaje, estado, fecha_envio)
                VALUES (:reserva_id, :telefono, :tipo, :mensaje, :estado, NOW())
            ");
            
            $stmt->execute([
                'reserva_id' => $reservaId,
                'telefono' => $telefono,
                'tipo' => $tipo,
                'mensaje' => $mensaje,
                'estado' => $estado
            ]);
        } catch (Exception $e) {
            error_log("Error registrando notificación: " . $e->getMessage());
        }
    }
}
