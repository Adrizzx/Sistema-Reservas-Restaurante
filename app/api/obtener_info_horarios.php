<?php
header('Content-Type: application/json; charset=UTF-8');

require_once '../../conexion/db.php';

try {
    // Obtener configuraciones de horarios
    $stmt = $pdo->query("SELECT clave, valor FROM configuracion_restaurante");
    $configs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configs[$row['clave']] = $row['valor'];
    }
    
    // Obtener horarios según día de la semana
    $horario_lunes_viernes = ($configs['horario_lunes_viernes_inicio'] ?? '10:00') . ' - ' . ($configs['horario_lunes_viernes_fin'] ?? '22:00');
    $horario_sabado = ($configs['horario_sabado_inicio'] ?? '11:00') . ' - ' . ($configs['horario_sabado_fin'] ?? '23:00');
    $horario_domingo = ($configs['horario_domingo_inicio'] ?? '12:00') . ' - ' . ($configs['horario_domingo_fin'] ?? '21:00');
    
    // Si hay horarios generales configurados, usarlos como respaldo
    if (isset($configs['hora_apertura']) && isset($configs['hora_cierre'])) {
        $horario_general = $configs['hora_apertura'] . ' - ' . $configs['hora_cierre'];
    } else {
        $horario_general = null;
    }
    
    // Días cerrados
    $dias_cerrados = $configs['dias_cerrados'] ?? '';
    $dias_cerrado_texto = '';
    
    if (!empty($dias_cerrados)) {
        $diasCerradosArray = array_map('trim', explode(',', $dias_cerrados));
        $nombresEspañol = [
            '0' => 'Domingos',
            '1' => 'Lunes',
            '2' => 'Martes',
            '3' => 'Miércoles',
            '4' => 'Jueves',
            '5' => 'Viernes',
            '6' => 'Sábados'
        ];
        
        $diasTexto = [];
        foreach ($diasCerradosArray as $dia) {
            if (isset($nombresEspañol[$dia])) {
                $diasTexto[] = $nombresEspañol[$dia];
            }
        }
        
        if (!empty($diasTexto)) {
            $dias_cerrado_texto = 'Cerrado: ' . implode(', ', $diasTexto);
        }
    }
    
    // Verificar si hay horarios específicos o usar el general
    $usar_horarios_especificos = isset($configs['horario_lunes_viernes_inicio']);
    
    echo json_encode([
        'success' => true,
        'horarios' => [
            'lunes_viernes' => $horario_lunes_viernes,
            'sabado' => $horario_sabado,
            'domingo' => $horario_domingo,
            'general' => $horario_general,
            'usar_especificos' => $usar_horarios_especificos
        ],
        'dias_cerrados' => $dias_cerrado_texto,
        'reservas_activas' => ($configs['reservas_activas'] ?? '1') === '1'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener horarios: ' . $e->getMessage()
    ]);
}
?>
