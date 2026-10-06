<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verificar si existe sesión de cliente activa
if (isset($_SESSION['cliente_authenticated']) && $_SESSION['cliente_authenticated'] === true && isset($_SESSION['cliente_id'])) {
    echo json_encode([
        'authenticated' => true,
        'cliente' => [
            'id' => $_SESSION['cliente_id'],
            'nombre' => $_SESSION['cliente_nombre'] ?? '',
            'apellido' => $_SESSION['cliente_apellido'] ?? '',
            'usuario' => $_SESSION['cliente_usuario'] ?? ''
        ]
    ]);
} else {
    echo json_encode([
        'authenticated' => false
    ]);
}
