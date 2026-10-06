<?php
/**
 * API: Obtener Reservas de Zonas del Cliente
 * Retorna las reservas de zonas del cliente autenticado
 */
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (!isset($_SESSION['cliente_authenticated'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../conexion/db.php';

try {
    $cliente_id = $_SESSION['cliente_id'];
    
    $query = "
        SELECT 
            rz.id,
            rz.zonas,
            rz.fecha_reserva,
            rz.hora_reserva,
            rz.numero_personas,
            rz.precio_total,
            rz.cantidad_mesas,
            rz.estado,
            rz.motivo_cancelacion,
            rz.fecha_creacion
        FROM reservas_zonas rz
        WHERE rz.cliente_id = ?
        ORDER BY rz.fecha_reserva DESC, rz.hora_reserva DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$cliente_id]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decodificar el JSON de zonas y traducir nombres
    $nombres_zonas = [
        'interior' => 'Salón Principal',
        'terraza' => 'Terraza',
        'vip' => 'Área VIP',
        'bar' => 'Bar & Lounge'
    ];
    
    foreach ($reservas as &$reserva) {
        $reserva['zonas'] = json_decode($reserva['zonas'], true);
        $reserva['zonas_nombres'] = array_map(function($z) use ($nombres_zonas) {
            return $nombres_zonas[$z] ?? $z;
        }, $reserva['zonas']);
    }
    
    echo json_encode([
        'success' => true,
        'reservas' => $reservas
    ]);
    
} catch (Exception $e) {
    error_log('Error en obtener_reservas_zonas_cliente.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener reservas: ' . $e->getMessage()
    ]);
}
?>
