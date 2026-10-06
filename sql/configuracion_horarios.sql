-- Tabla simple de configuración del restaurante
CREATE TABLE IF NOT EXISTS configuracion_restaurante (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    descripcion TEXT,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar configuraciones por defecto
INSERT INTO configuracion_restaurante (clave, valor, descripcion) VALUES
('horario_lunes_viernes_inicio', '10:00', 'Hora de apertura de lunes a viernes'),
('horario_lunes_viernes_fin', '22:00', 'Hora de cierre de lunes a viernes'),
('horario_sabado_inicio', '11:00', 'Hora de apertura los sábados'),
('horario_sabado_fin', '23:00', 'Hora de cierre los sábados'),
('horario_domingo_inicio', '12:00', 'Hora de apertura los domingos'),
('horario_domingo_fin', '21:00', 'Hora de cierre los domingos'),
('dias_cerrado', '', 'Días que el restaurante está cerrado (ej: 25-12,01-01 para Navidad y Año Nuevo)'),
('reservas_activas', '1', '1=Permitir reservas, 0=No permitir reservas')
ON DUPLICATE KEY UPDATE valor=valor;
