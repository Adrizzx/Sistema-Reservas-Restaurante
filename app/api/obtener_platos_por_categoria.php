<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once '../../conexion/db.php';

try {
    // Obtener categoría si se envía (opcional)
    $categoria_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : null;
    
    // Query base
    $query = "SELECT 
                p.id,
                p.nombre,
                p.descripcion,
                p.precio,
                p.stock_disponible,
                p.imagen_url,
                p.tiempo_preparacion,
                p.categoria_id,
                c.nombre as categoria_nombre
              FROM platos p
              INNER JOIN categorias_platos c ON p.categoria_id = c.id
              WHERE p.activo = 1 AND c.activo = 1";
    
    // Filtrar por categoría si se proporciona
    if ($categoria_id !== null) {
        $query .= " AND p.categoria_id = :categoria_id";
    }
    
    $query .= " ORDER BY c.orden_menu ASC, p.nombre ASC";
    
    $stmt = $pdo->prepare($query);
    
    if ($categoria_id !== null) {
        $stmt->bindParam(':categoria_id', $categoria_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $platos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar por categoría
    $platos_por_categoria = [];
    foreach ($platos as $plato) {
        $cat_id = $plato['categoria_id'];
        $cat_nombre = $plato['categoria_nombre'];
        
        if (!isset($platos_por_categoria[$cat_id])) {
            $platos_por_categoria[$cat_id] = [
                'categoria_id' => $cat_id,
                'categoria_nombre' => $cat_nombre,
                'platos' => []
            ];
        }
        
        $platos_por_categoria[$cat_id]['platos'][] = [
            'id' => intval($plato['id']),
            'nombre' => $plato['nombre'],
            'descripcion' => $plato['descripcion'],
            'precio' => floatval($plato['precio']),
            'stock_disponible' => intval($plato['stock_disponible']),
            'imagen_url' => $plato['imagen_url'],
            'tiempo_preparacion' => intval($plato['tiempo_preparacion'])
        ];
    }
    
    // Convertir a array indexado
    $resultado = array_values($platos_por_categoria);
    
    echo json_encode([
        'success' => true,
        'categorias' => $resultado,
        'total_categorias' => count($resultado),
        'total_platos' => count($platos)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener platos: ' . $e->getMessage()
    ]);
}
?>
