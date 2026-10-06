-- Script para actualizar precios de mesas automáticamente según capacidad

-- Primero, actualizar todos los precios existentes
UPDATE mesas SET precio_reserva = CASE
    WHEN capacidad_maxima <= 2 THEN 5.00
    WHEN capacidad_maxima BETWEEN 3 AND 4 THEN 6.00
    WHEN capacidad_maxima BETWEEN 5 AND 6 THEN 8.00
    WHEN capacidad_maxima BETWEEN 7 AND 10 THEN 10.00
    WHEN capacidad_maxima > 10 THEN 15.00
    ELSE 5.00
END;

-- Eliminar trigger si ya existe
DROP TRIGGER IF EXISTS actualizar_precio_mesa_before_insert;
DROP TRIGGER IF EXISTS actualizar_precio_mesa_before_update;

-- Crear trigger para INSERT (cuando se crea una mesa nueva)
DELIMITER $$
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

-- Crear trigger para UPDATE (cuando se edita la capacidad)
DELIMITER $$
CREATE TRIGGER actualizar_precio_mesa_before_update
BEFORE UPDATE ON mesas
FOR EACH ROW
BEGIN
    -- Solo actualizar si cambió la capacidad
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

-- Mostrar resultado
SELECT 
    numero_mesa,
    CONCAT(capacidad_minima, '-', capacidad_maxima) as capacidad,
    CONCAT('$', FORMAT(precio_reserva, 2)) as precio,
    ubicacion,
    estado
FROM mesas 
ORDER BY capacidad_maxima, numero_mesa;
