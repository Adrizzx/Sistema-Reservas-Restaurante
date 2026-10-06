-- Agregar campo motivo_cancelacion a la tabla reservas si no existe
ALTER TABLE reservas 
ADD COLUMN IF NOT EXISTS motivo_cancelacion VARCHAR(255) NULL DEFAULT NULL
AFTER estado;

-- Agregar índice para búsquedas más rápidas
ALTER TABLE reservas 
ADD INDEX idx_estado_fecha (estado, fecha_reserva);

-- Verificar estructura
DESCRIBE reservas;
