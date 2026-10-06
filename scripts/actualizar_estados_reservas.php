<?php
/**
 * Script para actualizar automáticamente los estados de las reservas
 * Este script debe ejecutarse periódicamente (cada 5-10 minutos)
 * 
 * Puede ejecutarse de varias formas:
 * 1. Manualmente desde el navegador
 * 2. Con un cron job
 * 3. Automáticamente al cargar páginas importantes
 */

require_once __DIR__ . '/../conexion/db.php';

header('Content-Type: application/json');

try {
    // Llamar al procedimiento almacenado
    $stmt = $pdo->prepare("CALL activar_reservas_programadas()");
    $stmt->execute();
    
    // Obtener el número de reservas actualizadas
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_actualizadas
        FROM reservas 
        WHERE estado = 'finalizada' 
        AND fecha_reserva < CURDATE()
    ");
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Estados de reservas actualizados correctamente',
        'reservas_finalizadas' => $resultado['total_actualizadas'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar estados: ' . $e->getMessage()
    ]);
}
