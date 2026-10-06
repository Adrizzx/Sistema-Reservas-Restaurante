-- =============================================
-- SCRIPT DE LIMPIEZA Y DATOS DE PRUEBA
-- Sistema de Reservas - Le Salon de Lumière
-- =============================================
-- Fecha: Diciembre 2025
-- Descripción: Limpia reservas y genera datos de prueba
--              desde Noviembre 2025 hasta Diciembre 10, 2025
-- =============================================

USE crud_proyecto;

-- =============================================
-- 1. LIMPIAR TODAS LAS RESERVAS
-- =============================================
-- Desactivar verificación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar tablas relacionadas primero
TRUNCATE TABLE pre_pedidos;
TRUNCATE TABLE notas_consumo;
TRUNCATE TABLE auditoria_reservas;

-- Limpiar y resetear la tabla de reservas
TRUNCATE TABLE reservas;

-- Reactivar verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 2. RESETEAR AUTO_INCREMENT
-- =============================================
ALTER TABLE reservas AUTO_INCREMENT = 1;
ALTER TABLE pre_pedidos AUTO_INCREMENT = 1;
ALTER TABLE notas_consumo AUTO_INCREMENT = 1;

-- =============================================
-- 3. ACTUALIZAR ESTADO DE MESAS A DISPONIBLE
-- =============================================
UPDATE mesas SET estado = 'disponible';

-- =============================================
-- 4. DATOS QUEMADOS: NOVIEMBRE 2025
-- =============================================
-- Reservas de Noviembre 1-15, 2025
INSERT INTO reservas (cliente_id, mesa_id, fecha_reserva, hora_reserva, numero_personas, estado) VALUES
-- Noviembre 1, 2025
(1, 1, '2025-11-01', '18:00:00', 2, 'finalizada'),
(2, 3, '2025-11-01', '19:30:00', 4, 'finalizada'),
(3, 5, '2025-11-01', '20:00:00', 6, 'finalizada'),

-- Noviembre 2, 2025
(4, 2, '2025-11-02', '17:00:00', 3, 'finalizada'),
(5, 7, '2025-11-02', '19:00:00', 8, 'finalizada'),
(6, 9, '2025-11-02', '20:30:00', 5, 'finalizada'),

-- Noviembre 3, 2025
(7, 1, '2025-11-03', '18:30:00', 2, 'finalizada'),
(8, 4, '2025-11-03', '19:00:00', 4, 'finalizada'),
(9, 6, '2025-11-03', '20:00:00', 7, 'finalizada'),
(10, 8, '2025-11-03', '21:00:00', 10, 'finalizada'),

-- Noviembre 4, 2025
(1, 2, '2025-11-04', '17:30:00', 3, 'finalizada'),
(2, 5, '2025-11-04', '19:00:00', 6, 'finalizada'),
(3, 10, '2025-11-04', '20:00:00', 4, 'finalizada'),

-- Noviembre 5, 2025
(4, 1, '2025-11-05', '18:00:00', 2, 'finalizada'),
(5, 3, '2025-11-05', '19:30:00', 5, 'finalizada'),
(6, 7, '2025-11-05', '20:00:00', 8, 'finalizada'),
(7, 9, '2025-11-05', '21:00:00', 12, 'finalizada'),

-- Noviembre 6, 2025
(8, 2, '2025-11-06', '17:00:00', 3, 'finalizada'),
(9, 4, '2025-11-06', '18:30:00', 4, 'finalizada'),
(10, 6, '2025-11-06', '19:30:00', 6, 'finalizada'),
(11, 8, '2025-11-06', '20:30:00', 9, 'finalizada'),

-- Noviembre 7, 2025
(1, 1, '2025-11-07', '18:00:00', 2, 'finalizada'),
(2, 5, '2025-11-07', '19:00:00', 7, 'finalizada'),
(3, 10, '2025-11-07', '20:00:00', 4, 'finalizada'),

