<?php
// Test para verificar la salida de obtener_reservas.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST DE API obtener_reservas.php ===\n\n";

// Simular llamada a la API
$url = 'http://localhost/PRY_PROYECTO/app/obtener_reservas.php?estado=pendiente';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

echo "HEADERS:\n";
echo $header;
echo "\n";

echo "BODY:\n";
echo $body;
echo "\n\n";

echo "BODY LENGTH: " . strlen($body) . " bytes\n";

// Intentar decodificar JSON
$json = json_decode($body, true);
if ($json === null) {
    echo "ERROR: No se pudo decodificar JSON\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    echo "Primeros 500 caracteres del body:\n";
    echo substr($body, 0, 500) . "\n";
} else {
    echo "✓ JSON válido\n";
    echo "Contenido:\n";
    print_r($json);
}
