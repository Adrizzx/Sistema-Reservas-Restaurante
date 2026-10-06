<?php
// test_admin_login.php - Script temporal para diagnosticar el login
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Login Admin</h2>";

echo "<h3>1. Testeando conexión DB</h3>";
require_once __DIR__ . '/conexion/db.php';

if (isset($mysqli) && !$mysqli->connect_error) {
    echo "✅ Conexión mysqli OK<br>";
    echo "Base de datos: crud_proyecto<br>";
} else {
    echo "❌ Error conexión mysqli: " . ($mysqli->connect_error ?? 'no inicializado') . "<br>";
}

echo "<h3>2. Buscando admin en DB</h3>";
$usuario = 'admin';

$sql = "SELECT id, usuario, password, nombre, apellido, email, rol, activo FROM administradores WHERE usuario = ? LIMIT 1";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    echo "❌ Error prepare: " . $mysqli->error . "<br>";
    exit;
}

$stmt->bind_param('s', $usuario);
$stmt->execute();
$stmt->store_result();

echo "Filas encontradas: " . $stmt->num_rows . "<br>";

if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $usuario_db, $password_db, $nombre, $apellido, $email, $rol, $activo);
    $stmt->fetch();
    
    echo "<h3>3. Datos del admin encontrado:</h3>";
    echo "ID: $id<br>";
    echo "Usuario: $usuario_db<br>";
    echo "Password en DB: '$password_db'<br>";
    echo "Nombre: $nombre<br>";
    echo "Apellido: $apellido<br>";
    echo "Email: $email<br>";
    echo "Rol: $rol<br>";
    echo "Activo: $activo<br>";
    
    echo "<h3>4. Probando comparación de passwords:</h3>";
    $test_password = 'admin';
    echo "Password a probar: '$test_password'<br>";
    
    // Comparación directa
    if ($password_db === $test_password) {
        echo "✅ Comparación directa (===): COINCIDE<br>";
    } else {
        echo "❌ Comparación directa (===): NO COINCIDE<br>";
        echo "Longitud DB: " . strlen($password_db) . "<br>";
        echo "Longitud test: " . strlen($test_password) . "<br>";
        echo "DB hex: " . bin2hex($password_db) . "<br>";
        echo "Test hex: " . bin2hex($test_password) . "<br>";
    }
    
    // password_verify
    if (password_verify($test_password, $password_db)) {
        echo "✅ password_verify(): COINCIDE (es un hash válido)<br>";
    } else {
        echo "ℹ️ password_verify(): NO COINCIDE (no es hash o no match)<br>";
    }
    
} else {
    echo "❌ Usuario 'admin' NO encontrado en la tabla administradores<br>";
}

$stmt->close();
$mysqli->close();

echo "<hr>";
echo "<p><strong>Conclusión:</strong> Revisa los resultados de arriba para ver por qué no hace match.</p>";
echo "<p><a href='index.html'>Volver al index</a></p>";
?>