-- Noviembre 8, 2025 (Viernes - más reservas)
(4, 1, '2025-11-08', '17:00:00', 2, 'finalizada'),
(5, 2, '2025-11-08', '17:30:00', 4, 'finalizada'),
(6, 3, '2025-11-08', '18:00:00', 5, 'finalizada'),
(7, 4, '2025-11-08', '18:30:00', 3, 'finalizada'),
(8, 5, '2025-11-08', '19:00:00', 6, 'finalizada'),
(9, 6, '2025-11-08', '19:30:00', 7, 'finalizada'),
(10, 7, '2025-11-08', '20:00:00', 8, 'finalizada'),
(11, 8, '2025-11-08', '20:30:00', 10, 'finalizada'),
(1, 9, '2025-11-08', '21:00:00', 12, 'finalizada'),

-- Noviembre 9, 2025 (Sábado - muchas reservas)
(2, 1, '2025-11-09', '17:00:00', 2, 'finalizada'),
(3, 2, '2025-11-09', '17:30:00', 3, 'finalizada'),
(4, 3, '2025-11-09', '18:00:00', 5, 'finalizada'),
(5, 4, '2025-11-09', '18:30:00', 4, 'finalizada'),
(6, 5, '2025-11-09', '19:00:00', 6, 'finalizada'),
(7, 6, '2025-11-09', '19:30:00', 8, 'finalizada'),
(8, 7, '2025-11-09', '20:00:00', 9, 'finalizada'),
(9, 8, '2025-11-09', '20:30:00', 10, 'finalizada'),
(10, 9, '2025-11-09', '21:00:00', 14, 'finalizada'),
(11, 10, '2025-11-09', '21:30:00', 4, 'finalizada'),

-- Noviembre 10, 2025
(1, 3, '2025-11-10', '18:00:00', 4, 'finalizada'),
(2, 6, '2025-11-10', '19:00:00', 7, 'finalizada'),
(3, 9, '2025-11-10', '20:00:00', 12, 'finalizada'),

-- Noviembre 11, 2025
(4, 1, '2025-11-11', '17:30:00', 2, 'finalizada'),
(5, 4, '2025-11-11', '19:00:00', 4, 'finalizada'),
(6, 8, '2025-11-11', '20:30:00', 10, 'finalizada'),

-- Noviembre 12, 2025
(7, 2, '2025-11-12', '18:00:00', 3, 'finalizada'),
(8, 5, '2025-11-12', '19:30:00', 6, 'finalizada'),
(9, 7, '2025-11-12', '20:00:00', 8, 'finalizada'),

-- Noviembre 13, 2025
(10, 1, '2025-11-13', '17:00:00', 2, 'finalizada'),
(11, 3, '2025-11-13', '19:00:00', 5, 'finalizada'),
(1, 6, '2025-11-13', '20:30:00', 7, 'finalizada'),

-- Noviembre 14, 2025
(2, 2, '2025-11-14', '18:30:00', 4, 'finalizada'),
(3, 4, '2025-11-14', '19:00:00', 4, 'finalizada'),
(4, 10, '2025-11-14', '20:00:00', 4, 'finalizada'),

-- Noviembre 15, 2025 (Viernes)
(5, 1, '2025-11-15', '17:00:00', 2, 'finalizada'),
(6, 2, '2025-11-15', '18:00:00', 3, 'finalizada'),
(7, 3, '2025-11-15', '18:30:00', 5, 'finalizada'),
(8, 5, '2025-11-15', '19:00:00', 6, 'finalizada'),
(9, 7, '2025-11-15', '19:30:00', 8, 'finalizada'),
(10, 8, '2025-11-15', '20:00:00', 10, 'finalizada'),
(11, 9, '2025-11-15', '20:30:00', 12, 'finalizada'),

