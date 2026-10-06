<?php
/**
 * Configuración de WhatsApp - Twilio API
 */

// Cargar variables de entorno
require_once __DIR__ . '/env_loader.php';

return [
    // Credenciales de Twilio
    'twilio_account_sid' => env('TWILIO_ACCOUNT_SID'),
    'twilio_auth_token' => env('TWILIO_AUTH_TOKEN'),
    'twilio_whatsapp_from' => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'),
    
    // Configuración del restaurante
    'restaurant_name' => env('RESTAURANT_NAME', 'Le Salon de Lumière'),
    'restaurant_phone' => env('RESTAURANT_PHONE', '+593999999999'),
    
    // Prefijo de país (Ecuador)
    'country_code' => env('COUNTRY_CODE', '593'),
    
    // Activar/desactivar envío automático
    'auto_send_enabled' => env('AUTO_SEND_ENABLED', true),
    
    // Modo de prueba (true = no envía realmente, solo registra en logs)
    'test_mode' => env('TEST_MODE', false),
    
    // Plantillas de mensajes
    'templates' => [
        'reserva_confirmada' => true,
        'reserva_modificada' => true,
        'reserva_cancelada' => true,
        'recordatorio_24h' => false,
    ]
];
