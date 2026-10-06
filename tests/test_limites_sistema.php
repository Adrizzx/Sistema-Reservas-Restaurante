<?php
/**
 * SCRIPT DE PRUEBAS DE LÍMITES DEL SISTEMA
 * Ejecutar: php tests/test_limites_sistema.php
 * 
 * Este script prueba valores extremos para encontrar vulnerabilidades
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n========================================\n";
echo "PRUEBAS DE LÍMITES DEL SISTEMA\n";
echo "========================================\n\n";

// Colores para terminal
class Color {
    public static $RED = "\033[31m";
    public static $GREEN = "\033[32m";
    public static $YELLOW = "\033[33m";
    public static $RESET = "\033[0m";
}

// Función para hacer peticiones POST
function testAPI($endpoint, $data, $descripcion) {
    echo "🧪 Testing: $descripcion\n";
    
    $url = "http://localhost/PRY_PROYECTO/$endpoint";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($data))
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($result && isset($result['success'])) {
        if ($result['success']) {
            echo Color::$RED . "  ❌ VULNERABILIDAD: Se aceptó valor inválido!\n" . Color::$RESET;
            echo "  Respuesta: " . $result['message'] . "\n";
            return ['vulnerable' => true, 'response' => $result];
        } else {
            echo Color::$GREEN . "  ✅ PROTEGIDO: " . $result['message'] . "\n" . Color::$RESET;
            return ['vulnerable' => false, 'response' => $result];
        }
    } else {
        echo Color::$YELLOW . "  ⚠️  Respuesta inesperada: " . substr($response, 0, 100) . "\n" . Color::$RESET;
        return ['vulnerable' => 'unknown', 'response' => $response];
    }
}

echo "===========================================\n";
echo "TEST 1: LÍMITES DE MESAS\n";
echo "===========================================\n\n";

$vulnerabilidades = [];

// Test 1.1: Mesa con 100 sillas
$test = testAPI('app/agregar_mesa.php', [
    'numero_mesa' => 'TEST-100',
    'capacidad_minima' => 1,
    'capacidad_maxima' => 100,
    'ubicacion' => 'interior',
    'estado' => 'disponible'
], "Mesa con 100 sillas");
if ($test['vulnerable'] === true) $vulnerabilidades[] = "Mesa con 100 sillas";

// Test 1.2: Mesa con 1000 sillas
$test = testAPI('app/agregar_mesa.php', [
    'numero_mesa' => 'TEST-1000',
    'capacidad_minima' => 1,
    'capacidad_maxima' => 1000,
    'ubicacion' => 'interior'
], "Mesa con 1000 sillas");
if ($test['vulnerable'] === true) $vulnerabilidades[] = "Mesa con 1000 sillas";

// Test 1.3: Mesa con capacidad negativa
$test = testAPI('app/agregar_mesa.php', [
    'numero_mesa' => 'TEST-NEG',
    'capacidad_minima' => -5,
    'capacidad_maxima' => -1,
    'ubicacion' => 'interior'
], "Mesa con capacidad negativa");
if ($test['vulnerable'] === true) $vulnerabilidades[] = "Capacidad negativa";

// Test 1.4: Mesa con capacidad 0
$test = testAPI('app/agregar_mesa.php', [
    'numero_mesa' => 'TEST-ZERO',
    'capacidad_minima' => 0,
    'capacidad_maxima' => 0,
    'ubicacion' => 'interior'
], "Mesa con capacidad 0");
if ($test['vulnerable'] === true) $vulnerabilidades[] = "Capacidad 0";

// Test 1.5: Número de mesa extremadamente largo
$test = testAPI('app/agregar_mesa.php', [
    'numero_mesa' => str_repeat('A', 500),
    'capacidad_minima' => 2,
    'capacidad_maxima' => 4,
    'ubicacion' => 'interior'
], "Número de mesa con 500 caracteres");
if ($test['vulnerable'] === true) $vulnerabilidades[] = "Número de mesa muy largo";

echo "\n===========================================\n";
echo "TEST 2: CREACIÓN MASIVA DE MESAS\n";
echo "===========================================\n\n";

echo "🧪 Intentando crear 100 mesas...\n";
$mesasCreadas = 0;
for ($i = 1; $i <= 100; $i++) {
    $test = testAPI('app/agregar_mesa.php', [
        'numero_mesa' => "MASA-$i",
        'capacidad_minima' => 2,
        'capacidad_maxima' => 4,
        'ubicacion' => 'interior'
    ], false); // Sin imprimir cada una
    
    if ($test['vulnerable'] === true) {
        $mesasCreadas++;
    }
}

if ($mesasCreadas > 50) {
    echo Color::$RED . "  ❌ VULNERABILIDAD: Se crearon $mesasCreadas mesas sin límite!\n" . Color::$RESET;
    $vulnerabilidades[] = "Creación masiva ilimitada de mesas";
} else if ($mesasCreadas > 0) {
    echo Color::$YELLOW . "  ⚠️  Se crearon $mesasCreadas mesas (revisar si es aceptable)\n" . Color::$RESET;
} else {
    echo Color::$GREEN . "  ✅ PROTEGIDO: No se permite creación masiva\n" . Color::$RESET;
}

echo "\n===========================================\n";
echo "TEST 3: VALIDACIÓN DE STRINGS\n";
echo "===========================================\n\n";

// Test 3.1: SQL Injection básico
$test = testAPI('app/agregar_mesa.php', [
    'numero_mesa' => "1' OR '1'='1",
    'capacidad_minima' => 2,
    'capacidad_maxima' => 4,
    'ubicacion' => 'interior'
], "SQL Injection en número_mesa");
if ($test['vulnerable'] === true) $vulnerabilidades[] = "SQL Injection posible";

// Test 3.2: XSS básico
$test = testAPI('app/agregar_mesa.php', [
    'numero_mesa' => "<script>alert('XSS')</script>",
    'capacidad_minima' => 2,
    'capacidad_maxima' => 4,
    'ubicacion' => 'interior'
], "XSS en número_mesa");
if ($test['vulnerable'] === true) $vulnerabilidades[] = "XSS posible";

echo "\n===========================================\n";
echo "TEST 4: CAPACIDAD MÍNIMA MAYOR QUE MÁXIMA\n";
echo "===========================================\n\n";

$test = testAPI('app/agregar_mesa.php', [
    'numero_mesa' => 'TEST-INVERTIDO',
    'capacidad_minima' => 10,
    'capacidad_maxima' => 2,
    'ubicacion' => 'interior'
], "Capacidad mínima (10) > máxima (2)");
if ($test['vulnerable'] === true) $vulnerabilidades[] = "Capacidad invertida";

echo "\n===========================================\n";
echo "RESUMEN DE VULNERABILIDADES\n";
echo "===========================================\n\n";

if (count($vulnerabilidades) > 0) {
    echo Color::$RED . "⚠️  SE ENCONTRARON " . count($vulnerabilidades) . " VULNERABILIDADES:\n\n" . Color::$RESET;
    foreach ($vulnerabilidades as $i => $vuln) {
        echo ($i + 1) . ". " . $vuln . "\n";
    }
    echo "\n" . Color::$YELLOW . "👉 Revisa el archivo de correcciones: tests/correcciones_sugeridas.md\n" . Color::$RESET;
} else {
    echo Color::$GREEN . "✅ ¡Excelente! No se encontraron vulnerabilidades en los tests básicos\n" . Color::$RESET;
}

echo "\n========================================\n";
echo "FIN DE PRUEBAS\n";
echo "========================================\n\n";
?>
