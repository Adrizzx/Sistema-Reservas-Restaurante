-- Agregar configuraciones adicionales para horarios del restaurante
INSERT INTO configuracion_restaurante (clave, valor, descripcion) VALUES
('hora_apertura', '11:00', 'Hora de apertura general del restaurante'),
('hora_cierre', '23:00', 'Hora de cierre general del restaurante'),
('duracion_reserva', '90', 'Duración promedio de una reserva en minutos'),
('intervalo_reservas', '15', 'Tiempo de preparación entre reservas en minutos')
ON DUPLICATE KEY UPDATE valor=valor;
