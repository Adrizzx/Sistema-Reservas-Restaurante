<?php
/**
 * Controlador de Auditoría
 * Registra todas las acciones importantes del sistema
 */

class AuditoriaController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Registrar cambio en horarios
     */
    public function registrarCambioHorarios($adminId, $adminNombre, $configAnterior, $configNueva, $reservasAfectadas = []) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO auditoria_horarios (
                    admin_id, 
                    admin_nombre, 
                    accion,
                    configuracion_anterior,
                    configuracion_nueva,
                    reservas_afectadas,
                    reservas_canceladas,
                    notificaciones_enviadas,
                    ip_address,
                    user_agent,
                    observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $totalAfectadas = count($reservasAfectadas);
            $observaciones = $totalAfectadas > 0 
                ? "Se cancelaron $totalAfectadas reserva(s) por cambio de horarios"
                : "No hubo reservas afectadas";
            
            $stmt->execute([
                $adminId,
                $adminNombre,
                'actualizar_horarios',
                json_encode($configAnterior, JSON_UNESCAPED_UNICODE),
                json_encode($configNueva, JSON_UNESCAPED_UNICODE),
                $totalAfectadas,
                $totalAfectadas, // Todas las afectadas se cancelan
                $totalAfectadas, // Notificación por cada cancelación
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                $observaciones
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Error registrando auditoría de horarios: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar acción en reserva
     */
    public function registrarAccionReserva($reservaId, $adminId, $accion, $estadoAnterior, $estadoNuevo, $datosAnteriores = null, $datosNuevos = null, $motivo = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO auditoria_reservas (
                    reserva_id,
                    admin_id,
                    accion,
                    estado_anterior,
                    estado_nuevo,
                    datos_anteriores,
                    datos_nuevos,
                    motivo,
                    ip_address,
                    user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $reservaId,
                $adminId,
                $accion,
                $estadoAnterior,
                $estadoNuevo,
                $datosAnteriores ? json_encode($datosAnteriores, JSON_UNESCAPED_UNICODE) : null,
                $datosNuevos ? json_encode($datosNuevos, JSON_UNESCAPED_UNICODE) : null,
                $motivo,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Error registrando auditoría de reserva: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar acción general del sistema
     */
    public function registrarAccionSistema($usuarioId, $usuarioTipo, $usuarioNombre, $modulo, $accion, $descripcion, $tablaAfectada = null, $registroId = null, $datosAnteriores = null, $datosNuevos = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO auditoria_sistema (
                    usuario_id,
                    usuario_tipo,
                    usuario_nombre,
                    modulo,
                    accion,
                    tabla_afectada,
                    registro_id,
                    descripcion,
                    datos_anteriores,
                    datos_nuevos,
                    ip_address,
                    user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $usuarioId,
                $usuarioTipo,
                $usuarioNombre,
                $modulo,
                $accion,
                $tablaAfectada,
                $registroId,
                $descripcion,
                $datosAnteriores ? json_encode($datosAnteriores, JSON_UNESCAPED_UNICODE) : null,
                $datosNuevos ? json_encode($datosNuevos, JSON_UNESCAPED_UNICODE) : null,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            return $this->pdo->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Error registrando auditoría del sistema: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener historial de cambios de horarios
     */
    public function obtenerHistorialHorarios($limite = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM auditoria_horarios 
                ORDER BY fecha_cambio DESC 
                LIMIT ?
            ");
            $stmt->execute([$limite]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo historial de horarios: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener historial de una reserva específica
     */
    public function obtenerHistorialReserva($reservaId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ar.*, a.nombre as admin_nombre_completo
                FROM auditoria_reservas ar
                LEFT JOIN administradores a ON ar.admin_id = a.id
                WHERE ar.reserva_id = ?
                ORDER BY ar.fecha_accion DESC
            ");
            $stmt->execute([$reservaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo historial de reserva: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener acciones de un administrador
     */
    public function obtenerAccionesAdmin($adminId, $fechaInicio = null, $fechaFin = null) {
        try {
            $query = "
                SELECT 'horarios' as tipo, accion, fecha_cambio as fecha, 
                       reservas_afectadas, observaciones
                FROM auditoria_horarios 
                WHERE admin_id = ?
            ";
            
            if ($fechaInicio && $fechaFin) {
                $query .= " AND fecha_cambio BETWEEN ? AND ?";
            }
            
            $query .= " UNION ALL ";
            
            $query .= "
                SELECT 'reservas' as tipo, accion, fecha_accion as fecha,
                       NULL as reservas_afectadas, motivo as observaciones
                FROM auditoria_reservas 
                WHERE admin_id = ?
            ";
            
            if ($fechaInicio && $fechaFin) {
                $query .= " AND fecha_accion BETWEEN ? AND ?";
            }
            
            $query .= " ORDER BY fecha DESC";
            
            $stmt = $this->pdo->prepare($query);
            
            if ($fechaInicio && $fechaFin) {
                $stmt->execute([$adminId, $fechaInicio, $fechaFin, $adminId, $fechaInicio, $fechaFin]);
            } else {
                $stmt->execute([$adminId, $adminId]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo acciones del admin: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener IP del cliente
     */
    private function getClientIP() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
}
