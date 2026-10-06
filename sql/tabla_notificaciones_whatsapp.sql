-- Tabla para registrar notificaciones de WhatsApp enviadas
CREATE TABLE IF NOT EXISTS `notificaciones_whatsapp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `mensaje` text NOT NULL,
  `estado` enum('enviado','fallido','pendiente') DEFAULT 'pendiente',
  `sid_twilio` varchar(100) DEFAULT NULL COMMENT 'ID de mensaje de Twilio',
  `error_mensaje` text DEFAULT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reserva` (`reserva_id`),
  KEY `idx_fecha` (`fecha_envio`),
  CONSTRAINT `fk_notif_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
