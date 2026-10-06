<?php
/**
 * Validador de Cédula Ecuatoriana
 * Valida el formato y dígito verificador de cédulas ecuatorianas
 */

class ValidadorCedula {
    
    /**
     * Valida una cédula ecuatoriana completa
     * @param string $cedula Cédula a validar
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public static function validar($cedula) {
        // Limpiar espacios
        $cedula = trim($cedula);
        
        // Verificar que solo contenga números
        if (!ctype_digit($cedula)) {
            return [
                'valido' => false, 
                'mensaje' => 'La cédula solo debe contener números'
            ];
        }
        
        // Verificar longitud
        if (strlen($cedula) !== 10) {
            return [
                'valido' => false, 
                'mensaje' => 'La cédula debe tener exactamente 10 dígitos'
            ];
        }
        
        // Validar que los dos primeros dígitos correspondan a una provincia válida (01-24)
        $provincia = (int)substr($cedula, 0, 2);
        if ($provincia < 1 || $provincia > 24) {
            return [
                'valido' => false, 
                'mensaje' => 'Los dos primeros dígitos no corresponden a una provincia válida'
            ];
        }
        
        // Validar el dígito verificador
        if (!self::validarDigitoVerificador($cedula)) {
            return [
                'valido' => false, 
                'mensaje' => 'La cédula no es válida (dígito verificador incorrecto)'
            ];
        }
        
        return [
            'valido' => true, 
            'mensaje' => 'Cédula válida'
        ];
    }
    
    /**
     * Valida el dígito verificador de la cédula ecuatoriana
     * @param string $cedula Cédula de 10 dígitos
     * @return bool
     */
    private static function validarDigitoVerificador($cedula) {
        // Los coeficientes para los primeros 9 dígitos
        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;
        
        // Calcular la suma
        for ($i = 0; $i < 9; $i++) {
            $valor = (int)$cedula[$i] * $coeficientes[$i];
            
            // Si el resultado es mayor a 9, restar 9
            if ($valor > 9) {
                $valor -= 9;
            }
            
            $suma += $valor;
        }
        
        // Calcular el dígito verificador
        $residuo = $suma % 10;
        $digitoVerificador = $residuo === 0 ? 0 : 10 - $residuo;
        
        // Comparar con el último dígito de la cédula
        return $digitoVerificador === (int)$cedula[9];
    }
    
    /**
     * Valida que la cédula no esté duplicada en la base de datos
     * @param string $cedula Cédula a verificar
     * @param mysqli $mysqli Conexión a la base de datos
     * @param int|null $excluir_id ID del cliente a excluir de la búsqueda (para edición)
     * @return array ['disponible' => bool, 'mensaje' => string]
     */
    public static function verificarDuplicado($cedula, $mysqli, $excluir_id = null) {
        $sql = "SELECT id FROM clientes WHERE cedula = ?";
        
        if ($excluir_id !== null) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $mysqli->prepare($sql);
        
        if (!$stmt) {
            return [
                'disponible' => false,
                'mensaje' => 'Error al verificar cédula'
            ];
        }
        
        if ($excluir_id !== null) {
            $stmt->bind_param('si', $cedula, $excluir_id);
        } else {
            $stmt->bind_param('s', $cedula);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $disponible = $result->num_rows === 0;
        $stmt->close();
        
        return [
            'disponible' => $disponible,
            'mensaje' => $disponible ? 'Cédula disponible' : 'La cédula ya está registrada en el sistema'
        ];
    }
}
?>
