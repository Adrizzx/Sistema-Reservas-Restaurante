<?php
require_once 'conexion/db.php';

echo "<h2>🔍 Verificación del Sistema - Restaurante Elegante</h2>";

try {
    // Verificar administradores
    $query_admin = "SELECT COUNT(*) as total FROM administradores WHERE activo = 1";
    $result_admin = $mysqli->query($query_admin);
    $admin_count = $result_admin->fetch_assoc()['total'];
    
    echo "<h3>👨‍💼 Administradores</h3>";
    echo "<p>Total administradores activos: <strong>$admin_count</strong></p>";
    
    if ($admin_count == 0) {
        // Crear administrador de prueba
        $usuario = 'admin';
        $password = 'password';
        $nombre = 'Administrador Sistema';
        $email = 'admin@restaurante.com';
        
        $insert_admin = "INSERT INTO administradores (usuario, password, nombre, email, activo) VALUES (?, ?, ?, ?, 1)";
        $stmt = $mysqli->prepare($insert_admin);
        $stmt->bind_param("ssss", $usuario, $password, $nombre, $email);
        
        if ($stmt->execute()) {
            echo "<p>✅ Administrador de prueba creado: <strong>admin / password</strong></p>";
        } else {
            echo "<p>❌ Error creando administrador: " . $mysqli->error . "</p>";
        }
        $stmt->close();
    } else {
        // Mostrar administradores existentes
        $query_list = "SELECT usuario, nombre FROM administradores WHERE activo = 1 LIMIT 3";
        $result_list = $mysqli->query($query_list);
        echo "<ul>";
        while ($row = $result_list->fetch_assoc()) {
            echo "<li>Usuario: <strong>{$row['usuario']}</strong> - {$row['nombre']}</li>";
        }
        echo "</ul>";
    }
    
    // Verificar clientes
    $query_client = "SELECT COUNT(*) as total FROM clientes WHERE activo = 1";
    $result_client = $mysqli->query($query_client);
    $client_count = $result_client->fetch_assoc()['total'];
    
    echo "<h3>👥 Clientes</h3>";
    echo "<p>Total clientes activos: <strong>$client_count</strong></p>";
    
    if ($client_count == 0) {
        // Crear cliente de prueba
        $nombre = 'Juan';
        $apellido = 'Pérez';
        $email = 'juan@email.com';
        $telefono = '0987654321';
        
        $insert_client = "INSERT INTO clientes (nombre, apellido, email, telefono, activo) VALUES (?, ?, ?, ?, 1)";
        $stmt = $mysqli->prepare($insert_client);
        $stmt->bind_param("ssss", $nombre, $apellido, $email, $telefono);
        
        if ($stmt->execute()) {
            echo "<p>✅ Cliente de prueba creado: <strong>juan@email.com / 0987654321</strong></p>";
        } else {
            echo "<p>❌ Error creando cliente: " . $mysqli->error . "</p>";
        }
        $stmt->close();
    } else {
        // Mostrar clientes existentes
        $query_list_client = "SELECT nombre, apellido, email, telefono FROM clientes WHERE activo = 1 LIMIT 3";
        $result_list_client = $mysqli->query($query_list_client);
        echo "<ul>";
        while ($row = $result_list_client->fetch_assoc()) {
            echo "<li><strong>{$row['nombre']} {$row['apellido']}</strong> - {$row['email']} / {$row['telefono']}</li>";
        }
        echo "</ul>";
    }
    
    // Verificar mesas
    $query_mesas = "SELECT COUNT(*) as total FROM mesas WHERE activo = 1";
    $result_mesas = $mysqli->query($query_mesas);
    $mesas_count = $result_mesas->fetch_assoc()['total'];
    
    echo "<h3>🪑 Mesas</h3>";
    echo "<p>Total mesas activas: <strong>$mesas_count</strong></p>";
    
    if ($mesas_count == 0) {
        // Crear mesas de prueba
        for ($i = 1; $i <= 10; $i++) {
            $capacidad = rand(2, 8);
            $insert_mesa = "INSERT INTO mesas (numero_mesa, capacidad, estado, activo) VALUES (?, ?, 'disponible', 1)";
            $stmt = $mysqli->prepare($insert_mesa);
            $stmt->bind_param("ii", $i, $capacidad);
            $stmt->execute();
        }
        echo "<p>✅ 10 mesas de prueba creadas</p>";
    }
    
    echo "<hr>";
    echo "<h3>🚀 Estado del Sistema</h3>";
    echo "<p>✅ Base de datos: <strong>Conectada</strong></p>";
    echo "<p>✅ Tablas: <strong>Configuradas</strong></p>";
    echo "<p>✅ Datos de prueba: <strong>Disponibles</strong></p>";
    
    echo "<hr>";
    echo "<h3>🔗 Enlaces de Prueba</h3>";
    echo "<p><a href='index.html' class='btn btn-primary'>🏠 Página Principal</a></p>";
    echo "<p><a href='admin.php' class='btn btn-success'>👨‍💼 Panel Admin</a></p>";
    echo "<p><strong>Credenciales Admin:</strong> admin / password</p>";
    echo "<p><strong>Credenciales Cliente:</strong> juan@email.com / 0987654321</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

$mysqli->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h2 { color: #2c3e50; }
h3 { color: #34495e; }
.btn { 
    padding: 10px 20px; 
    margin: 5px; 
    text-decoration: none; 
    border-radius: 5px; 
    display: inline-block; 
}
.btn-primary { background: #3498db; color: white; }
.btn-success { background: #27ae60; color: white; }
</style>