<?php
// app/buscarAdminPorId.php
// NOTE: This endpoint returns the stored username and password for a given admin id.
// This is insecure (it exposes passwords). Use only for local testing and remove/replace
// with a proper server-side authentication flow in production.

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion/db.php';

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, usuario, password, nombre, apellido, email, activo FROM administradores WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo json_encode(['success' => false, 'message' => 'Administrador no encontrado']);
        exit;
    }

    // Return usuario and password as stored (warning: may be plain text)
    echo json_encode(['success' => true, 'admin' => [
        'id' => (int)$admin['id'],
        'usuario' => $admin['usuario'],
        'password' => $admin['password'],
        'nombre' => $admin['nombre'],
        'apellido' => $admin['apellido'],
        'email' => $admin['email'],
        'activo' => (int)$admin['activo']
    ]]);
} catch (Exception $e) {
    error_log('buscarAdminPorId error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}

?>
