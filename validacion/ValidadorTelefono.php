<?php
/**
 * Validador de Números de Teléfono/Celular
 * Valida que el número tenga exactamente 10 dígitos
 */

class ValidadorTelefono {
    
    /**
     * Valida un número de teléfono/celular
     * @param string $telefono Número de teléfono a validar
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public static function validar($telefono) {
        $telefono = trim($telefono);
        
        // Verificar que no esté vacío
        if (empty($telefono)) {
            return [
                'valido' => false,
                'mensaje' => 'El número de celular es requerido'
            ];
        }
        
        // Eliminar espacios y guiones que el usuario pueda haber ingresado
        $telefonoLimpio = preg_replace('/[\s\-]/', '', $telefono);
        
        // Verificar que solo contenga dígitos
        if (!preg_match('/^\d+$/', $telefonoLimpio)) {
            return [
                'valido' => false,
                'mensaje' => 'El número de celular solo puede contener dígitos'
            ];
        }
        
        // Verificar que tenga exactamente 10 dígitos
        if (strlen($telefonoLimpio) !== 10) {
            return [
                'valido' => false,
                'mensaje' => 'El número de celular debe tener exactamente 10 dígitos'
            ];
        }
        
        // Verificar que empiece con 09 (formato Ecuador)
        if (!preg_match('/^09/', $telefonoLimpio)) {
            return [
                'valido' => false,
                'mensaje' => 'El número de celular debe comenzar con 09'
            ];
        }
        
        return [
            'valido' => true,
            'mensaje' => 'Número de celular válido'
        ];
    }
    
    /**
     * Limpia un número de teléfono eliminando espacios y guiones
     * @param string $telefono Teléfono a limpiar
     * @return string Teléfono limpio (solo dígitos)
     */
    public static function limpiar($telefono) {
        return preg_replace('/[\s\-]/', '', trim($telefono));
    }
    
    /**
     * Formatea un número de teléfono para mostrar (09XX-XXX-XXX)
     * @param string $telefono Teléfono a formatear
     * @return string Teléfono formateado
     */
    public static function formatear($telefono) {
        $limpio = self::limpiar($telefono);
        
        if (strlen($limpio) === 10) {
            return substr($limpio, 0, 4) . '-' . substr($limpio, 4, 3) . '-' . substr($limpio, 7);
        }
        
        return $limpio;
    }
}
?>
