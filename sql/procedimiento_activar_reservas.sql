DELIMITER $$

-- Procedimiento almacenado para activar reservas cuando llega su hora
DROP PROCEDURE IF EXISTS activar_reservas_programadas$$

CREATE PROCEDURE activar_reservas_programadas()
BEGIN
    -- Cambiar estado de 'confirmada' a 'en_curso' cuando llega la fecha/hora
    UPDATE reservas 
    SET estado = 'en_curso'
    WHERE estado = 'confirmada'
    AND CONCAT(fecha_reserva, ' ', hora_reserva) <= NOW()
    AND CONCAT(fecha_reserva, ' ', hora_reserva) >= DATE_SUB(NOW(), INTERVAL 30 MINUTE);
    
    -- Finalizar reservas en curso que ya pasaron (más de 3 horas)
    UPDATE reservas 
    SET estado = 'finalizada'
    WHERE estado = 'en_curso'
    AND CONCAT(fecha_reserva, ' ', hora_reserva) <= DATE_SUB(NOW(), INTERVAL 3 HOUR);
    
    -- NUEVO: Finalizar reservas confirmadas que ya pasaron hace más de 1 día
    UPDATE reservas 
    SET estado = 'finalizada'
    WHERE estado = 'confirmada'
    AND fecha_reserva < CURDATE();
    
    -- Liberar mesas de reservas finalizadas
    UPDATE mesas m
    INNER JOIN reservas r ON m.id = r.id_mesa
    SET m.estado = 'disponible'
    WHERE r.estado = 'finalizada'
    AND m.estado IN ('reservada', 'ocupada');
END$$

DELIMITER ;

-- Nota: Este procedimiento debe ser ejecutado periódicamente.
-- Opciones para ejecutarlo:
-- 1. Desde el panel de admin (botón manual)
-- 2. Con un cron job: */5 * * * * mysql -u crud_proyecto -p12345 crud_proyecto -e "CALL activar_reservas_programadas();"
-- 3. Con un script PHP que se ejecute periódicamente

-- Para ejecutar manualmente:
-- CALL activar_reservas_programadas();
