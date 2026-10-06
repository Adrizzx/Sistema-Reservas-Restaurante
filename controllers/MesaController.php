<?php
/**
 * Controller de Mesas
 * Gestión de operaciones CRUD y estado de mesas
 */

require_once __DIR__ . '/../models/Mesa.php';

class MesaController {
    private $mesaModel;
    
    public function __construct() {
        $this->mesaModel = new Mesa();
    }
    
    /**
     * Obtener todas las mesas
     */
    public function getAll() {
        return $this->mesaModel->getAll();
    }
    
    /**
     * Obtener estado de mesas para visualización
     */
    public function getEstadoMesas() {
        return $this->mesaModel->getEstadoMesas();
    }
    
    /**
     * Obtener mesas disponibles
     */
    public function getDisponibles() {
        return $this->mesaModel->getDisponibles();
    }
    
    /**
     * Obtener mesa por ID
     */
    public function getById($id) {
        return $this->mesaModel->getById($id);
    }
    
    /**
     * Crear nueva mesa
     */
    public function create($data) {
        // Validaciones
        if (empty($data['numero_mesa'])) {
            return ['success' => false, 'message' => 'Número de mesa requerido'];
        }
        
        if (empty($data['capacidad_maxima']) || $data['capacidad_maxima'] < 1) {
            return ['success' => false, 'message' => 'Capacidad máxima inválida'];
        }
        
        if ($this->mesaModel->create($data)) {
            return ['success' => true, 'message' => 'Mesa creada exitosamente'];
        }
        
        return ['success' => false, 'message' => 'Error al crear mesa'];
    }
    
    /**
     * Actualizar mesa
     */
    public function update($id, $data) {
        if ($this->mesaModel->update($id, $data)) {
            return ['success' => true, 'message' => 'Mesa actualizada exitosamente'];
        }
        
        return ['success' => false, 'message' => 'Error al actualizar mesa'];
    }
    
    /**
     * Eliminar mesa
     */
    public function delete($id) {
        if ($this->mesaModel->delete($id)) {
            return ['success' => true, 'message' => 'Mesa eliminada exitosamente'];
        }
        
        return ['success' => false, 'message' => 'Error al eliminar mesa'];
    }
    
    /**
     * Cambiar estado de mesa
     */
    public function cambiarEstado($id, $estado) {
        $estadosValidos = ['disponible', 'ocupada', 'reservada'];
        
        if (!in_array($estado, $estadosValidos)) {
            return ['success' => false, 'message' => 'Estado inválido'];
        }
        
        if ($this->mesaModel->cambiarEstado($id, $estado)) {
            return ['success' => true, 'message' => 'Estado actualizado'];
        }
        
        return ['success' => false, 'message' => 'Error al cambiar estado'];
    }
    
    /**
     * Seleccionar mesa (guardar en sesión)
     */
    public function seleccionarMesa($mesa_id) {
        $mesa = $this->mesaModel->getById($mesa_id);
        
        if (!$mesa) {
            return ['success' => false, 'message' => 'Mesa no encontrada'];
        }
        
        if ($mesa['estado'] !== 'disponible') {
            return ['success' => false, 'message' => 'Mesa no disponible'];
        }
        
        $_SESSION['mesa_seleccionada_id'] = $mesa_id;
        $_SESSION['mesa_seleccionada_numero'] = $mesa['numero_mesa'];
        
        return ['success' => true, 'message' => 'Mesa seleccionada', 'mesa' => $mesa];
    }
    
    /**
     * Deseleccionar mesa
     */
    public function deseleccionarMesa() {
        unset($_SESSION['mesa_seleccionada_id']);
        unset($_SESSION['mesa_seleccionada_numero']);
        
        return ['success' => true, 'message' => 'Mesa deseleccionada'];
    }
    
    /**
     * Obtener mesa seleccionada
     */
    public function getMesaSeleccionada() {
        if (isset($_SESSION['mesa_seleccionada_id'])) {
            return $this->mesaModel->getById($_SESSION['mesa_seleccionada_id']);
        }
        
        return null;
    }
}
?>
