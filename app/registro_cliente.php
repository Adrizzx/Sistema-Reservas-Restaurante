<?php
session_start();
header('Content-Type: application/json');

require_once '../conexion/db.php';
require_once '../validacion/ValidadorNombres.php';
require_once '../validacion/ValidadorCedula.php';
require_once '../validacion/ValidadorUsuario.php';
require_once '../validacion/ValidadorTelefono.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    if (!$nombre || !$apellido || !$cedula || !$telefono || !$usuario || !$password) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos requeridos']);
        exit;
    }

    // VALIDAR NOMBRE
    $validacionNombre = ValidadorNombres::validar($nombre, 'nombre');
    if (!$validacionNombre['valido']) {
        echo json_encode(['success' => false, 'message' => $validacionNombre['mensaje']]);
        exit;
    }

    // VALIDAR APELLIDO
    $validacionApellido = ValidadorNombres::validar($apellido, 'apellido');
    if (!$validacionApellido['valido']) {
        echo json_encode(['success' => false, 'message' => $validacionApellido['mensaje']]);
        exit;
    }

    // VALIDAR CÉDULA
    $validacionCedula = ValidadorCedula::validar($cedula);
    if (!$validacionCedula['valido']) {
        echo json_encode(['success' => false, 'message' => $validacionCedula['mensaje']]);
        exit;
    }

    // VERIFICAR QUE LA CÉDULA NO ESTÉ DUPLICADA
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

    // VALIDAR USUARIO
    $validacionUsuario = ValidadorUsuario::validarFormato($usuario);
    if (!$validacionUsuario['valido']) {
        echo json_encode(['success' => false, 'message' => $validacionUsuario['mensaje']]);
        exit;
    }

    // VERIFICAR QUE EL USUARIO NO EXISTA
    $verificarUsuario = ValidadorUsuario::verificarDisponibilidad($usuario, $mysqli);
    if (!$verificarUsuario['disponible']) {
        echo json_encode(['success' => false, 'message' => $verificarUsuario['mensaje']]);
        exit;
    }

    // Limpiar y formatear nombre, apellido y teléfono
    $nombre = ValidadorNombres::limpiar($nombre);
    $apellido = ValidadorNombres::limpiar($apellido);
    $telefono = ValidadorTelefono::limpiar($telefono);

    // Insertar nuevo cliente
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("INSERT INTO clientes (nombre, apellido, cedula, telefono, ciudad, usuario, password_hash, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) throw new Exception('Error en prepare: ' . $mysqli->error);
    $stmt->bind_param('ssssssss', $nombre, $apellido, $cedula, $telefono, $ciudad, $usuario, $password_hash, $email);
    $ok = $stmt->execute();

    if (!$ok) throw new Exception('Error al insertar: ' . $stmt->error);

    $new_id = $stmt->insert_id;
    $stmt->close();

    // Crear sesión automática
    $_SESSION['cliente_id'] = $new_id;
    $_SESSION['cliente_nombre'] = $nombre;
    $_SESSION['cliente_apellido'] = $apellido;
    $_SESSION['cliente_email'] = '';
    $_SESSION['cliente_telefono'] = $telefono;
    $_SESSION['cliente_authenticated'] = true;

    echo json_encode(['success' => true, 'message' => 'Registro exitoso', 'cliente' => ['id' => $new_id, 'nombre' => $nombre, 'apellido' => $apellido]]);
    exit;

} catch (Exception $e) {
    error_log('Error en registro_cliente.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

$mysqli->close();

?>
