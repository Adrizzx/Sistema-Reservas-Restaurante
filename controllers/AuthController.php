<?php
/**
 * Controller de Autenticación
 * Gestión de login/registro de clientes y administradores
 */

require_once __DIR__ . '/../models/Cliente.php';

class AuthController {
    private $clienteModel;
    
    public function __construct() {
        $this->clienteModel = new Cliente();
    }
    
    /**
     * Login de cliente
     */
    public function loginCliente($email, $password) {
        $cliente = $this->clienteModel->validarCredenciales($email, $password);
        
        if ($cliente) {
            $_SESSION['cliente_id'] = $cliente['id'];
            $_SESSION['cliente_nombre'] = $cliente['nombre'];
            $_SESSION['cliente_apellido'] = $cliente['apellido'];
            $_SESSION['cliente_email'] = $cliente['email'];
            $_SESSION['cliente_authenticated'] = true;
            
            return ['success' => true, 'cliente' => $cliente];
        }
        
        return ['success' => false, 'message' => 'Credenciales incorrectas'];
    }
    
    /**
     * Registro de cliente
     */
    public function registroCliente($data) {
        // Validar que no exista el email
        if ($this->clienteModel->emailExiste($data['email'])) {
            return ['success' => false, 'message' => 'El email ya está registrado'];
        }
        
        // Crear cliente
        if ($this->clienteModel->create($data)) {
            // Auto-login
            $cliente = $this->clienteModel->getByEmail($data['email']);
            
            $_SESSION['cliente_id'] = $cliente['id'];
            $_SESSION['cliente_nombre'] = $cliente['nombre'];
            $_SESSION['cliente_apellido'] = $cliente['apellido'];
            $_SESSION['cliente_email'] = $cliente['email'];
            $_SESSION['cliente_authenticated'] = true;
            
            return ['success' => true, 'cliente' => $cliente];
        }
        
        return ['success' => false, 'message' => 'Error al registrar cliente'];
    }
    
    /**
     * Logout
     */
    public function logout() {
        session_destroy();
        return ['success' => true];
    }
    
    /**
     * Verificar si está autenticado
     */
    public function isAuthenticated() {
        return isset($_SESSION['cliente_authenticated']) && $_SESSION['cliente_authenticated'] === true;
    }
    
    /**
     * Obtener cliente actual
     */
    public function getCurrentCliente() {
        if ($this->isAuthenticated()) {
            return [
                'id' => $_SESSION['cliente_id'],
                'nombre' => $_SESSION['cliente_nombre'],
                'apellido' => $_SESSION['cliente_apellido'],
                'email' => $_SESSION['cliente_email']
            ];
        }
        
        return null;
    }
}
?>
