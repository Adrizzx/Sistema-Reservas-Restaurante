<?php
/**
 * Validador de Usuarios y Correos
 * Valida unicidad de usuarios y correos electrónicos
 */

class ValidadorUsuario {
    
    /**
     * Valida el formato de un nombre de usuario
     * @param string $usuario Usuario a validar
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public static function validarFormato($usuario) {
        $usuario = trim($usuario);
        
        if (empty($usuario)) {
            return [
                'valido' => false,
                'mensaje' => 'El usuario es requerido'
            ];
        }
        
        if (strlen($usuario) < 4) {
            return [
                'valido' => false,
                'mensaje' => 'El usuario debe tener al menos 4 caracteres'
            ];
        }
        
        if (strlen($usuario) > 30) {
            return [
                'valido' => false,
                'mensaje' => 'El usuario no puede tener más de 30 caracteres'
            ];
        }
        
        // Solo letras, números, guiones y guiones bajos
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $usuario)) {
            return [
                'valido' => false,
                'mensaje' => 'El usuario solo puede contener letras, números, guiones y guiones bajos'
            ];
        }
        
        return [
            'valido' => true,
            'mensaje' => 'Usuario válido'
        ];
    }
    
    /**
     * Verifica si un usuario ya está en uso
     * @param string $usuario Usuario a verificar
     * @param mysqli $mysqli Conexión a la base de datos
     * @param int|null $excluir_id ID del cliente a excluir (para edición)
     * @return array ['disponible' => bool, 'mensaje' => string]
     */
    public static function verificarDisponibilidad($usuario, $mysqli, $excluir_id = null) {
        $sql = "SELECT id FROM clientes WHERE usuario = ?";
        
        if ($excluir_id !== null) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $mysqli->prepare($sql);
        
        if (!$stmt) {
            return [
                'disponible' => false,
                'mensaje' => 'Error al verificar usuario'
            ];
        }
        
        if ($excluir_id !== null) {
            $stmt->bind_param('si', $usuario, $excluir_id);
        } else {
            $stmt->bind_param('s', $usuario);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $disponible = $result->num_rows === 0;
        $stmt->close();
        
        return [
            'disponible' => $disponible,
            'mensaje' => $disponible ? 'Usuario disponible' : 'El usuario ya está en uso'
        ];
    }
    
    /**
     * Valida el formato de un correo electrónico
     * @param string $correo Correo a validar
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public static function validarCorreo($correo) {
        $correo = trim($correo);
        
        if (empty($correo)) {
            return [
                'valido' => false,
                'mensaje' => 'El correo electrónico es requerido'
            ];
        }
        
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return [
                'valido' => false,
                'mensaje' => 'El formato del correo electrónico no es válido'
            ];
        }
        
        return [
            'valido' => true,
            'mensaje' => 'Correo válido'
        ];
    }
    
    /**
     * Verifica si un correo ya está en uso
     * @param string $correo Correo a verificar
     * @param mysqli $mysqli Conexión a la base de datos
     * @param int|null $excluir_id ID del cliente a excluir (para edición)
     * @return array ['disponible' => bool, 'mensaje' => string]
     */
    public static function verificarCorreoDisponible($correo, $mysqli, $excluir_id = null) {
        $sql = "SELECT id FROM clientes WHERE email = ?";
        
        if ($excluir_id !== null) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $mysqli->prepare($sql);
        
        if (!$stmt) {
            return [
                'disponible' => false,
                'mensaje' => 'Error al verificar correo'
            ];
        }
        
        if ($excluir_id !== null) {
            $stmt->bind_param('si', $correo, $excluir_id);
        } else {
            $stmt->bind_param('s', $correo);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $disponible = $result->num_rows === 0;
        $stmt->close();
        
        return [
            'disponible' => $disponible,
            'mensaje' => $disponible ? 'Correo disponible' : 'El correo electrónico ya está registrado'
        ];
    }
}
?>
