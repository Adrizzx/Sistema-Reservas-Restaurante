<?php
header('Content-Type: application/json; charset=UTF-8');

require_once '../../conexion/db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fecha = $input['fecha'] ?? date('Y-m-d');
    $hora = $input['hora'] ?? date('H:i');
    
    // Obtener configuraciones
    $stmt = $pdo->query("SELECT clave, valor FROM configuracion_restaurante");
    $configs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configs[$row['clave']] = $row['valor'];
    }
    
    // Verificar si las reservas están activas
    if (isset($configs['reservas_activas']) && $configs['reservas_activas'] !== '1') {
        echo json_encode([
            'success' => false,
            'valido' => false,
            'message' => 'Las reservas están temporalmente deshabilitadas'
        ]);
        exit;
    }
    
    // Validación de fecha
    $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$fecha_obj) {
        echo json_encode(['success' => false, 'valido' => false, 'message' => 'Formato de fecha inválido']);
        exit;
    }
    // Normalizar hora de fecha a 00:00 para comparaciones de días
    $fecha_obj->setTime(0, 0, 0);

    // Validar que no sea fecha pasada
    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0);
    if ($fecha_obj < $hoy) {
        echo json_encode(['success' => false, 'valido' => false, 'message' => 'No se pueden hacer reservas con fechas pasadas']);
        exit;
    }
    
    // Validar que no sea más de 180 días (aprox. 6 meses) adelante
    $max_adelanto = new DateTime();
    $max_adelanto->setTime(0, 0, 0);
    $max_adelanto->modify('+180 days');
    // Si la fecha es ESTRICTAMENTE mayor que la máxima permitida, rechazar
    // +180 días debe ser permitido, +181 debe ser rechazado
    if ($fecha_obj > $max_adelanto) {
        echo json_encode(['success' => false, 'valido' => false, 'message' => 'No se pueden hacer reservas con más de 6 meses de anticipación']);
        exit;
    }
    
    // Obtener hora de apertura y cierre según día de la semana
    $dia_semana = (int)$fecha_obj->format('N'); // 1=Lunes, 7=Domingo
    if ($dia_semana >= 1 && $dia_semana <= 5) { // Lunes-Viernes
        $hora_apertura = '10:00';
        $hora_cierre = '22:00';
    } elseif ($dia_semana == 6) { // Sábado
        $hora_apertura = '11:00';
        $hora_cierre = '23:00';
    } elseif ($dia_semana == 7) { // Domingo
        $hora_apertura = '12:00';
        $hora_cierre = '21:00';
    } else {
        echo json_encode([
            'success' => false,
            'valido' => false,
            'message' => 'Día de la semana inválido'
        ]);
        exit;
    }
    
    $dias_cerrados = $configs['dias_cerrados'] ?? '';
    
    // Validar día de la semana no esté cerrado
    if (!empty($dias_cerrados)) {
        $diaSemana = $fecha_obj->format('w'); // 0=Domingo, 1=Lunes, ..., 6=Sábado
        $diasCerradosArray = array_map('trim', explode(',', $dias_cerrados));
        
        if (in_array($diaSemana, $diasCerradosArray)) {
            $nombresEspañol = [
                0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
                4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'
            ];
            echo json_encode([
                'success' => false,
                'valido' => false,
                'message' => "No se pueden hacer reservas los días {$nombresEspañol[$diaSemana]}. El restaurante está cerrado."
            ]);
            exit;
        }
    }
    
    // Validar que la hora esté dentro del rango de apertura-cierre
    $hora_valida = ($hora >= $hora_apertura && $hora <= $hora_cierre);
    
    if ($hora_valida) {
        // Validar minutos (solo en intervalos de 30)
        $minutos = (int)substr($hora, 3, 2);
        if ($minutos != 0 && $minutos != 30) {
            $hora_valida = false;
            $message_extra = 'Las reservas solo se pueden hacer en intervalos de 30 minutos';
        }
    }
    
    if (!$hora_valida) {
        $message = isset($message_extra) ? $message_extra : "El horario de reserva debe estar entre $hora_apertura y $hora_cierre";
        echo json_encode([
            'success' => false,
            'valido' => false,
            'message' => $message,
            'horario_disponible' => [
                'inicio' => $hora_apertura,
                'fin' => $hora_cierre
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'valido' => true,
            'message' => 'Horario válido',
            'horario_disponible' => [
                'inicio' => $hora_apertura,
                'fin' => $hora_cierre
            ]
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al validar horario: ' . $e->getMessage()
    ]);
}
?>
