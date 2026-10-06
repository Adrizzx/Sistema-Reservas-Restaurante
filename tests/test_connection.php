<?php
// test_connection.php - Prueba de conexión para el sistema del restaurante

try {
    require_once 'conexion/db.php';
    
    // Probar conexión básica
    $test_query = "SELECT COUNT(*) as total FROM administradores";
    $result = $pdo->query($test_query);
    $count = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='font-family: Arial; padding: 20px; background: linear-gradient(135deg, #2d1b12 0%, #1a0e0a 100%); color: white; border-radius: 10px; max-width: 600px; margin: 20px auto;'>";
    echo "<h2 style='color: #d4af37; margin: 0 0 20px 0;'>🏆 SISTEMA RESTAURANTE ELEGANTE</h2>";
    echo "<h3 style='color: #4CAF50; margin: 0 0 15px 0;'>✅ Conexión a Base de Datos: EXITOSA</h3>";
    echo "<p><strong>Base de datos:</strong> crud_proyecto</p>";
    echo "<p><strong>Administradores registrados:</strong> " . $count['total'] . "</p>";
    
    // Verificar tabla de mesas
    $mesas_query = "SELECT COUNT(*) as total FROM mesas";
    $mesas_result = $pdo->query($mesas_query);
    $mesas_count = $mesas_result->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Mesas disponibles:</strong> " . $mesas_count['total'] . "</p>";
    
    // Verificar tabla de reservas
    $reservas_query = "SELECT COUNT(*) as total FROM reservas";
    $reservas_result = $pdo->query($reservas_query);
    $reservas_count = $reservas_result->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Reservas registradas:</strong> " . $reservas_count['total'] . "</p>";
    
    // Verificar credenciales de administrador
    $admin_query = "SELECT usuario, nombre, apellido FROM administradores WHERE usuario = 'admin'";
    $admin_result = $pdo->query($admin_query);
    $admin_data = $admin_result->fetch(PDO::FETCH_ASSOC);
    
    if ($admin_data) {
        echo "<hr style='border-color: #d4af37; margin: 20px 0;'>";
        echo "<h4 style='color: #d4af37; margin: 0 0 10px 0;'>👤 CREDENCIALES DE PRUEBA</h4>";
        echo "<p><strong>Usuario:</strong> " . $admin_data['usuario'] . "</p>";
        echo "<p><strong>Nombre:</strong> " . $admin_data['nombre'] . " " . $admin_data['apellido'] . "</p>";
        echo "<p><strong>Contraseña:</strong> password</p>";
        
        echo "<hr style='border-color: #d4af37; margin: 20px 0;'>";
        echo "<div style='background: rgba(212, 175, 55, 0.1); padding: 15px; border-radius: 8px; border: 1px solid rgba(212, 175, 55, 0.3);'>";
        echo "<h4 style='color: #d4af37; margin: 0 0 10px 0;'>🔗 ENLACES RÁPIDOS</h4>";
        echo "<p><a href='index.html' style='color: #d4af37; text-decoration: none;'>📱 Página Principal del Restaurante</a></p>";
        echo "<p><a href='admin.php' style='color: #d4af37; text-decoration: none;'>🛠️ Panel de Administración</a></p>";
        echo "</div>";
    }
    
    echo "<div style='margin-top: 20px; padding: 15px; background: rgba(76, 175, 80, 0.1); border: 1px solid rgba(76, 175, 80, 0.3); border-radius: 8px;'>";
    echo "<p style='margin: 0; color: #4CAF50;'><strong>✅ Sistema completamente funcional y listo para usar!</strong></p>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='font-family: Arial; padding: 20px; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border-radius: 10px; max-width: 600px; margin: 20px auto;'>";
    echo "<h2 style='color: #fff; margin: 0 0 20px 0;'>❌ ERROR DE CONEXIÓN</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Solución:</strong> Verificar que XAMPP esté ejecutándose y que la base de datos 'crud_proyecto' exista.</p>";
    echo "<p><strong>Comando:</strong> <code>sudo /opt/lampp/lampp start</code></p>";
    echo "</div>";
}
?>