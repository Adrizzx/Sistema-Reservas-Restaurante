<?php
/**
 * Validador de Reservas
 * Valida fechas, horas y restricciones de tiempo para reservas
 */

class ValidadorReserva {
    
    /**
     * Valida que la fecha de reserva no sea en el pasado
     * @param string $fecha Fecha en formato YYYY-MM-DD
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public static function validarFecha($fecha) {
        if (empty($fecha)) {
            return [
                'valido' => false,
                'mensaje' => 'La fecha de reserva es requerida'
            ];
        }
        
        $fechaReserva = new DateTime($fecha);
        $hoy = new DateTime('today');
        
        if ($fechaReserva < $hoy) {
            return [
                'valido' => false,
                'mensaje' => 'No se pueden hacer reservas para días pasados. Solo puede reservar desde hoy en adelante'
            ];
        }
        
        return [
            'valido' => true,
            'mensaje' => 'Fecha válida'
        ];
    }
    
    /**
     * Valida que la hora de reserva cumpla con el mínimo de 2 horas de anticipación
     * @param string $fecha Fecha en formato YYYY-MM-DD
     * @param string $hora Hora en formato HH:MM:SS
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public static function validarHoraAnticipacion($fecha, $hora) {
        if (empty($fecha) || empty($hora)) {
            return [
                'valido' => false,
                'mensaje' => 'La fecha y hora son requeridas'
            ];
        }
        
        try {
            // Crear fecha y hora de la reserva
            $fechaHoraReserva = new DateTime("$fecha $hora");
            
            // Obtener fecha y hora actual
            $ahora = new DateTime();
            
            // Calcular el tiempo mínimo requerido (2 horas desde ahora)
            $minimoRequerido = clone $ahora;
            $minimoRequerido->add(new DateInterval('PT2H')); // PT2H = Period Time 2 Hours
            
            // Verificar que la reserva sea al menos 2 horas después
            if ($fechaHoraReserva < $minimoRequerido) {
                return [
                    'valido' => false,
                    'mensaje' => 'Recuerde que solo puede reservar con al menos 2 horas de anticipación desde la hora actual'
                ];
            }
            
            return [
                'valido' => true,
                'mensaje' => 'Hora válida'
            ];
            
        } catch (Exception $e) {
            return [
                'valido' => false,
                'mensaje' => 'Formato de fecha u hora inválido'
            ];
        }
    }
    
    /**
     * Valida que la fecha y hora estén dentro del horario del restaurante
     * @param string $fecha Fecha en formato YYYY-MM-DD
     * @param string $hora Hora en formato HH:MM:SS
     * @param mysqli $mysqli Conexión a la base de datos
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public static function validarHorarioRestaurante($fecha, $hora, $mysqli) {
        try {
            // Obtener configuración de horarios
            $stmt = $mysqli->prepare("SELECT hora_apertura, hora_cierre, dias_cerrados FROM configuracion_restaurante LIMIT 1");
            
            if (!$stmt) {
                return [
                    'valido' => false,
                    'mensaje' => 'Error al verificar horario del restaurante'
                ];
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $config = $result->fetch_assoc();
            $stmt->close();
            
            if (!$config) {
                return [
                    'valido' => true,
                    'mensaje' => 'Sin restricciones de horario configuradas'
                ];
            }
            
            // Verificar día de la semana
            $diaSemana = date('w', strtotime($fecha)); // 0 = Domingo, 6 = Sábado
            $diasCerrados = !empty($config['dias_cerrados']) ? explode(',', $config['dias_cerrados']) : [];
            
            if (in_array($diaSemana, $diasCerrados)) {
                $nombresDias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                return [
                    'valido' => false,
                    'mensaje' => 'El restaurante está cerrado los ' . $nombresDias[$diaSemana]
                ];
            }
            
            // Verificar hora
            $horaReserva = substr($hora, 0, 5); // HH:MM
            $horaApertura = substr($config['hora_apertura'], 0, 5);
            $horaCierre = substr($config['hora_cierre'], 0, 5);
            
            if ($horaReserva < $horaApertura) {
                return [
                    'valido' => false,
                    'mensaje' => "La reserva es antes del horario de apertura ($horaApertura)"
                ];
            }
            
            if ($horaReserva > $horaCierre) {
                return [
                    'valido' => false,
                    'mensaje' => "La reserva es después del horario de cierre ($horaCierre)"
                ];
            }
            
            return [
                'valido' => true,
                'mensaje' => 'Horario válido'
            ];
            
        } catch (Exception $e) {
            return [
                'valido' => false,
                'mensaje' => 'Error al validar horario: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Valida toda la reserva (fecha, hora y anticipación)
     * @param string $fecha Fecha en formato YYYY-MM-DD
     * @param string $hora Hora en formato HH:MM:SS
     * @param mysqli $mysqli Conexión a la base de datos
     * @return array ['valido' => bool, 'mensaje' => string, 'errores' => array]
     */
    public static function validarReservaCompleta($fecha, $hora, $mysqli) {
        $errores = [];
        
        // Validar fecha
        $resultadoFecha = self::validarFecha($fecha);
        if (!$resultadoFecha['valido']) {
            $errores[] = $resultadoFecha['mensaje'];
        }
        
        // Validar anticipación de 2 horas
        $resultadoAnticipacion = self::validarHoraAnticipacion($fecha, $hora);
        if (!$resultadoAnticipacion['valido']) {
            $errores[] = $resultadoAnticipacion['mensaje'];
        }
        
        // Validar horario del restaurante
        $resultadoHorario = self::validarHorarioRestaurante($fecha, $hora, $mysqli);
        if (!$resultadoHorario['valido']) {
            $errores[] = $resultadoHorario['mensaje'];
        }
        
        if (count($errores) > 0) {
            return [
                'valido' => false,
                'mensaje' => implode('. ', $errores),
                'errores' => $errores
            ];
        }
        
        return [
            'valido' => true,
            'mensaje' => 'Reserva válida',
            'errores' => []
        ];
    }
}
?>
