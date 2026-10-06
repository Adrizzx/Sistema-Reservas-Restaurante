<?php
/**
 * API: Obtener Reservas de Zonas
 * Retorna las reservas de zonas completas
 */
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/../../conexion/db.php';

try {
    // Filtro opcional por estado
    $estado = isset($_GET['estado']) && !empty($_GET['estado']) ? $_GET['estado'] : null;
    
    $query = "
        SELECT 
            rz.id,
            rz.cliente_id,
            rz.zonas,
            rz.fecha_reserva,
            rz.hora_reserva,
            rz.numero_personas,
            rz.precio_total,
            rz.cantidad_mesas,
            rz.estado,
            rz.fecha_creacion,
            CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
            c.telefono as cliente_telefono,
            c.email as cliente_email
        FROM reservas_zonas rz
        INNER JOIN clientes c ON rz.cliente_id = c.id
    ";
    
    if ($estado) {
        $query .= " WHERE rz.estado = :estado";
    }
    
    $query .= " ORDER BY rz.fecha_reserva DESC, rz.hora_reserva DESC";
    
    $stmt = $pdo->prepare($query);
    
    if ($estado) {
        $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decodificar el JSON de zonas para cada reserva
    foreach ($reservas as &$reserva) {
        $reserva['zonas'] = json_decode($reserva['zonas'], true);
        
        // Traducir nombres de zonas
        $nombres_zonas = [
            'interior' => 'Salón Principal',
            'terraza' => 'Terraza',
            'vip' => 'Área VIP',
            'bar' => 'Bar & Lounge'
        ];
        
        $reserva['zonas_nombres'] = array_map(function($z) use ($nombres_zonas) {
            return $nombres_zonas[$z] ?? $z;
        }, $reserva['zonas']);
    }
    
    echo json_encode([
        'success' => true,
        'reservas' => $reservas
    ]);
    
} catch (Exception $e) {
    error_log('Error en obtener_reservas_zonas.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener reservas: ' . $e->getMessage()
    ]);
}
?>
