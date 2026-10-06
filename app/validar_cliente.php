<?php
session_start();

// Configurar encabezados para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Conectar a la base de datos
require_once '../conexion/db.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

try {
    // Obtener datos del formulario
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Usuario y contraseña son requeridos']);
        exit;
    }

    // Primero intentar en la tabla clientes (usuario/password)
    $query_v2 = "SELECT id, nombre, apellido, usuario, password_hash, telefono, ciudad FROM clientes WHERE usuario = ? LIMIT 1";
    $stmt_v2 = $mysqli->prepare($query_v2);
    if ($stmt_v2) {
        $stmt_v2->bind_param('s', $usuario);
        $stmt_v2->execute();
        $res = $stmt_v2->get_result();
        if ($res && $res->num_rows === 1) {
            $cliente = $res->fetch_assoc();
            // verificar contraseña
            if (isset($cliente['password_hash']) && password_verify($password, $cliente['password_hash'])) {
                // Crear sesión
                $_SESSION['cliente_id'] = $cliente['id'];
                $_SESSION['cliente_nombre'] = $cliente['nombre'];
                $_SESSION['cliente_apellido'] = $cliente['apellido'];
                $_SESSION['cliente_email'] = '';
                $_SESSION['cliente_telefono'] = $cliente['telefono'];
                $_SESSION['cliente_login_time'] = time();
                $_SESSION['cliente_authenticated'] = true;

                // Registrar acceso - DESHABILITADO (tabla log_accesos no existe)
                // $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                // $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                // $log_query = "INSERT INTO log_accesos (usuario_id, tipo_usuario, ip_address, user_agent, fecha_acceso, exitoso) VALUES (?, 'cliente', ?, ?, NOW(), 1)";
                // $log_stmt = $mysqli->prepare($log_query);
                // if ($log_stmt) {
                //     $log_stmt->bind_param('iss', $cliente['id'], $ip_address, $user_agent);
                //     $log_stmt->execute();
                //     $log_stmt->close();
                // }

                echo json_encode(['success' => true, 'message' => 'Acceso autorizado', 'cliente' => ['id' => $cliente['id'], 'nombre' => $cliente['nombre'], 'apellido' => $cliente['apellido'] ], 'estudiante' => ['id' => $cliente['id'], 'nombre' => $cliente['nombre'], 'apellido' => $cliente['apellido'] ]]);
                $stmt_v2->close();
                $mysqli->close();
                exit;
            } else {
                // contraseña incorrecta
                echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
                $stmt_v2->close();
                $mysqli->close();
                exit;
            }
        }
        $stmt_v2->close();
    }

    // Si no se encontró en clientes, intentar compatibilidad con la tabla antigua (email+telefono)
    $email = $usuario; // en algunos formularios anteriores se pasaba email en 'usuario'
    $telefono = $password; // y teléfono en 'password'
    $query_old = "SELECT id, nombre, apellido, email, telefono FROM clientes WHERE email = ? AND telefono = ?";
    $stmt2 = $mysqli->prepare($query_old);
    if ($stmt2) {
        $stmt2->bind_param('ss', $email, $telefono);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($res2 && $res2->num_rows === 1) {
            $cliente = $res2->fetch_assoc();
            // Crear sesión similar a antes
            $_SESSION['cliente_id'] = $cliente['id'];
            $_SESSION['cliente_nombre'] = $cliente['nombre'];
            $_SESSION['cliente_apellido'] = $cliente['apellido'];
            $_SESSION['cliente_email'] = $cliente['email'];
            $_SESSION['cliente_telefono'] = $cliente['telefono'];
            $_SESSION['cliente_login_time'] = time();
            $_SESSION['cliente_authenticated'] = true;

            // registrar acceso - DESHABILITADO (tabla log_accesos no existe)
            // $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            // $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            // $log_query = "INSERT INTO log_accesos (usuario_id, tipo_usuario, ip_address, user_agent, fecha_acceso, exitoso) VALUES (?, 'cliente', ?, ?, NOW(), 1)";
            // $log_stmt = $mysqli->prepare($log_query);
            // if ($log_stmt) {
            //     $log_stmt->bind_param('iss', $cliente['id'], $ip_address, $user_agent);
            //     $log_stmt->execute();
            //     $log_stmt->close();
            // }

            echo json_encode(['success' => true, 'message' => 'Acceso autorizado', 'cliente' => ['id' => $cliente['id'], 'nombre' => $cliente['nombre'], 'apellido' => $cliente['apellido'] ], 'estudiante' => ['id' => $cliente['id'], 'nombre' => $cliente['nombre'], 'apellido' => $cliente['apellido'] ]]);
            $stmt2->close();
            $mysqli->close();
            exit;
        }
        $stmt2->close();
    }

    // Si llegamos aquí no se encontró el cliente
    echo json_encode(['success' => false, 'message' => 'Cliente no encontrado. Regístrate primero']);

} catch (Exception $e) {
    error_log("Error en validar_cliente.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

$mysqli->close();
?>