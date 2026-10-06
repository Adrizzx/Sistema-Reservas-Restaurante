<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/db.php';
require_once __DIR__ . '/../validacion/ValidadorNombres.php';
require_once __DIR__ . '/../validacion/ValidadorCedula.php';
require_once __DIR__ . '/../validacion/ValidadorUsuario.php';
require_once __DIR__ . '/../validacion/ValidadorTelefono.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$cedula = trim($_POST['cedula'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$ciudad = trim($_POST['ciudad'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';
$email = trim($_POST['email'] ?? '');

// Validar campos requeridos
if (empty($nombre) || empty($apellido) || empty($cedula) || empty($telefono) || empty($usuario) || empty($password) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit;
}

// Basic DB connection check
if (!isset($mysqli) || ($mysqli instanceof mysqli && $mysqli->connect_errno)) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

try {
    // ============================================
    // VALIDACIONES CON LOS NUEVOS VALIDADORES
    // ============================================
    
    // VALIDAR NOMBRE (sin números)
    $validacionNombre = ValidadorNombres::validar($nombre, 'nombre');
    if (!$validacionNombre['valido']) {
        echo json_encode(['success' => false, 'message' => $validacionNombre['mensaje']]);
        exit;
    }

    // VALIDAR APELLIDO (sin números)
    $validacionApellido = ValidadorNombres::validar($apellido, 'apellido');
    if (!$validacionApellido['valido']) {
        echo json_encode(['success' => false, 'message' => $validacionApellido['mensaje']]);
        exit;
    }

    // VALIDAR CÉDULA (10 dígitos + verificador)
    $validacionCedula = ValidadorCedula::validar($cedula);
    if (!$validacionCedula['valido']) {
        echo json_encode(['success' => false, 'message' => $validacionCedula['mensaje']]);
        exit;
    }

    // VERIFICAR CÉDULA NO DUPLICADA
    $verificarCedula = ValidadorCedula::verificarDuplicado($cedula, $mysqli);
    if (!$verificarCedula['disponible']) {
        echo json_encode(['success' => false, 'message' => $verificarCedula['mensaje']]);
        exit;
    }

    // VALIDAR TELÉFONO (10 dígitos)
    $validacionTelefono = ValidadorTelefono::validar($telefono);
    if (!$validacionTelefono['valido']) {
        echo json_encode(['success' => false, 'message' => $validacionTelefono['mensaje']]);
        exit;
    }

    // VALIDAR FORMATO DE USUARIO
    $validacionUsuario = ValidadorUsuario::validarFormato($usuario);
    if (!$validacionUsuario['valido']) {
        echo json_encode(['success' => false, 'message' => $validacionUsuario['mensaje']]);
        exit;
    }

    // VERIFICAR USUARIO NO DUPLICADO
    $verificarUsuario = ValidadorUsuario::verificarDisponibilidad($usuario, $mysqli);
    if (!$verificarUsuario['disponible']) {
        echo json_encode(['success' => false, 'message' => $verificarUsuario['mensaje']]);
        exit;
    }

    // VALIDAR EMAIL
    $validacionEmail = ValidadorUsuario::validarCorreo($email);
    if (!$validacionEmail['valido']) {
        echo json_encode(['success' => false, 'message' => $validacionEmail['mensaje']]);
        exit;
    }

    // VERIFICAR EMAIL NO DUPLICADO
    $verificarEmail = ValidadorUsuario::verificarCorreoDisponible($email, $mysqli);
    if (!$verificarEmail['disponible']) {
        echo json_encode(['success' => false, 'message' => $verificarEmail['mensaje']]);
        exit;
    }

    // Limpiar y formatear nombre, apellido y teléfono
    $nombre = ValidadorNombres::limpiar($nombre);
    $apellido = ValidadorNombres::limpiar($apellido);
    $telefono = ValidadorTelefono::limpiar($telefono);
    
    // Insertar nuevo cliente (password sin hash en columna password_hash)
    $insert_sql = "INSERT INTO clientes (nombre, apellido, cedula, telefono, ciudad, usuario, password_hash, email) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $mysqli->prepare($insert_sql);
    
    if (!$insert_stmt) {
        throw new Exception('Error al preparar inserción: ' . $mysqli->error);
    }
    
    $insert_stmt->bind_param('ssssssss', $nombre, $apellido, $cedula, $telefono, $ciudad, $usuario, $password, $email);
    
    if ($insert_stmt->execute()) {
        $nuevo_id = $insert_stmt->insert_id;
        $insert_stmt->close();
        
        // Crear sesión automáticamente después del registro
        $_SESSION['cliente_id'] = $nuevo_id;
        $_SESSION['cliente_usuario'] = $usuario;
        $_SESSION['cliente_nombre'] = $nombre;
        $_SESSION['cliente_apellido'] = $apellido;
        $_SESSION['cliente_telefono'] = $telefono;
        $_SESSION['cliente_ciudad'] = $ciudad;
        $_SESSION['cliente_authenticated'] = true;
        $_SESSION['cliente_login_time'] = time();
        
        echo json_encode([
            'success' => true,
            'message' => 'Registro exitoso',
            'cliente' => [
                'id' => $nuevo_id,
                'usuario' => $usuario,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono' => $telefono
            ]
        ]);
    } else {
        throw new Exception('Error al ejecutar inserción: ' . $insert_stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al registrar: ' . $e->getMessage()]);
}

$mysqli->close();
