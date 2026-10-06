<?php
/**
 * API: Crear Reserva de Zona Completa (VERSIÓN SIMPLIFICADA)
 * Crea UNA SOLA reserva de zona en la tabla reservas_zonas
 * NO crea reservas individuales por mesa
 */
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['cliente_authenticated'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../conexion/db.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $cliente_id = $_SESSION['cliente_id'];
    $zonas = $data['zonas'] ?? []; // Array de zonas: ['interior', 'terraza', 'vip', 'bar']
    $fecha_reserva = $data['fecha_reserva'] ?? '';
    $hora_reserva = $data['hora_reserva'] ?? '';
    $numero_personas = $data['numero_personas'] ?? 0;
    
    // Validaciones
    if (empty($zonas) || !is_array($zonas)) {
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar al menos una zona']);
        exit;
    }

    // Normalizar y validar nombres de zonas aceptadas
    $allowed_zonas = ['interior', 'terraza', 'vip', 'bar'];
    $zonas = array_filter(array_map('trim', $zonas), function($z) use ($allowed_zonas) {
        return $z !== '' && in_array($z, $allowed_zonas, true);
    });
    $zonas = array_values(array_unique($zonas));

    if (empty($zonas)) {
        echo json_encode(['success' => false, 'message' => 'No hay zonas válidas en la selección']);
        exit;
    }

    if (empty($fecha_reserva) || empty($hora_reserva)) {
        echo json_encode(['success' => false, 'message' => 'Fecha y hora son requeridas']);
        exit;
    }
    
    // Validación de hora
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $hora_reserva)) {
        echo json_encode(['success' => false, 'message' => 'Formato de hora inválido (use HH:MM)']);
        exit;
    }
    
    // Validar horario de apertura/cierre
    $hora_num = (int)substr($hora_reserva, 0, 2);
    $minutos_num = (int)substr($hora_reserva, 3, 2);
    
    // Validar que no sean negativos
    if ($hora_num < 0 || $minutos_num < 0) {
        echo json_encode(['success' => false, 'message' => 'La hora no puede contener valores negativos']);
        exit;
    }
    
    // Validar horario usando la API
    $url = 'http://localhost/PRY_PROYECTO/app/api/validar_horario_reserva.php';
    $data_to_send = json_encode(['fecha' => $fecha_reserva, 'hora' => $hora_reserva]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_to_send);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    if (!is_array($result) || !isset($result['valido'])) {
        $msg = 'Error al validar horario';
        if (!empty($curlErr)) $msg .= ': ' . $curlErr;
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
    if (!$result['valido']) {
        echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Horario inválido']);
        exit;
    }
    
    // Validación de número de personas: permitir 1-50 (enteros)
    if (!is_numeric($numero_personas) || (int)$numero_personas < 1) {
        echo json_encode(['success' => false, 'message' => 'El número de personas debe ser entre 1 y 50']);
        exit;
    }

    $numero_personas = (int)$numero_personas;
    if ($numero_personas > 50) {
        echo json_encode(['success' => false, 'message' => 'Para grupos mayores a 50 personas contacte directamente al restaurante']);
        exit;
    }
    
    // Calcular precio según cantidad de zonas
    $precios = [
        1 => 60.00,  // 1 zona
        2 => 100.00, // 2 zonas
        3 => 120.00, // 3 zonas
        4 => 140.00  // 4 zonas (todas)
    ];
    
    $cantidad_zonas = count($zonas);
    $precio_total = $precios[$cantidad_zonas] ?? $precios[1];
    
    // Contar las mesas totales DISPONIBLES en las zonas seleccionadas
    // Y verificar que la capacidad máxima total sea suficiente
    $placeholders = str_repeat('?,', count($zonas) - 1) . '?';
    $query_mesas = "
        SELECT COUNT(*) as total_mesas, COALESCE(SUM(capacidad_maxima), 0) as capacidad_total
        FROM mesas 
        WHERE ubicacion IN ($placeholders)
        AND estado = 'disponible'
    ";
    $stmt = $pdo->prepare($query_mesas);
    if (!$stmt->execute(array_values($zonas))) {
        echo json_encode(['success' => false, 'message' => 'Error al validar mesas disponibles']);
        exit;
    }
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Asegurar conversión correcta de tipos
    $cantidad_mesas = (int)($resultado['total_mesas'] ?? 0);
    $capacidad_total = (int)($resultado['capacidad_total'] ?? 0);
    
    // Validar que hay mesas disponibles
    if ($cantidad_mesas <= 0) {
        echo json_encode(['success' => false, 'message' => 'No hay mesas disponibles en las zonas seleccionadas']);
        exit;
    }
    
    // Validar que hay mesas disponibles
    if ($cantidad_mesas <= 0) {
        echo json_encode(['success' => false, 'message' => 'No hay mesas disponibles en las zonas seleccionadas']);
        exit;
    }
    
    // Validar que la capacidad total es suficiente para el número de personas
    if ($capacidad_total < $numero_personas) {
        echo json_encode(['success' => false, 'message' => 'Capacidad insuficiente en las zonas seleccionadas para ' . $numero_personas . ' personas. Máximo disponible: ' . $capacidad_total]);
        exit;
    }
    
    // Verificar que no haya otra reserva de zona en la misma fecha/hora
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as conflictos
        FROM reservas_zonas
        WHERE fecha_reserva = ?
        AND estado IN ('pendiente', 'confirmada')
        AND hora_reserva = ?
    ");
    if (!$stmt || !$stmt->execute([$fecha_reserva, $hora_reserva])) {
        echo json_encode(['success' => false, 'message' => 'Error al verificar conflictos de horarios']);
        exit;
    }
    $resultado_conflictos = $stmt->fetch(PDO::FETCH_ASSOC);
    $conflictos = (int)($resultado_conflictos['conflictos'] ?? 0);
    
    if ($conflictos > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Ya existe una reserva de zona en ese horario'
        ]);
        exit;
    }
    
    // Crear UNA SOLA reserva de zona (NO múltiples reservas por mesa)
    $stmt = $pdo->prepare("
        INSERT INTO reservas_zonas 
        (cliente_id, zonas, fecha_reserva, hora_reserva, numero_personas, precio_total, cantidad_mesas, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')
    ");
    
    $zonas_json = json_encode($zonas);
    $stmt->execute([
        $cliente_id,
        $zonas_json,
        $fecha_reserva,
        $hora_reserva,
        $numero_personas,
        $precio_total,
        $cantidad_mesas
    ]);
    
    $reserva_id = $pdo->lastInsertId();
    
    // Nombres de zonas para mostrar
    $nombres_zonas = [
        'interior' => 'Salón Principal',
        'terraza' => 'Terraza',
        'vip' => 'Área VIP',
        'bar' => 'Bar & Lounge'
    ];
    
    $zonas_texto = array_map(function($z) use ($nombres_zonas) {
        return $nombres_zonas[$z] ?? $z;
    }, $zonas);
    
    echo json_encode([
        'success' => true,
        'message' => 'Solicitud de reserva de zona enviada exitosamente',
        'data' => [
            'reserva_id' => $reserva_id,
            'zonas' => $zonas_texto,
            'cantidad_mesas' => $cantidad_mesas,
            'precio_total' => $precio_total,
            'fecha' => $fecha_reserva,
            'hora' => $hora_reserva,
            'personas' => $numero_personas
        ]
    ]);

    
} catch (Exception $e) {
    error_log('Error en crear_reserva_zona.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al crear reserva de zona: ' . $e->getMessage()
    ]);
}
?>
