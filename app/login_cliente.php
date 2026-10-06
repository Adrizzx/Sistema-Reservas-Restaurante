<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../conexion/db.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener credenciales
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    if (empty($email) || empty($telefono)) {
        echo json_encode(['success' => false, 'message' => 'Email y teléfono son requeridos']);
        exit;
    }

    // Buscar cliente por email y teléfono
    $sql = "SELECT 
                c.id,
                c.nombre,
                c.apellido,
                c.telefono,
                c.email,
                c.cedula,
                c.usuario
            FROM clientes c
            WHERE c.email = :email AND c.telefono = :telefono";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado o credenciales incorrectas']);
        exit;
    }

    // Iniciar sesión
    session_start();
    $_SESSION['cliente_authenticated'] = true;
    $_SESSION['cliente_id'] = $cliente['id'];
    $_SESSION['cliente_nombre'] = $cliente['nombre'] . ' ' . $cliente['apellido'];
    $_SESSION['cliente_email'] = $cliente['email'];

    echo json_encode([
        'success' => true,
        'message' => 'Acceso autorizado',
        'cliente' => [
            'id' => $cliente['id'],
            'nombre' => $cliente['nombre'],
            'apellido' => $cliente['apellido'],
            'email' => $cliente['email'],
            'telefono' => $cliente['telefono'],
            'preferencias' => $cliente['preferencias']
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error general: ' . $e->getMessage()]);
}
?>