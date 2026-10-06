<?php
/**
 * SCRIPT DE PRUEBAS DE LÍMITES - REGISTRO DE CLIENTES
 * Ejecutar: php tests/test_limites_clientes.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n========================================\n";
echo "PRUEBAS DE LÍMITES - CLIENTES\n";
echo "========================================\n\n";

class Color {
    public static $RED = "\033[31m";
    public static $GREEN = "\033[32m";
    public static $YELLOW = "\033[33m";
    public static $RESET = "\033[0m";
}

function testRegistro($data, $descripcion) {
    echo "🧪 Testing: $descripcion\n";
    
    $url = "http://localhost/PRY_PROYECTO/app/registro_cliente.php";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($result && isset($result['success'])) {
        if ($result['success']) {
            echo Color::$RED . "  ❌ VULNERABILIDAD: Se aceptó!\n" . Color::$RESET;
            return true;
        } else {
            echo Color::$GREEN . "  ✅ PROTEGIDO: " . $result['message'] . "\n" . Color::$RESET;
            return false;
        }
    } else {
        echo Color::$YELLOW . "  ⚠️  Error: " . substr($response, 0, 100) . "\n" . Color::$RESET;
        return false;
    }
}

$vulnerabilidades = [];

echo "TEST 1: Nombre extremadamente largo\n";
echo "=====================================\n";
if (testRegistro([
    'nombre' => str_repeat('Juan', 100),
    'apellido' => 'Pérez',
    'cedula' => '1234567890',
    'telefono' => '0999999999',
    'ciudad' => 'Quito',
    'usuario' => 'test' . rand(1000, 9999),
    'password' => '12345678'
], "Nombre con 400 caracteres")) {
    $vulnerabilidades[] = "Nombre extremadamente largo";
}

echo "\nTEST 2: Cédula inválida\n";
echo "========================\n";
if (testRegistro([
    'nombre' => 'Test',
    'apellido' => 'User',
    'cedula' => str_repeat('9', 50),
    'telefono' => '0999999999',
    'ciudad' => 'Quito',
    'usuario' => 'test' . rand(1000, 9999),
    'password' => '12345678'
], "Cédula con 50 dígitos")) {
    $vulnerabilidades[] = "Cédula demasiado larga";
}

echo "\nTEST 3: Teléfono inválido\n";
echo "==========================\n";
if (testRegistro([
    'nombre' => 'Test',
    'apellido' => 'User',
    'cedula' => '1234567890',
    'telefono' => str_repeat('9', 100),
    'ciudad' => 'Quito',
    'usuario' => 'test' . rand(1000, 9999),
    'password' => '12345678'
], "Teléfono con 100 dígitos")) {
    $vulnerabilidades[] = "Teléfono demasiado largo";
}

echo "\nTEST 4: Creación masiva de usuarios\n";
echo "=====================================\n";
echo "Intentando crear 100 usuarios...\n";
$creados = 0;
for ($i = 0; $i < 100; $i++) {
    $ch = curl_init("http://localhost/PRY_PROYECTO/app/registro_cliente.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'nombre' => 'Test',
        'apellido' => 'Masivo',
        'cedula' => '12345678' . str_pad($i, 2, '0'),
        'telefono' => '099999999' . $i,
        'ciudad' => 'Quito',
        'usuario' => 'testmass' . $i . rand(100, 999),
        'password' => '12345678'
    ]));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    if ($result && $result['success']) {
        $creados++;
    }
    
    if ($i % 10 == 0) echo ".";
}

echo "\n";
if ($creados > 50) {
    echo Color::$RED . "❌ VULNERABILIDAD: Se crearon $creados usuarios sin límite!\n" . Color::$RESET;
    $vulnerabilidades[] = "Registro masivo ilimitado";
} else {
    echo Color::$GREEN . "✅ Se crearon $creados usuarios\n" . Color::$RESET;
}

echo "\n===========================================\n";
echo "RESUMEN DE VULNERABILIDADES - CLIENTES\n";
echo "===========================================\n\n";

if (count($vulnerabilidades) > 0) {
    echo Color::$RED . "⚠️  SE ENCONTRARON " . count($vulnerabilidades) . " VULNERABILIDADES:\n\n" . Color::$RESET;
    foreach ($vulnerabilidades as $i => $vuln) {
        echo ($i + 1) . ". " . $vuln . "\n";
    }
} else {
    echo Color::$GREEN . "✅ No se encontraron vulnerabilidades\n" . Color::$RESET;
}

echo "\n";
?>
