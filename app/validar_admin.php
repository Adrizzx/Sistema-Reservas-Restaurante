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
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $err]);
    exit;
}

try {
    $sql = "SELECT id, usuario, password, nombre, apellido, email, rol, activo FROM administradores WHERE usuario = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error preparando la consulta: ' . $mysqli->error);
    }

    $stmt->bind_param('s', $usuario);
    $stmt->execute();
    
    // Use store_result() + bind_result() to be compatible with installations
    // that do not have mysqlnd (where get_result() is unavailable).
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        // Bind columns
        $stmt->bind_result($id, $usuario_db, $password_db, $nombre, $apellido, $email, $rol, $activo);
        $stmt->fetch();
        
        $admin = [
            'id' => $id,
            'usuario' => $usuario_db,
            'password' => $password_db,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'email' => $email,
            'rol' => $rol,
            'activo' => $activo
        ];

        if (isset($admin['activo']) && (int)$admin['activo'] !== 1) {
            echo json_encode(['success' => false, 'message' => 'Usuario inactivo']);
            $stmt->close();
            exit;
        }

        $password_ok = false;
        if (!empty($admin['password']) && password_verify($password, $admin['password'])) {
            $password_ok = true;
        } elseif ($admin['password'] === $password) {
            // temporary fallback for plaintext passwords
            $password_ok = true;
        }

        if ($password_ok) {
            // Create session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_usuario'] = $admin['usuario'];
            $_SESSION['admin_nombre'] = $admin['nombre'];
            $_SESSION['admin_apellido'] = $admin['apellido'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_rol'] = $admin['rol'] ?? null;
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_login_time'] = time();

            // Update last access (commented - column may not exist)
            // $upd = $mysqli->prepare("UPDATE administradores SET ultimo_acceso = NOW() WHERE id = ?");
            // if ($upd) { $upd->bind_param('i', $admin['id']); $upd->execute(); $upd->close(); }

            // Log access (commented - table may not exist)
            // $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            // $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            // $log = $mysqli->prepare("INSERT INTO log_accesos (usuario_id, tipo_usuario, ip_address, user_agent, fecha_acceso, exitoso) VALUES (?, 'admin', ?, ?, NOW(), 1)");
            // if ($log) { $log->bind_param('iss', $admin['id'], $ip, $ua); $log->execute(); $log->close(); }

            echo json_encode(['success' => true, 'message' => 'Acceso autorizado', 'admin' => ['id' => $admin['id'], 'usuario' => $admin['usuario'], 'nombre' => $admin['nombre'], 'email' => $admin['email']]]);
        } else {
            // Log failed attempt (commented - table may not exist)
            // $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            // $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            // $logf = $mysqli->prepare("INSERT INTO log_accesos (usuario_id, tipo_usuario, ip_address, user_agent, fecha_acceso, exitoso) VALUES (?, 'admin', ?, ?, NOW(), 0)");
            // if ($logf) { $idtmp = $admin['id']; $logf->bind_param('iss', $idtmp, $ip, $ua); $logf->execute(); $logf->close(); }

            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o inactivo']);
    }

    if ($stmt) $stmt->close();
} catch (Exception $e) {
    error_log('Error en validar_admin.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

$mysqli->close();
