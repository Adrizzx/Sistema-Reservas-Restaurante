<?php
header('Content-Type: application/json');

// Script seguro y idempotente para añadir la columna `tiempo_preparacion` en la tabla `platos`
// Uso: acceder desde el navegador: /app/api/add_tiempo_preparacion.php

require_once '../../conexion/db.php';

try {
    // Verificar que $pdo existe
    if (!isset($pdo)) {
        throw new Exception('No se encontró la conexión PDO en conexion/db.php');
    }

    // Nombre de la tabla y columna
    $table = 'platos';
    $column = 'tiempo_preparacion';

    // Comprobar existencia de la columna en INFORMATION_SCHEMA
    $sql = "SELECT COUNT(1) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :col";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['db' => $dbname, 'table' => $table, 'col' => $column]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && isset($row['cnt']) && (int)$row['cnt'] > 0) {
        echo json_encode(['ok' => true, 'message' => "La columna '$column' ya existe en la tabla '$table'."]);
        exit;
    }

    // Añadir columna de forma segura
    $alter = "ALTER TABLE `$table` ADD COLUMN `$column` INT DEFAULT NULL";
    $pdo->exec($alter);

    echo json_encode(['ok' => true, 'message' => "Columna '$column' añadida correctamente a la tabla '$table'."]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    exit;
}

?>
