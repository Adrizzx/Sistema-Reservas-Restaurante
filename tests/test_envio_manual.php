<?php
/**
 * Script para enviar notificaciones WhatsApp manualmente a reservas sin notificar
 */

// Configurar para mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de Envío Manual de WhatsApp</h2>";

// Obtener reserva_id desde URL o usar última sin notificación
$reserva_id = isset($_GET['reserva_id']) ? intval($_GET['reserva_id']) : null;

if (!$reserva_id) {
    // Buscar reservas sin notificación
    require_once 'conexion/db.php';
    
    $stmt = $pdo->query("
        SELECT r.id, r.fecha_reserva, r.hora_reserva, c.nombre, c.telefono
        FROM reservas r 
        INNER JOIN clientes c ON r.cliente_id = c.id
        LEFT JOIN notificaciones_whatsapp n ON r.id = n.reserva_id
        WHERE n.id IS NULL
        ORDER BY r.id DESC
        LIMIT 10
    ");
    
    $sin_notificacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Reservas sin notificación:</h3>";
    echo "<ul>";
    foreach ($sin_notificacion as $reserva) {
        echo "<li>";
        echo "<strong>Reserva #{$reserva['id']}</strong> - ";
        echo "{$reserva['nombre']} - {$reserva['telefono']} - ";
        echo "{$reserva['fecha_reserva']} {$reserva['hora_reserva']} ";
        echo "<a href='?reserva_id={$reserva['id']}' style='background:#4CAF50;color:white;padding:5px 10px;text-decoration:none;border-radius:3px;'>Enviar WhatsApp</a>";
        echo "</li>";
    }
    echo "</ul>";
    
    exit;
}

// Enviar notificación para reserva específica
echo "<h3>Enviando notificación para reserva #{$reserva_id}...</h3>";

$url = 'http://' . $_SERVER['HTTP_HOST'] . '/PRY_PROYECTO/app/api/enviar_whatsapp.php';
$data = json_encode(['reserva_id' => $reserva_id]);

echo "<p><strong>URL:</strong> $url</p>";
echo "<p><strong>Data:</strong> $data</p>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

echo "<h3>Resultado:</h3>";
echo "<p><strong>HTTP Code:</strong> $http_code</p>";

if ($curl_error) {
    echo "<p style='color:red;'><strong>Error cURL:</strong> $curl_error</p>";
}

echo "<p><strong>Respuesta:</strong></p>";
echo "<pre style='background:#f0f0f0;padding:10px;border-radius:5px;'>";
echo htmlspecialchars($response);
echo "</pre>";

$json_response = json_decode($response, true);
if ($json_response) {
    echo "<h4>JSON Decodificado:</h4>";
    echo "<pre>";
    print_r($json_response);
    echo "</pre>";
}

curl_close($ch);

echo "<hr>";
echo "<a href='test_envio_manual.php' style='background:#2196F3;color:white;padding:10px 20px;text-decoration:none;border-radius:3px;'>Volver a la lista</a>";
?>
