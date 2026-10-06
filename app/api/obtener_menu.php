<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../conexion/db.php';

try {
    // Obtener todas las categorías activas
    $queryCategorias = "SELECT * FROM categorias_platos WHERE activo = 1 ORDER BY orden_menu ASC";
    $stmtCategorias = $pdo->prepare($queryCategorias);
    $stmtCategorias->execute();
    $categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

    $menuCompleto = [];

    foreach ($categorias as $categoria) {
        // Obtener platos de cada categoría usando categoria_id
        $queryPlatos = "SELECT * FROM platos WHERE categoria_id = :categoria_id AND activo = 1 ORDER BY nombre ASC";
        $stmtPlatos = $pdo->prepare($queryPlatos);
        $stmtPlatos->bindParam(':categoria_id', $categoria['id'], PDO::PARAM_INT);
        $stmtPlatos->execute();
        $platos = $stmtPlatos->fetchAll(PDO::FETCH_ASSOC);

        $menuCompleto[] = [
            'id' => $categoria['id'],
            'nombre' => $categoria['nombre'],
            'descripcion' => $categoria['descripcion'],
            'imagen_url' => $categoria['imagen_url'] ?? null,
            'platos' => $platos
        ];
    }

    echo json_encode([
        'success' => true,
        'categorias' => $menuCompleto
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el menú: ' . $e->getMessage()
    ]);
}
?>
