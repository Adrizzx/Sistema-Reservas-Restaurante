<?php
// Script para limpiar datos de prueba
try {
    $pdo = new PDO('mysql:host=localhost;dbname=crud_proyecto', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE cedula = '1723177646' OR email = 'cliente2.demo@example.com' OR telefono = '0991234567'");
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ Eliminados: $count registros de prueba\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
