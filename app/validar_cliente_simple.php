<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if ($usuario === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Usuario y contraseña son requeridos']);
    exit;
}

// Basic DB connection check
if (!isset($mysqli) || ($mysqli instanceof mysqli && $mysqli->connect_errno)) {
    $err = isset($mysqli) && ($mysqli instanceof mysqli) ? $mysqli->connect_error : 'Conexión no inicializada';
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

try {
    // Buscar cliente por usuario
    $sql = "SELECT id, nombre, apellido, cedula, telefono, ciudad, usuario, password_hash, email FROM clientes WHERE usuario = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Error preparando la consulta: ' . $mysqli->error);
    }

    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    
    // Use store_result() + bind_result() for compatibility
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        // Bind columns
        $stmt->bind_result($id, $nombre, $apellido, $cedula, $telefono, $ciudad, $usuario_db, $password_db, $email);
        $stmt->fetch();
        
        $cliente = [
            'id' => $id,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'cedula' => $cedula,
            'telefono' => $telefono,
            'ciudad' => $ciudad,
            'usuario' => $usuario_db,
            'password_hash' => $password_db,
            'email' => $email
        ];

        // Comparación directa de password (sin hash)
        if ($cliente['password_hash'] === $password) {
            // Create session
            $_SESSION['cliente_id'] = $cliente['id'];
            $_SESSION['cliente_usuario'] = $cliente['usuario'];
            $_SESSION['cliente_nombre'] = $cliente['nombre'];
            $_SESSION['cliente_apellido'] = $cliente['apellido'];
            $_SESSION['cliente_telefono'] = $cliente['telefono'];
            $_SESSION['cliente_ciudad'] = $cliente['ciudad'];
            $_SESSION['cliente_email'] = $cliente['email'];
            $_SESSION['cliente_authenticated'] = true;
            $_SESSION['cliente_login_time'] = time();

            echo json_encode([
                'success' => true, 
                'message' => 'Acceso autorizado',
                'cliente' => [
                    'id' => $cliente['id'],
                    'usuario' => $cliente['usuario'],
                    'nombre' => $cliente['nombre'],
                    'apellido' => $cliente['apellido'],
                    'telefono' => $cliente['telefono'],
                    'email' => $cliente['email']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }

    if ($stmt) $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

$mysqli->close();
