-- Tabla para reservas de zonas completas
-- Esto evita crear múltiples reservas individuales

CREATE TABLE IF NOT EXISTS `reservas_zonas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `zonas` TEXT NOT NULL COMMENT 'JSON array de zonas: ["interior","terraza","vip","bar"]',
  `fecha_reserva` date NOT NULL,
  `hora_reserva` time NOT NULL,
  `numero_personas` int(11) NOT NULL,
  `precio_total` decimal(10,2) NOT NULL,
  `cantidad_mesas` int(11) NOT NULL COMMENT 'Total de mesas incluidas',
  `estado` enum('pendiente','confirmada','cancelada','finalizada') DEFAULT 'pendiente',
  `motivo_cancelacion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_fecha` (`fecha_reserva`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_reservas_zonas_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Reservas de zonas completas del restaurante';
