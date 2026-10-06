<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../conexion/db.php';

try {
    // Obtener las últimas 10 reservas ordenadas por fecha de creación
    $query = "
        SELECT 
            r.id,
            r.fecha_reserva,
            r.hora_reserva,
            r.numero_personas,
            r.estado,
            CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
            c.telefono,
            c.email,
            m.numero_mesa,
            m.ubicacion,
            TIME_FORMAT(r.hora_reserva, '%H:%i') as hora_formateada,
            DATE_FORMAT(r.fecha_reserva, '%d/%m/%Y') as fecha_formateada
        FROM reservas r
        LEFT JOIN mesas m ON r.mesa_id = m.id
        LEFT JOIN clientes c ON r.cliente_id = c.id
        ORDER BY r.id DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->query($query);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para el frontend
    $reservas_formateadas = array_map(function($reserva) {
        $color_estado = [
            'pendiente' => '#ffc107',
            'confirmada' => '#28a745', 
            'en_curso' => '#17a2b8',
            'finalizada' => '#6c757d',
            'cancelada' => '#dc3545'
        ];
        
        $texto_estado = [
            'pendiente' => 'Pendiente',
            'confirmada' => 'Confirmada',
            'en_curso' => 'En Curso',
            'finalizada' => 'Finalizada',
            'cancelada' => 'Cancelada'
        ];
        
        return [
            'id' => $reserva['id'],
            'fecha' => $reserva['fecha_formateada'],
            'hora' => $reserva['hora_formateada'],
            'personas' => (int)$reserva['numero_personas'],
            'estado' => $reserva['estado'],
            'estado_texto' => $texto_estado[$reserva['estado']] ?? $reserva['estado'],
            'estado_color' => $color_estado[$reserva['estado']] ?? '#6c757d',
            'cliente' => [
                'nombre' => $reserva['cliente_nombre'],
                'telefono' => $reserva['telefono'],
                'email' => $reserva['email']
            ],
            'mesa' => [
                'numero' => $reserva['numero_mesa'],
                'tipo' => $reserva['ubicacion'],
                'ubicacion' => $reserva['ubicacion']
            ],
            'observaciones' => $reserva['observaciones']
        ];
    }, $reservas);
    
    // Estadísticas de reservas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas WHERE DATE(fecha_reserva) >= CURDATE()");
    $total_proximas = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas WHERE estado = 'confirmada'");
    $confirmadas = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas WHERE estado = 'pendiente'");
    $pendientes = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas WHERE estado = 'en_curso'");
    $en_curso = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservas WHERE DATE(fecha_reserva) = CURDATE()");
    $hoy = $stmt->fetch()['total'];
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'reservas' => $reservas_formateadas,
        'total_encontradas' => count($reservas_formateadas),
        'estadisticas' => [
            'total_proximas' => $total_proximas,
            'confirmadas' => $confirmadas,
            'pendientes' => $pendientes,
            'en_curso' => $en_curso,
            'hoy' => $hoy
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor',
        'error' => $e->getMessage()
    ]);
}
?>