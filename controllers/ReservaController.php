<?php
/**
 * Controller de Reservas
 * Gestión de reservas del restaurante
 */

require_once __DIR__ . '/../models/Reserva.php';
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../validacion/ValidadorReserva.php';

class ReservaController {
    private $reservaModel;
    private $mesaModel;
    
    public function __construct() {
        $this->reservaModel = new Reserva();
        $this->mesaModel = new Mesa();
    }
    
    /**
     * Obtener todas las reservas
     */
    public function getAll() {
        return $this->reservaModel->getAll();
    }
    
    /**
     * Obtener reservas de un cliente
     */
    public function getByCliente($cliente_id) {
        return $this->reservaModel->getByCliente($cliente_id);
    }
    
    /**
     * Obtener reserva por ID
     */
    public function getById($id) {
        return $this->reservaModel->getById($id);
    }
    
    /**
     * Crear nueva reserva
     */
    public function create($data) {
        // Validaciones básicas
        if (empty($data['cliente_id'])) {
            return ['success' => false, 'message' => 'Cliente requerido'];
        }
        
        if (empty($data['mesa_id'])) {
            return ['success' => false, 'message' => 'Mesa requerida'];
        }
        
        if (empty($data['fecha_reserva']) || empty($data['hora_reserva'])) {
            return ['success' => false, 'message' => 'Fecha y hora requeridas'];
        }
        
        if (empty($data['num_personas']) || $data['num_personas'] < 1) {
            return ['success' => false, 'message' => 'Número de personas inválido'];
        }

        // VALIDAR FECHA Y HORA CON LOS NUEVOS VALIDADORES
        require_once __DIR__ . '/../conexion/db.php';
        global $mysqli;
        
        $validacionReserva = ValidadorReserva::validarReservaCompleta(
            $data['fecha_reserva'], 
            $data['hora_reserva'],
            $mysqli
        );
        
        if (!$validacionReserva['valido']) {
            return ['success' => false, 'message' => $validacionReserva['mensaje']];
        }
        
        // Verificar que la mesa existe y está disponible
        $mesa = $this->mesaModel->getById($data['mesa_id']);
        if (!$mesa) {
            return ['success' => false, 'message' => 'Mesa no encontrada'];
        }
        
        // Verificar capacidad
        if ($data['num_personas'] < $mesa['capacidad_minima'] || $data['num_personas'] > $mesa['capacidad_maxima']) {
            return ['success' => false, 'message' => 'Número de personas fuera de capacidad de la mesa'];
        }
        
        // Verificar disponibilidad
        if (!$this->reservaModel->verificarDisponibilidad($data['mesa_id'], $data['fecha_reserva'], $data['hora_reserva'])) {
            return ['success' => false, 'message' => 'La mesa no está disponible en esa fecha y hora'];
        }
        
        // Crear reserva
        $reserva_id = $this->reservaModel->create($data);
        
        if ($reserva_id) {
            // Cambiar estado de mesa a reservada
            $this->mesaModel->cambiarEstado($data['mesa_id'], 'reservada');
            
            // Limpiar sesión de mesa seleccionada
            unset($_SESSION['mesa_seleccionada_id']);
            unset($_SESSION['mesa_seleccionada_numero']);
            
            return [
                'success' => true, 
                'message' => 'Reserva creada exitosamente',
                'reserva_id' => $reserva_id
            ];
        }
        
        return ['success' => false, 'message' => 'Error al crear reserva'];
    }
    
    /**
     * Actualizar reserva
     */
    public function update($id, $data) {
        $reserva = $this->reservaModel->getById($id);
        
        if (!$reserva) {
            return ['success' => false, 'message' => 'Reserva no encontrada'];
        }
        
        // Verificar disponibilidad si cambió fecha/hora
        if (isset($data['fecha_reserva']) || isset($data['hora_reserva'])) {
            $fecha = $data['fecha_reserva'] ?? $reserva['fecha_reserva'];
            $hora = $data['hora_reserva'] ?? $reserva['hora_reserva'];
            
            if (!$this->reservaModel->verificarDisponibilidad($reserva['mesa_id'], $fecha, $hora, $id)) {
                return ['success' => false, 'message' => 'La mesa no está disponible en esa fecha y hora'];
            }
        }
        
        if ($this->reservaModel->update($id, $data)) {
            return ['success' => true, 'message' => 'Reserva actualizada exitosamente'];
        }
        
        return ['success' => false, 'message' => 'Error al actualizar reserva'];
    }
    
    /**
     * Cancelar reserva
     */
    public function cancelar($id) {
        $reserva = $this->reservaModel->getById($id);
        
        if (!$reserva) {
            return ['success' => false, 'message' => 'Reserva no encontrada'];
        }
        
        if ($this->reservaModel->cambiarEstado($id, 'cancelada')) {
            // Liberar mesa
            $this->mesaModel->cambiarEstado($reserva['mesa_id'], 'disponible');
            
            return ['success' => true, 'message' => 'Reserva cancelada'];
        }
        
        return ['success' => false, 'message' => 'Error al cancelar reserva'];
    }
    
    /**
     * Eliminar reserva
     */
    public function delete($id) {
        if ($this->reservaModel->delete($id)) {
            return ['success' => true, 'message' => 'Reserva eliminada'];
        }
        
        return ['success' => false, 'message' => 'Error al eliminar reserva'];
    }
    
    /**
     * Obtener reservas recientes
     */
    public function getRecientes($limit = 10) {
        return $this->reservaModel->getRecientes($limit);
    }
}
?>
