<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

require_once '../../conexion/db.php';

try {
    // Obtener estado actual de todas las mesas con reservas
    $query = "
        SELECT 
            m.id,
            m.numero_mesa,
            m.capacidad_minima,
            m.capacidad_maxima,
            m.ubicacion,
            m.estado,
            m.descripcion,
            m.precio_reserva,
            r.id as reserva_id,
            CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
            c.telefono,
            r.fecha_reserva,
            r.hora_reserva,
            r.numero_personas,
            r.estado as reserva_estado
        FROM mesas m
        LEFT JOIN reservas r ON m.id = r.mesa_id 
            AND DATE(r.fecha_reserva) = CURDATE()
            AND r.estado IN ('confirmada', 'pendiente', 'en_curso')
        LEFT JOIN clientes c ON r.cliente_id = c.id
        ORDER BY m.numero_mesa ASC
    ";
    
    $stmt = $pdo->query($query);
    $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para el frontend
    $mesas_formateadas = array_map(function($mesa) {
        return [
            'id' => $mesa['id'],
            'numero' => $mesa['numero_mesa'],
            'capacidad' => (int)$mesa['capacidad_maxima'],
            'capacidad_minima' => (int)$mesa['capacidad_minima'],
            'capacidad_maxima' => (int)$mesa['capacidad_maxima'],
            'tipo' => $mesa['ubicacion'],
            'ubicacion' => $mesa['ubicacion'],
            'estado' => $mesa['estado'],
            'descripcion' => $mesa['descripcion'],
            'precio_reserva' => (float)$mesa['precio_reserva'],
            'reserva' => $mesa['reserva_id'] ? [
                'id' => $mesa['reserva_id'],
                'cliente' => $mesa['cliente_nombre'],
                'telefono' => $mesa['telefono'],
                'fecha' => $mesa['fecha_reserva'],
                'hora' => date('H:i', strtotime($mesa['hora_reserva'])),
                'personas' => (int)$mesa['numero_personas'],
                'estado' => $mesa['reserva_estado']
            ] : null,
            'disponible_desde' => $mesa['estado'] === 'disponible' ? date('H:i') : null,
            'color_estado' => $mesa['estado'] === 'ocupada' ? '#dc3545' : '#28a745'
        ];
    }, $mesas);
    
    // Estadísticas rápidas
    $total_mesas = count($mesas);
    $mesas_ocupadas = count(array_filter($mesas, fn($m) => in_array($m['estado'], ['ocupada', 'reservada'])));
    $mesas_disponibles = $total_mesas - $mesas_ocupadas;
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'mesas' => $mesas_formateadas,
        'resumen' => [
            'total' => $total_mesas,
            'ocupadas' => $mesas_ocupadas,
            'disponibles' => $mesas_disponibles,
            'porcentaje_ocupacion' => $total_mesas > 0 ? round(($mesas_ocupadas / $total_mesas) * 100, 1) : 0
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