-- Noviembre 16, 2025 (Sábado)
(1, 1, '2025-11-16', '17:00:00', 2, 'finalizada'),
(2, 2, '2025-11-16', '17:30:00', 4, 'finalizada'),
(3, 3, '2025-11-16', '18:00:00', 5, 'finalizada'),
(4, 4, '2025-11-16', '18:30:00', 4, 'finalizada'),
(5, 5, '2025-11-16', '19:00:00', 7, 'finalizada'),
(6, 6, '2025-11-16', '19:30:00', 8, 'finalizada'),
(7, 7, '2025-11-16', '20:00:00', 9, 'finalizada'),
(8, 8, '2025-11-16', '20:30:00', 10, 'finalizada'),
(9, 9, '2025-11-16', '21:00:00', 15, 'finalizada'),

-- Noviembre 17-30, 2025 (más datos)
(10, 1, '2025-11-17', '18:00:00', 2, 'finalizada'),
(11, 4, '2025-11-17', '19:30:00', 4, 'finalizada'),
(1, 7, '2025-11-17', '20:00:00', 8, 'finalizada'),

(2, 2, '2025-11-18', '17:30:00', 3, 'finalizada'),
(3, 5, '2025-11-18', '19:00:00', 6, 'finalizada'),
(4, 9, '2025-11-18', '20:30:00', 12, 'finalizada'),

(5, 1, '2025-11-19', '18:00:00', 2, 'finalizada'),
(6, 3, '2025-11-19', '19:00:00', 5, 'finalizada'),
(7, 8, '2025-11-19', '20:00:00', 10, 'finalizada'),

(8, 2, '2025-11-20', '17:00:00', 4, 'finalizada'),
(9, 6, '2025-11-20', '19:30:00', 7, 'finalizada'),
(10, 10, '2025-11-20', '20:00:00', 4, 'finalizada'),

(11, 1, '2025-11-21', '18:30:00', 2, 'finalizada'),
(1, 4, '2025-11-21', '19:00:00', 4, 'finalizada'),
(2, 7, '2025-11-21', '20:30:00', 8, 'finalizada'),

-- Noviembre 22, 2025 (Viernes)
(3, 1, '2025-11-22', '17:00:00', 2, 'finalizada'),
(4, 2, '2025-11-22', '18:00:00', 3, 'finalizada'),
(5, 3, '2025-11-22', '18:30:00', 5, 'finalizada'),
(6, 5, '2025-11-22', '19:00:00', 6, 'finalizada'),
(7, 6, '2025-11-22', '19:30:00', 7, 'finalizada'),
(8, 8, '2025-11-22', '20:00:00', 10, 'finalizada'),
(9, 9, '2025-11-22', '20:30:00', 13, 'finalizada'),

-- Noviembre 23, 2025 (Sábado)
(10, 1, '2025-11-23', '17:00:00', 2, 'finalizada'),
(11, 2, '2025-11-23', '17:30:00', 4, 'finalizada'),
(1, 3, '2025-11-23', '18:00:00', 5, 'finalizada'),
(2, 4, '2025-11-23', '18:30:00', 4, 'finalizada'),
(3, 5, '2025-11-23', '19:00:00', 6, 'finalizada'),
(4, 6, '2025-11-23', '19:30:00', 8, 'finalizada'),
(5, 7, '2025-11-23', '20:00:00', 9, 'finalizada'),
(6, 8, '2025-11-23', '20:30:00', 10, 'finalizada'),
(7, 9, '2025-11-23', '21:00:00', 14, 'finalizada'),

(8, 1, '2025-11-24', '18:00:00', 2, 'finalizada'),
(9, 5, '2025-11-24', '19:30:00', 6, 'finalizada'),

(10, 2, '2025-11-25', '17:30:00', 3, 'finalizada'),
(11, 6, '2025-11-25', '19:00:00', 7, 'finalizada'),

(1, 1, '2025-11-26', '18:00:00', 2, 'finalizada'),
(2, 4, '2025-11-26', '19:30:00', 4, 'finalizada'),

(3, 3, '2025-11-27', '17:00:00', 5, 'finalizada'),
(4, 7, '2025-11-27', '19:00:00', 8, 'finalizada'),

