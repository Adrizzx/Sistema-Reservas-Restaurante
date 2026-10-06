-- =============================================
-- BASE DE DATOS COMPLETA: crud_proyecto
-- Sistema de Restaurante - Le Salon de Lumière
-- =============================================
-- Versión: 2.0 Unificada
-- Fecha: Diciembre 2025
-- Descripción: Script completo con todas las tablas, triggers, procedimientos
--              y datos de ejemplo para el sistema de gestión de restaurante
-- =============================================

-- =============================================
-- 1. CREACIÓN DE BASE DE DATOS
-- =============================================
DROP DATABASE IF EXISTS crud_proyecto;
CREATE DATABASE crud_proyecto CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE crud_proyecto;

-- =============================================
-- 2. TABLA: administradores
-- Gestión de usuarios administradores del sistema
-- =============================================
DROP TABLE IF EXISTS `administradores`;
CREATE TABLE `administradores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rol` enum('admin','manager','cajero') DEFAULT 'admin',
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Tabla de administradores con distintos roles de acceso';

-- Datos de ejemplo
INSERT INTO `administradores` VALUES 
(1,'admin','admin','Admin','Restaurante','admin@restaurante.com','admin',1,'2025-11-12 01:54:19','2025-11-07 02:05:38');

-- =============================================
-- 3. TABLA: clientes
-- Registro de clientes del restaurante
-- =============================================
DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `cedula` (`cedula`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Tabla de clientes registrados en el sistema';

-- Datos de ejemplo
INSERT INTO `clientes` VALUES 
(1,'Joffre','Gomez','1728677574','0990000001','Machachi','joffre','20082004','cliente.demo@example.com'),
(2,'María','González','1234567890','0991234567','Quito','maria.g','maria123','maria.gonzalez@email.com'),
(3,'Carlos','Rodríguez','0987654321','0992345678','Guayaquil','carlos.r','carlos456','carlos.rodriguez@email.com'),
(4,'Ana','López','1122334455','0993456789','Cuenca','ana.l','ana789','ana.lopez@email.com'),
(5,'Diego','Martínez','5544332211','0994567890','Quito','diego.m','diego321','diego.martinez@email.com'),
(6,'Sofía','Hernández','6677889900','0995678901','Ambato','sofia.h','sofia654','sofia.hernandez@email.com'),
(7,'Roberto','Silva','9988776655','0996789012','Manta','roberto.s','roberto987','roberto.silva@email.com'),
(8,'Isabella','Morales','1357924680','0997890123','Loja','isabella.m','isabella246','isabella.morales@email.com'),
(9,'Fernando','Castro','2468013579','0998901234','Machala','fernando.c','fernando135','fernando.castro@email.com'),
(10,'Valentina','Ruiz','3691470258','0999012345','Riobamba','valentina.r','valentina802','valentina.ruiz@email.com'),
(11,'Alejandro','Vega','7531598642','0990123456','Ibarra','alejandro.v','alejandro579','alejandro.vega@email.com');

-- =============================================
-- 4. TABLA: categorias_platos
-- Categorías del menú del restaurante
-- =============================================
DROP TABLE IF EXISTS `categorias_platos`;
CREATE TABLE `categorias_platos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden_menu` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Categorías para organizar el menú de platos';

-- Datos de ejemplo
INSERT INTO `categorias_platos` VALUES 
(35,'Entradas','Platos para comenzar la experiencia gastronómica',1,1),
(36,'Platos Principales','Especialidades de la casa y platos principales',2,1),
(37,'Carnes','Selección premium de carnes y parrillas',3,1),
(38,'Mariscos','Frescos del mar preparados con maestría',4,1),
(39,'Postres','Dulces creaciones para finalizar',5,1),
(40,'Bebidas','Selección de bebidas y cocteles',6,1);

-- =============================================
-- 5. TABLA: mesas
-- Gestión de mesas del restaurante
-- =============================================
DROP TABLE IF EXISTS `mesas`;
CREATE TABLE `mesas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_mesa` varchar(10) NOT NULL,
  `capacidad_minima` int(11) NOT NULL DEFAULT 1,
  `capacidad_maxima` int(11) NOT NULL DEFAULT 4,
  `precio_reserva` decimal(10,2) DEFAULT 5.00,
  `ubicacion` enum('interior','terraza','vip','bar') DEFAULT 'interior',
  `estado` enum('disponible','ocupada','reservada','mantenimiento') DEFAULT 'disponible',
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_mesa` (`numero_mesa`),
  KEY `idx_mesas_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Mesas del restaurante con capacidad y precios dinámicos';

-- Datos de ejemplo
INSERT INTO `mesas` VALUES 
(1,'M01',1,5,8.00,'interior','disponible','Mesa íntima junto a la ventana','2025-11-12 21:12:57','2025-11-16 06:04:08'),
(2,'M02',1,8,10.00,'interior','disponible','Mesa familiar en el centro del salón','2025-11-12 21:12:57','2025-11-16 06:02:27'),
(3,'M03',1,6,8.00,'interior','disponible','Mesa mediana para grupos','2025-11-12 21:12:57','2025-11-16 06:02:27'),
(4,'M04',1,6,8.00,'interior','disponible','Mesa principal cerca de la cocina','2025-11-12 21:12:57','2025-11-16 06:02:27'),
(5,'T01',1,8,10.00,'terraza','disponible','Mesa grande en terraza','2025-11-12 21:12:57','2025-11-16 06:02:27'),
(6,'T02',1,10,10.00,'terraza','disponible','Mesa con vista al jardín','2025-11-12 21:12:57','2025-11-16 06:02:27'),
(7,'V01',1,10,10.00,'vip','disponible','Mesa VIP con servicio exclusivo','2025-11-12 21:12:57','2025-11-16 06:02:27'),
(8,'V02',1,12,15.00,'vip','disponible','Sala privada VIP','2025-11-12 21:12:57','2025-11-16 06:02:27'),
(9,'B01',1,15,15.00,'bar','disponible','Mesa grande en la barra','2025-11-12 21:12:57','2025-11-20 01:59:47'),
(10,'B02',1,4,6.00,'bar','disponible','Mesa de bar con vista a cocina','2025-11-12 21:12:57','2025-11-16 06:02:27');

-- =============================================
-- 6. TABLA: reservas
-- Reservas de mesas realizadas por clientes
-- =============================================
DROP TABLE IF EXISTS `reservas`;
CREATE TABLE `reservas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `mesa_id` int(11) NOT NULL,
  `fecha_reserva` date NOT NULL,
  `hora_reserva` time NOT NULL,
  `numero_personas` int(11) NOT NULL,
  `estado` enum('pendiente','confirmada','en_curso','finalizada','cancelada') DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `idx_fecha_hora` (`fecha_reserva`,`hora_reserva`),
  KEY `idx_mesa_fecha` (`mesa_id`,`fecha_reserva`),
  KEY `idx_reservas_fecha_estado` (`fecha_reserva`,`estado`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Reservas de mesas con estado y seguimiento temporal';

-- Datos de ejemplo
INSERT INTO `reservas` VALUES 
(1,1,1,'2025-11-15','19:30:00',4,'pendiente'),
(2,1,2,'2025-11-16','20:00:00',3,'en_curso'),
(3,1,3,'2025-11-12','17:00:00',2,'confirmada');

-- =============================================
-- 7. TABLA: platos
-- Platos disponibles en el menú
-- =============================================
DROP TABLE IF EXISTS `platos`;
CREATE TABLE `platos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock_disponible` int(11) DEFAULT 0,
  `imagen_url` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `tiempo_preparacion` int(11) DEFAULT 15,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_platos_nombre_categoria` (`nombre`,`categoria_id`),
  KEY `idx_platos_categoria` (`categoria_id`,`activo`),
  CONSTRAINT `platos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_platos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Platos del menú con precios, stock y tiempos de preparación';

-- Datos de ejemplo
INSERT INTO `platos` VALUES 
(64,35,'Croquetas de jamón','Crujientes croquetas de jamón serrano',7.50,18,'https://picsum.photos/200?food1',1,15,'2025-11-20 02:04:46'),
(65,35,'Ensalada César','Clásica ensalada con aderezo César',6.80,25,'https://picsum.photos/200?food2',1,10,'2025-11-20 02:04:46'),
(66,35,'Sopa de tomate','Sopa cremosa de tomate con especias',5.50,20,'https://picsum.photos/200?food3',1,12,'2025-11-20 02:04:46'),
(67,36,'Lasaña boloñesa','Lasaña casera con salsa boloñesa',14.50,15,'https://picsum.photos/200?food4',1,25,'2025-11-20 02:04:46'),
(68,36,'Risotto de hongos','Risotto cremoso con variedad de hongos',16.20,12,'https://picsum.photos/200?food5',1,20,'2025-11-20 02:04:46'),
(69,36,'Pollo teriyaki','Pollo glaseado en salsa teriyaki',13.90,22,'https://picsum.photos/200?food6',1,18,'2025-11-20 02:04:46'),
(70,36,'Tacos de carne','Tacos tradicionales con carne marinada',11.20,30,'https://picsum.photos/200?food7',1,15,'2025-11-20 02:04:46'),
(71,37,'Costillas BBQ','Costillas de cerdo con salsa BBQ',19.90,10,'https://picsum.photos/200?food8',1,30,'2025-11-20 02:04:46'),
(72,37,'Lomo fino','Lomo fino a la parrilla en su punto',22.50,8,'https://picsum.photos/200?food9',1,25,'2025-11-20 02:04:46'),
(73,37,'Pollo a la plancha','Pechuga de pollo grillada',12.00,20,'https://picsum.photos/200?food10',1,20,'2025-11-20 02:04:46'),
(74,37,'Medallones de cerdo','Medallones con salsa de champiñones',17.50,14,'https://picsum.photos/200?food11',1,22,'2025-11-20 02:04:46'),
(75,38,'Ceviche mixto','Ceviche de pescado y mariscos frescos',13.80,25,'https://picsum.photos/200?food12',1,15,'2025-11-20 02:04:46'),
(76,38,'Camarones al ajillo','Camarones salteados en ajo',15.50,16,'https://picsum.photos/200?food13',1,18,'2025-11-20 02:04:46'),
(77,38,'Pulpo a la parrilla','Pulpo tierno a la parrilla',18.30,10,'https://picsum.photos/200?food14',1,20,'2025-11-20 02:04:46'),
(78,38,'Arroz marinero','Arroz con variedad de mariscos',14.90,13,'https://picsum.photos/200?food15',1,25,'2025-11-20 02:04:46'),
(79,39,'Tiramisú','Postre italiano con café',6.50,20,'https://picsum.photos/200?food16',1,5,'2025-11-20 02:04:46'),
(80,39,'Cheesecake','Pastel de queso clásico',6.80,18,'https://picsum.photos/200?food17',1,5,'2025-11-20 02:04:46'),
(81,39,'Helado artesanal','Helado de crema artesanal',4.90,30,'https://picsum.photos/200?food18',1,3,'2025-11-20 02:04:46'),
(82,39,'Brownie con helado','Brownie caliente con bola de helado',7.20,15,'https://picsum.photos/200?food19',1,8,'2025-11-20 02:04:46'),
(83,40,'Limonada natural','Refrescante limonada casera',3.50,40,'https://picsum.photos/200?food20',1,5,'2025-11-20 02:04:46'),
(84,40,'Mojito','Cóctel de ron con menta',5.80,25,'https://picsum.photos/200?food21',1,5,'2025-11-20 02:04:46'),
(85,40,'Café americano','Café filtrado clásico',2.50,50,'https://picsum.photos/200?food22',1,3,'2025-11-20 02:04:46'),
(86,40,'Chocolate caliente','Chocolate cremoso caliente',3.90,35,'https://picsum.photos/200?food23',1,5,'2025-11-20 02:04:46'),
(87,40,'Té verde','Té verde natural',2.80,45,'https://picsum.photos/200?food24',1,3,'2025-11-20 02:04:46');

-- =============================================
-- 8. TABLA: pre_pedidos
-- Pedidos anticipados de platos asociados a reservas
-- =============================================
DROP TABLE IF EXISTS `pre_pedidos`;
CREATE TABLE `pre_pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `plato_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reserva_id` (`reserva_id`),
  KEY `plato_id` (`plato_id`),
  CONSTRAINT `pre_pedidos_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pre_pedidos_ibfk_2` FOREIGN KEY (`plato_id`) REFERENCES `platos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Pedidos de platos realizados al momento de la reserva';

-- =============================================
-- 9. TABLA: notas_consumo
-- Notas de consumo (facturas) de cada reserva
-- =============================================
DROP TABLE IF EXISTS `notas_consumo`;
CREATE TABLE `notas_consumo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `numero_nota` varchar(20) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `impuesto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('borrador','finalizada','pagada','anulada') DEFAULT 'borrador',
  `metodo_pago` enum('efectivo','tarjeta','transferencia','mixto') DEFAULT NULL,
  `fecha_generacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_pago` timestamp NULL DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_nota` (`numero_nota`),
  KEY `reserva_id` (`reserva_id`),
  CONSTRAINT `notas_consumo_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Notas de consumo (facturas) con totales e impuestos';

-- =============================================
-- 10. TABLA: historial_estados
-- Auditoría de cambios de estado en el sistema
-- =============================================
DROP TABLE IF EXISTS `historial_estados`;
CREATE TABLE `historial_estados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tabla_referencia` enum('reservas','mesas','notas_consumo') NOT NULL,
  `registro_id` int(11) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `motivo` text DEFAULT NULL,
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `historial_estados_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `administradores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Registro de auditoría para cambios de estado';

-- Datos de ejemplo
INSERT INTO `historial_estados` VALUES 
(1,'mesas',2,'disponible','ocupada',NULL,NULL,'2025-11-07 02:39:41'),
(2,'mesas',6,'disponible','ocupada',NULL,NULL,'2025-11-07 02:39:41'),
(3,'mesas',7,'disponible','ocupada',NULL,NULL,'2025-11-07 02:39:41'),
(4,'mesas',9,'disponible','ocupada',NULL,NULL,'2025-11-07 02:39:41'),
(5,'mesas',4,'disponible','reservada',NULL,NULL,'2025-11-07 02:39:41'),
(6,'mesas',8,'disponible','reservada',NULL,NULL,'2025-11-07 02:39:41'),
(7,'mesas',5,'disponible','mantenimiento',NULL,NULL,'2025-11-07 02:39:41'),
(8,'reservas',3,'pendiente','confirmada',NULL,NULL,'2025-11-12 22:11:22'),
(9,'reservas',3,'confirmada','cancelada',NULL,NULL,'2025-11-12 22:11:27'),
(10,'reservas',3,'cancelada','pendiente',NULL,NULL,'2025-11-12 22:11:34'),
(11,'reservas',3,'pendiente','confirmada',NULL,NULL,'2025-11-12 22:14:05'),
(12,'reservas',2,'pendiente','en_curso',NULL,NULL,'2025-11-12 22:14:17');

-- =============================================
-- 11. TABLA: configuracion_restaurante
-- Configuración general del restaurante (horarios, etc.)
-- =============================================
DROP TABLE IF EXISTS `configuracion_restaurante`;
CREATE TABLE `configuracion_restaurante` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clave` VARCHAR(100) UNIQUE NOT NULL,
    `valor` TEXT NOT NULL,
    `descripcion` TEXT,
    `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Configuración dinámica del restaurante';

-- Datos de configuración
INSERT INTO `configuracion_restaurante` (`clave`, `valor`, `descripcion`) VALUES
('horario_lunes_viernes_inicio', '10:00', 'Hora de apertura de lunes a viernes'),
('horario_lunes_viernes_fin', '22:00', 'Hora de cierre de lunes a viernes'),
('horario_sabado_inicio', '11:00', 'Hora de apertura los sábados'),
('horario_sabado_fin', '23:00', 'Hora de cierre los sábados'),
('horario_domingo_inicio', '12:00', 'Hora de apertura los domingos'),
('horario_domingo_fin', '21:00', 'Hora de cierre los domingos'),
('dias_cerrado', '', 'Días que el restaurante está cerrado (ej: 25-12,01-01 para Navidad y Año Nuevo)'),
('reservas_activas', '1', '1=Permitir reservas, 0=No permitir reservas'),
('hora_apertura', '11:00', 'Hora de apertura general del restaurante'),
('hora_cierre', '23:00', 'Hora de cierre general del restaurante'),
('duracion_reserva', '90', 'Duración promedio de una reserva en minutos'),
('intervalo_reservas', '15', 'Tiempo de preparación entre reservas en minutos')
ON DUPLICATE KEY UPDATE valor=valor;

-- =============================================
-- 12. TABLA: notificaciones_whatsapp
-- Registro de notificaciones enviadas vía WhatsApp (Twilio)
-- =============================================
DROP TABLE IF EXISTS `notificaciones_whatsapp`;
CREATE TABLE `notificaciones_whatsapp` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de notificaciones WhatsApp enviadas con Twilio API';

-- =============================================
-- 13. TRIGGERS
-- Automatización de procesos mediante triggers
-- =============================================

-- Trigger 1: Actualizar precio de mesa antes de insertar (según capacidad)
DELIMITER $$
DROP TRIGGER IF EXISTS actualizar_precio_mesa_before_insert$$
CREATE TRIGGER actualizar_precio_mesa_before_insert
BEFORE INSERT ON mesas
FOR EACH ROW
BEGIN
    SET NEW.precio_reserva = CASE
        WHEN NEW.capacidad_maxima <= 2 THEN 5.00
        WHEN NEW.capacidad_maxima BETWEEN 3 AND 4 THEN 6.00
        WHEN NEW.capacidad_maxima BETWEEN 5 AND 6 THEN 8.00
        WHEN NEW.capacidad_maxima BETWEEN 7 AND 10 THEN 10.00
        WHEN NEW.capacidad_maxima > 10 THEN 15.00
        ELSE 5.00
    END;
END$$
DELIMITER ;

-- Trigger 2: Actualizar precio de mesa antes de actualizar (si cambia capacidad)
DELIMITER $$
DROP TRIGGER IF EXISTS actualizar_precio_mesa_before_update$$
CREATE TRIGGER actualizar_precio_mesa_before_update
BEFORE UPDATE ON mesas
FOR EACH ROW
BEGIN
    IF NEW.capacidad_maxima != OLD.capacidad_maxima THEN
        SET NEW.precio_reserva = CASE
            WHEN NEW.capacidad_maxima <= 2 THEN 5.00
            WHEN NEW.capacidad_maxima BETWEEN 3 AND 4 THEN 6.00
            WHEN NEW.capacidad_maxima BETWEEN 5 AND 6 THEN 8.00
            WHEN NEW.capacidad_maxima BETWEEN 7 AND 10 THEN 10.00
            WHEN NEW.capacidad_maxima > 10 THEN 15.00
            ELSE 5.00
        END;
    END IF;
END$$
DELIMITER ;

-- Trigger 3: Registrar cambios de estado en mesas
DELIMITER $$
DROP TRIGGER IF EXISTS tr_mesas_cambio_estado$$
CREATE TRIGGER tr_mesas_cambio_estado 
AFTER UPDATE ON mesas
FOR EACH ROW
BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO historial_estados (tabla_referencia, registro_id, estado_anterior, estado_nuevo, fecha_cambio)
        VALUES ('mesas', NEW.id, OLD.estado, NEW.estado, NOW());
    END IF;
END$$
DELIMITER ;

-- Trigger 4: Registrar cambios de estado en reservas
DELIMITER $$
DROP TRIGGER IF EXISTS tr_reservas_cambio_estado$$
CREATE TRIGGER tr_reservas_cambio_estado 
AFTER UPDATE ON reservas
FOR EACH ROW
BEGIN
    IF OLD.estado != NEW.estado THEN
        INSERT INTO historial_estados (tabla_referencia, registro_id, estado_anterior, estado_nuevo, fecha_cambio)
        VALUES ('reservas', NEW.id, OLD.estado, NEW.estado, NOW());
    END IF;
END$$
DELIMITER ;

-- =============================================
-- 14. PROCEDIMIENTOS ALMACENADOS
-- =============================================

-- Procedimiento: Activar reservas cuando llega su hora
DELIMITER $$
DROP PROCEDURE IF EXISTS activar_reservas_programadas$$
CREATE PROCEDURE activar_reservas_programadas()
BEGIN
    -- Cambiar estado de 'confirmada' a 'en_curso' cuando llega la fecha/hora
    UPDATE reservas 
    SET estado = 'en_curso'
    WHERE estado = 'confirmada'
    AND CONCAT(fecha_reserva, ' ', hora_reserva) <= NOW()
    AND CONCAT(fecha_reserva, ' ', hora_reserva) >= DATE_SUB(NOW(), INTERVAL 30 MINUTE);
    
    -- Finalizar reservas antiguas automáticamente (más de 3 horas)
    UPDATE reservas 
    SET estado = 'finalizada'
    WHERE estado = 'en_curso'
    AND CONCAT(fecha_reserva, ' ', hora_reserva) <= DATE_SUB(NOW(), INTERVAL 3 HOUR);
END$$
DELIMITER ;

-- =============================================
-- 15. VISTAS ÚTILES
-- =============================================

-- Vista: Reservas con información completa
DROP VIEW IF EXISTS v_reservas_completas;
CREATE VIEW v_reservas_completas AS
SELECT 
    r.id,
    r.fecha_reserva,
    r.hora_reserva,
    r.numero_personas,
    r.estado,
    c.nombre AS cliente_nombre,
    c.apellido AS cliente_apellido,
    c.telefono AS cliente_telefono,
    c.email AS cliente_email,
    m.numero_mesa,
    m.ubicacion,
    m.precio_reserva AS precio_mesa,
    IFNULL(SUM(pp.subtotal), 0) AS total_platos,
    (m.precio_reserva + IFNULL(SUM(pp.subtotal), 0)) AS total_reserva
FROM reservas r
INNER JOIN clientes c ON r.cliente_id = c.id
INNER JOIN mesas m ON r.mesa_id = m.id
LEFT JOIN pre_pedidos pp ON r.id = pp.reserva_id
GROUP BY r.id;

-- Vista: Mesas disponibles
DROP VIEW IF EXISTS v_mesas_disponibles;
CREATE VIEW v_mesas_disponibles AS
SELECT 
    numero_mesa,
    CONCAT(capacidad_minima, '-', capacidad_maxima) AS capacidad,
    precio_reserva,
    ubicacion,
    descripcion
FROM mesas
WHERE estado = 'disponible'
ORDER BY ubicacion, numero_mesa;

-- =============================================
-- FIN DEL SCRIPT COMPLETO
-- =============================================
-- Para ejecutar este script:
-- mysql -u crud_proyecto -p12345 < crud_proyecto_COMPLETO_UNIFICADO.sql
-- =============================================
