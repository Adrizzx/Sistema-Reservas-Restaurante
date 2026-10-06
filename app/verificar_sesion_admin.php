<?php
session_start();
header('Content-Type: application/json');

// Verificar si existe sesión activa de administrador
$sesion_activa = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;

echo json_encode([
    'activa' => $sesion_activa,
    'usuario' => $sesion_activa ? ($_SESSION['admin_usuario'] ?? null) : null
]);
