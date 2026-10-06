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

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'add'; // add, remove, clear, get
    
    // Inicializar carrito si no existe
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }
    
    switch ($action) {
        case 'add':
            $plato_id = intval($input['plato_id'] ?? 0);
            $cantidad = intval($input['cantidad'] ?? 1);
            
            if ($plato_id <= 0 || $cantidad <= 0) {
                throw new Exception('Datos inválidos');
            }
            
            // Verificar que el plato existe y tiene stock
            $stmt = $pdo->prepare("SELECT id, nombre, precio, stock_disponible, imagen_url, tiempo_preparacion 
                                   FROM platos 
                                   WHERE id = :id AND activo = 1");
            $stmt->bindParam(':id', $plato_id, PDO::PARAM_INT);
            $stmt->execute();
            $plato = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plato) {
                throw new Exception('Plato no encontrado');
            }
            
            // Verificar stock disponible
            $cantidad_actual = isset($_SESSION['carrito'][$plato_id]) ? $_SESSION['carrito'][$plato_id]['cantidad'] : 0;
            $cantidad_total = $cantidad_actual + $cantidad;
            
            if ($cantidad_total > $plato['stock_disponible']) {
                throw new Exception('Stock insuficiente. Disponible: ' . $plato['stock_disponible']);
            }
            
            // Agregar o actualizar en carrito
            if (isset($_SESSION['carrito'][$plato_id])) {
                $_SESSION['carrito'][$plato_id]['cantidad'] = $cantidad_total;
                $_SESSION['carrito'][$plato_id]['subtotal'] = $cantidad_total * floatval($plato['precio']);
            } else {
                $_SESSION['carrito'][$plato_id] = [
                    'id' => intval($plato['id']),
                    'nombre' => $plato['nombre'],
                    'precio' => floatval($plato['precio']),
                    'cantidad' => $cantidad,
                    'subtotal' => $cantidad * floatval($plato['precio']),
                    'imagen_url' => $plato['imagen_url'],
                    'tiempo_preparacion' => intval($plato['tiempo_preparacion']),
                    'stock_disponible' => intval($plato['stock_disponible'])
                ];
            }
            
            $message = 'Plato agregado al carrito';
            break;
            
        case 'remove':
            $plato_id = intval($input['plato_id'] ?? 0);
            
            if (isset($_SESSION['carrito'][$plato_id])) {
                unset($_SESSION['carrito'][$plato_id]);
                $message = 'Plato eliminado del carrito';
            } else {
                throw new Exception('Plato no encontrado en el carrito');
            }
            break;
            
        case 'update':
            $plato_id = intval($input['plato_id'] ?? 0);
            $cantidad = intval($input['cantidad'] ?? 0);
            
            if (!isset($_SESSION['carrito'][$plato_id])) {
                throw new Exception('Plato no encontrado en el carrito');
            }
            
            if ($cantidad <= 0) {
                unset($_SESSION['carrito'][$plato_id]);
                $message = 'Plato eliminado del carrito';
            } else {
                // Verificar stock
                $stock = $_SESSION['carrito'][$plato_id]['stock_disponible'];
                if ($cantidad > $stock) {
                    throw new Exception('Stock insuficiente. Disponible: ' . $stock);
                }
                
                $_SESSION['carrito'][$plato_id]['cantidad'] = $cantidad;
                $_SESSION['carrito'][$plato_id]['subtotal'] = $cantidad * $_SESSION['carrito'][$plato_id]['precio'];
                $message = 'Cantidad actualizada';
            }
            break;
            
        case 'clear':
            $_SESSION['carrito'] = [];
            $message = 'Carrito vaciado';
            break;
            
        case 'get':
        default:
            $message = 'Carrito obtenido';
            break;
    }
    
    // Calcular totales
    $total_items = 0;
    $total_precio = 0;
    $tiempo_preparacion_total = 0;
    
    foreach ($_SESSION['carrito'] as $item) {
        $total_items += $item['cantidad'];
        $total_precio += $item['subtotal'];
        $tiempo_preparacion_total = max($tiempo_preparacion_total, $item['tiempo_preparacion']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'carrito' => array_values($_SESSION['carrito']),
        'totales' => [
            'items' => $total_items,
            'subtotal' => round($total_precio, 2),
            'impuesto' => round($total_precio * 0.12, 2), // IVA 12%
            'total' => round($total_precio * 1.12, 2),
            'tiempo_preparacion' => $tiempo_preparacion_total
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
