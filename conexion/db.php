<?php

// conexion/db.php - SISTEMA DE GESTIÓN ACADÉMICA
// Cargar variables de entorno
require_once __DIR__ . '/../config/env_loader.php';

$host = env('DB_HOST', 'localhost');
$dbname = env('DB_NAME', 'crud_proyecto');
$username = env('DB_USER', 'root');
$password = env('DB_PASS', '');
$charset = env('DB_CHARSET', 'utf8mb4');
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// Crear conexión PDO (usada por algunos scripts)
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Opcional: establecer el charset correctamente
    $pdo->exec("SET NAMES $charset");
} catch (PDOException $e) {
    error_log("PDO connection error: " . $e->getMessage());
    // Para APIs que requieren JSON, lanzar excepción
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false || 
        strpos($_SERVER['SCRIPT_NAME'], 'obtener_') !== false) {
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode([
            'success' => false,
            'message' => 'Error de conexión a la base de datos'
        ]));
    }
}

// Crear conexión mysqli (muchos scripts del proyecto usan $mysqli)
$mysqli = new mysqli($host, $username, $password, $dbname);
if ($mysqli->connect_error) {
    error_log("MySQLi connection error: " . $mysqli->connect_error);
} else {
    // Establecer charset
    $mysqli->set_charset($charset);
}

// Dejar disponible ambas variables ($pdo y $mysqli) para compatibilidad