(5, 2, '2025-11-28', '18:30:00', 3, 'finalizada'),
(6, 8, '2025-11-28', '20:00:00', 10, 'finalizada'),

-- Noviembre 29, 2025 (Viernes)
(7, 1, '2025-11-29', '17:00:00', 2, 'finalizada'),
(8, 2, '2025-11-29', '18:00:00', 4, 'finalizada'),
(9, 3, '2025-11-29', '18:30:00', 5, 'finalizada'),
(10, 5, '2025-11-29', '19:00:00', 6, 'finalizada'),
(11, 7, '2025-11-29', '19:30:00', 8, 'finalizada'),
(1, 8, '2025-11-29', '20:00:00', 10, 'finalizada'),
(2, 9, '2025-11-29', '20:30:00', 12, 'finalizada'),

-- Noviembre 30, 2025 (Sábado)
(3, 1, '2025-11-30', '17:00:00', 2, 'finalizada'),
(4, 2, '2025-11-30', '17:30:00', 3, 'finalizada'),
(5, 3, '2025-11-30', '18:00:00', 5, 'finalizada'),
(6, 4, '2025-11-30', '18:30:00', 4, 'finalizada'),
(7, 5, '2025-11-30', '19:00:00', 7, 'finalizada'),
(8, 6, '2025-11-30', '19:30:00', 8, 'finalizada'),
(9, 7, '2025-11-30', '20:00:00', 9, 'finalizada'),
(10, 8, '2025-11-30', '20:30:00', 10, 'finalizada'),
(11, 9, '2025-11-30', '21:00:00', 15, 'finalizada');

-- =============================================
-- 5. DATOS QUEMADOS: DICIEMBRE 1-10, 2025
-- =============================================
INSERT INTO reservas (cliente_id, mesa_id, fecha_reserva, hora_reserva, numero_personas, estado) VALUES
-- Diciembre 1, 2025
(1, 1, '2025-12-01', '18:00:00', 2, 'finalizada'),
(2, 3, '2025-12-01', '19:00:00', 4, 'finalizada'),
(3, 6, '2025-12-01', '20:00:00', 7, 'finalizada'),

-- Diciembre 2, 2025
(4, 2, '2025-12-02', '17:30:00', 3, 'finalizada'),
(5, 5, '2025-12-02', '19:00:00', 6, 'finalizada'),
(6, 8, '2025-12-02', '20:30:00', 10, 'finalizada'),

-- Diciembre 3, 2025
(7, 1, '2025-12-03', '18:00:00', 2, 'finalizada'),
(8, 4, '2025-12-03', '19:30:00', 4, 'finalizada'),
(9, 7, '2025-12-03', '20:00:00', 8, 'finalizada'),

-- Diciembre 4, 2025
(10, 2, '2025-12-04', '17:00:00', 3, 'finalizada'),
(11, 5, '2025-12-04', '19:00:00', 6, 'finalizada'),
(1, 9, '2025-12-04', '20:30:00', 12, 'finalizada'),

-- Diciembre 5, 2025
(2, 1, '2025-12-05', '18:30:00', 2, 'finalizada'),
(3, 3, '2025-12-05', '19:00:00', 5, 'finalizada'),
(4, 8, '2025-12-05', '20:00:00', 10, 'finalizada'),

-- Diciembre 6, 2025 (Viernes)
(5, 1, '2025-12-06', '17:00:00', 2, 'finalizada'),
(6, 2, '2025-12-06', '17:30:00', 3, 'finalizada'),
(7, 3, '2025-12-06', '18:00:00', 5, 'finalizada'),
(8, 4, '2025-12-06', '18:30:00', 4, 'finalizada'),
(9, 5, '2025-12-06', '19:00:00', 6, 'finalizada'),
(10, 6, '2025-12-06', '19:30:00', 7, 'finalizada'),
(11, 7, '2025-12-06', '20:00:00', 9, 'finalizada'),
(1, 8, '2025-12-06', '20:30:00', 10, 'finalizada'),
(2, 9, '2025-12-06', '21:00:00', 13, 'finalizada'),

