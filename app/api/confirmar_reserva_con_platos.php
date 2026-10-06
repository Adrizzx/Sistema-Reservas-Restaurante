<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once '../../conexion/db.php';

// Verificar autenticación
if (!isset($_SESSION['cliente_id']) || !$_SESSION['cliente_authenticated']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No estás autenticado'
    ]);
    exit;
}

// Verificar mesa seleccionada
if (!isset($_SESSION['mesa_seleccionada_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Debes seleccionar una mesa primero'
    ]);
    exit;
}

// Verificar que hay items en el carrito
if (empty($_SESSION['carrito'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'El carrito está vacío'
    ]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $cliente_id = $_SESSION['cliente_id'];
    $mesa_id = $_SESSION['mesa_seleccionada_id'];
    $fecha_reserva = $input['fecha_reserva'] ?? date('Y-m-d');
    $hora_reserva = $input['hora_reserva'] ?? date('H:i:s');
    $numero_personas = intval($input['numero_personas'] ?? 1);
    
    // 1. Validar horario de atención
    $dia_semana = date('N', strtotime($fecha_reserva));
    
    // Obtener configuraciones de horarios
    $stmt = $pdo->query("SELECT clave, valor FROM configuracion_restaurante");
    $configs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $configs[$row['clave']] = $row['valor'];
    }
    
    // Verificar si las reservas están activas
    if (isset($configs['reservas_activas']) && $configs['reservas_activas'] !== '1') {
        throw new Exception('Las reservas están temporalmente deshabilitadas');
    }
    
    // Verificar días cerrados (formato: dd-mm,dd-mm o números de día de semana 0-6)
    if (isset($configs['dias_cerrados']) && !empty($configs['dias_cerrados'])) {
        $dias_cerrados = array_map('trim', explode(',', $configs['dias_cerrados']));
        $fecha_formato = date('d-m', strtotime($fecha_reserva));
        $num_dia_semana = date('w', strtotime($fecha_reserva)); // 0=Domingo
        
        // Verificar si la fecha específica está cerrada o el día de la semana
        if (in_array($fecha_formato, $dias_cerrados) || in_array($num_dia_semana, $dias_cerrados)) {
            throw new Exception('El restaurante está cerrado en esta fecha');
        }
    }
    
    // Determinar horario según día de la semana
    // Priorizar horarios específicos, si no existen usar horarios generales
    if ($dia_semana >= 1 && $dia_semana <= 5) {
        $hora_inicio = $configs['horario_lunes_viernes_inicio'] ?? $configs['hora_apertura'] ?? '10:00';
        $hora_fin = $configs['horario_lunes_viernes_fin'] ?? $configs['hora_cierre'] ?? '22:00';
        $tipo_dia = 'Lunes a Viernes';
    } elseif ($dia_semana == 6) {
        $hora_inicio = $configs['horario_sabado_inicio'] ?? $configs['hora_apertura'] ?? '11:00';
        $hora_fin = $configs['horario_sabado_fin'] ?? $configs['hora_cierre'] ?? '23:00';
        $tipo_dia = 'Sábado';
    } else {
        $hora_inicio = $configs['horario_domingo_inicio'] ?? $configs['hora_apertura'] ?? '12:00';
        $hora_fin = $configs['horario_domingo_fin'] ?? $configs['hora_cierre'] ?? '21:00';
        $tipo_dia = 'Domingo';
    }
    
    // Validar hora (solo si no está vacío el valor)
    $hora_reserva_sin_segundos = substr($hora_reserva, 0, 5); // Normalizar a HH:MM
    if (!empty($hora_inicio) && !empty($hora_fin) && 
        ($hora_reserva_sin_segundos < $hora_inicio || $hora_reserva_sin_segundos > $hora_fin)) {
        throw new Exception("Hora no válida. $tipo_dia el restaurante atiende de $hora_inicio a $hora_fin");
    }
    
    $pdo->beginTransaction();
    
    // 2. Verificar que la mesa está disponible y validar capacidad
    $stmt = $pdo->prepare("SELECT estado, numero_mesa, capacidad_minima, capacidad_maxima FROM mesas WHERE id = :id FOR UPDATE");
    $stmt->bindParam(':id', $mesa_id, PDO::PARAM_INT);
    $stmt->execute();
    $mesa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mesa) {
        throw new Exception('Mesa no encontrada');
    }
    
    if ($mesa['estado'] !== 'disponible') {
        throw new Exception('La mesa ' . $mesa['numero_mesa'] . ' no está disponible');
    }
    
    // Validar capacidad
    if ($numero_personas < $mesa['capacidad_minima']) {
        throw new Exception('La mesa ' . $mesa['numero_mesa'] . ' requiere mínimo ' . $mesa['capacidad_minima'] . ' personas');
    }
    
    if ($numero_personas > $mesa['capacidad_maxima']) {
        throw new Exception('La mesa ' . $mesa['numero_mesa'] . ' permite máximo ' . $mesa['capacidad_maxima'] . ' personas. Seleccionaste ' . $numero_personas);
    }
    
    // 2. Crear la reserva en estado PENDIENTE (el admin debe confirmarla)
    $stmt = $pdo->prepare("INSERT INTO reservas 
                           (cliente_id, mesa_id, fecha_reserva, hora_reserva, numero_personas, estado) 
                           VALUES 
                           (:cliente_id, :mesa_id, :fecha_reserva, :hora_reserva, :numero_personas, 'pendiente')");
    
    $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
    $stmt->bindParam(':mesa_id', $mesa_id, PDO::PARAM_INT);
    $stmt->bindParam(':fecha_reserva', $fecha_reserva);
    $stmt->bindParam(':hora_reserva', $hora_reserva);
    $stmt->bindParam(':numero_personas', $numero_personas, PDO::PARAM_INT);
    $stmt->execute();
    
    $reserva_id = $pdo->lastInsertId();
    
    // 3. Procesar cada item del carrito
    $total_reserva = 0;
    $platos_agregados = [];
    
    foreach ($_SESSION['carrito'] as $item) {
        $plato_id = $item['id'];
        $cantidad = $item['cantidad'];
        $precio_unitario = $item['precio'];
        $subtotal = $item['subtotal'];
        
        // Verificar stock actual y bloquearlo
        $stmt = $pdo->prepare("SELECT stock_disponible, nombre FROM platos WHERE id = :id FOR UPDATE");
        $stmt->bindParam(':id', $plato_id, PDO::PARAM_INT);
        $stmt->execute();
        $plato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plato) {
            throw new Exception('Plato ID ' . $plato_id . ' no encontrado');
        }
        
        if ($plato['stock_disponible'] < $cantidad) {
            throw new Exception('Stock insuficiente para: ' . $plato['nombre'] . 
                              '. Disponible: ' . $plato['stock_disponible']);
        }
        
        // Descontar stock
        $nuevo_stock = $plato['stock_disponible'] - $cantidad;
        $stmt = $pdo->prepare("UPDATE platos SET stock_disponible = :stock WHERE id = :id");
        $stmt->bindParam(':stock', $nuevo_stock, PDO::PARAM_INT);
        $stmt->bindParam(':id', $plato_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Insertar pre_pedido
        $stmt = $pdo->prepare("INSERT INTO pre_pedidos 
                               (reserva_id, plato_id, cantidad, precio_unitario, subtotal) 
                               VALUES 
                               (:reserva_id, :plato_id, :cantidad, :precio_unitario, :subtotal)");
        
        $stmt->bindParam(':reserva_id', $reserva_id, PDO::PARAM_INT);
        $stmt->bindParam(':plato_id', $plato_id, PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':precio_unitario', $precio_unitario);
        $stmt->bindParam(':subtotal', $subtotal);
        $stmt->execute();
        
        $total_reserva += $subtotal;
        $platos_agregados[] = [
            'nombre' => $plato['nombre'],
            'cantidad' => $cantidad,
            'subtotal' => $subtotal
        ];
    }
    
    // 4. Cambiar estado de la mesa a 'reservada'
    $stmt = $pdo->prepare("UPDATE mesas SET estado = 'reservada' WHERE id = :id");
    $stmt->bindParam(':id', $mesa_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // 5. Crear nota de consumo (borrador)
    $numero_nota = 'NC-' . date('Ymd') . '-' . str_pad($reserva_id, 6, '0', STR_PAD_LEFT);
    $subtotal = $total_reserva;
    $impuesto = round($subtotal * 0.12, 2); // IVA 12%
    $total_con_iva = round($subtotal + $impuesto, 2);
    
    $stmt = $pdo->prepare("INSERT INTO notas_consumo 
                           (reserva_id, numero_nota, subtotal, impuesto, total, estado) 
                           VALUES 
                           (:reserva_id, :numero_nota, :subtotal, :impuesto, :total, 'borrador')");
    
    $stmt->bindParam(':reserva_id', $reserva_id, PDO::PARAM_INT);
    $stmt->bindParam(':numero_nota', $numero_nota);
    $stmt->bindParam(':subtotal', $subtotal);
    $stmt->bindParam(':impuesto', $impuesto);
    $stmt->bindParam(':total', $total_con_iva);
    $stmt->execute();
    
    // Confirmar transacción
    $pdo->commit();
    
    // NO enviar WhatsApp aquí - se enviará cuando el admin confirme la reserva
    
    // Limpiar carrito y sesión de mesa
    $_SESSION['carrito'] = [];
    unset($_SESSION['mesa_seleccionada_id']);
    unset($_SESSION['mesa_seleccionada_numero']);
    
    echo json_encode([
        'success' => true,
        'message' => '¡Reserva confirmada exitosamente!',
        'reserva' => [
            'id' => $reserva_id,
            'numero_nota' => $numero_nota,
            'mesa' => $mesa['numero_mesa'],
            'fecha' => $fecha_reserva,
            'hora' => $hora_reserva,
            'personas' => $numero_personas,
            'platos' => $platos_agregados,
            'subtotal' => $subtotal,
            'impuesto' => $impuesto,
            'total' => $total_con_iva
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error al confirmar reserva: ' . $e->getMessage()
    ]);
}
?>