-- Diciembre 7, 2025 (Sábado)
(3, 1, '2025-12-07', '17:00:00', 2, 'finalizada'),
(4, 2, '2025-12-07', '17:30:00', 4, 'finalizada'),
(5, 3, '2025-12-07', '18:00:00', 5, 'finalizada'),
(6, 4, '2025-12-07', '18:30:00', 4, 'finalizada'),
(7, 5, '2025-12-07', '19:00:00', 6, 'finalizada'),
(8, 6, '2025-12-07', '19:30:00', 8, 'finalizada'),
(9, 7, '2025-12-07', '20:00:00', 9, 'finalizada'),
(10, 8, '2025-12-07', '20:30:00', 10, 'finalizada'),
(11, 9, '2025-12-07', '21:00:00', 14, 'finalizada'),
(1, 10, '2025-12-07', '21:30:00', 4, 'finalizada'),

-- Diciembre 8, 2025
(2, 1, '2025-12-08', '18:00:00', 2, 'finalizada'),
(3, 4, '2025-12-08', '19:00:00', 4, 'finalizada'),
(4, 7, '2025-12-08', '20:30:00', 8, 'finalizada'),

-- Diciembre 9, 2025
(5, 2, '2025-12-09', '17:30:00', 3, 'finalizada'),
(6, 5, '2025-12-09', '19:00:00', 6, 'finalizada'),
(7, 8, '2025-12-09', '20:00:00', 10, 'finalizada'),

-- Diciembre 10, 2025 (HOY)
(8, 1, '2025-12-10', '18:00:00', 2, 'confirmada'),
(9, 3, '2025-12-10', '19:00:00', 4, 'confirmada'),
(10, 6, '2025-12-10', '19:30:00', 7, 'confirmada'),
(11, 8, '2025-12-10', '20:00:00', 10, 'confirmada');

-- =============================================
-- 6. ACTUALIZAR ESTADOS DE MESAS SEGÚN HOY
-- =============================================
-- Marcar como reservadas las mesas de hoy
UPDATE mesas SET estado = 'reservada' WHERE id IN (1, 3, 6, 8);

-- =============================================
-- 7. VERIFICACIÓN Y ESTADÍSTICAS
-- =============================================
SELECT 
    '====== RESUMEN DE DATOS CARGADOS ======' as info;

SELECT 
    DATE_FORMAT(fecha_reserva, '%Y-%m') as mes,
    COUNT(*) as total_reservas,
    SUM(CASE WHEN estado = 'finalizada' THEN 1 ELSE 0 END) as finalizadas,
    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
    SUM(CASE WHEN estado = 'en_curso' THEN 1 ELSE 0 END) as en_curso,
    SUM(numero_personas) as total_personas
FROM reservas
GROUP BY DATE_FORMAT(fecha_reserva, '%Y-%m')
ORDER BY mes;

SELECT 
    '====== RESERVAS POR DÍA EN DICIEMBRE ======' as info;

SELECT 
    fecha_reserva,
    COUNT(*) as reservas,
    SUM(numero_personas) as personas,
    GROUP_CONCAT(CONCAT('Mesa ', numero_mesa) SEPARATOR ', ') as mesas_usadas
FROM reservas r
JOIN mesas m ON r.mesa_id = m.id
WHERE fecha_reserva >= '2025-12-01' AND fecha_reserva <= '2025-12-10'
GROUP BY fecha_reserva
ORDER BY fecha_reserva;

SELECT 
    '====== ESTADO ACTUAL DE MESAS ======' as info;

SELECT 
    estado,
    COUNT(*) as cantidad
FROM mesas
GROUP BY estado;

-- =============================================
-- FIN DEL SCRIPT
-- =============================================
SELECT 
    '✅ SCRIPT COMPLETADO EXITOSAMENTE' as resultado,
    COUNT(*) as total_reservas_cargadas,
    MIN(fecha_reserva) as primera_reserva,
    MAX(fecha_reserva) as ultima_reserva
FROM reservas;
