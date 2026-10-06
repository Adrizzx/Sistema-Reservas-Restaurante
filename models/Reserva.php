<?php
/**
 * Modelo Reserva
 * Gestión de reservas del restaurante
 */

require_once __DIR__ . '/../config/database.php';

class Reserva {
    private $db;
    private $table = 'reservas';
    
    public $id;
    public $cliente_id;
    public $mesa_id;
    public $fecha_reserva;
    public $hora_reserva;
    public $num_personas;
    public $estado;
    public $notas;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todas las reservas
     */
    public function getAll() {
        $query = "SELECT r.*, 
                         c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.email as cliente_email,
                         m.numero_mesa, m.precio_reserva
                  FROM {$this->table} r
                  LEFT JOIN clientes c ON r.cliente_id = c.id
                  LEFT JOIN mesas m ON r.mesa_id = m.id
                  ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener reservas por cliente
     */
    public function getByCliente($cliente_id) {
        $query = "SELECT r.*, m.numero_mesa, m.ubicacion, m.precio_reserva
                  FROM {$this->table} r
                  LEFT JOIN mesas m ON r.mesa_id = m.id
                  WHERE r.cliente_id = ?
                  ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$cliente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener reserva por ID
     */
    public function getById($id) {
        $query = "SELECT r.*, 
                         c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.email as cliente_email,
                         m.numero_mesa, m.ubicacion, m.precio_reserva
                  FROM {$this->table} r
                  LEFT JOIN clientes c ON r.cliente_id = c.id
                  LEFT JOIN mesas m ON r.mesa_id = m.id
                  WHERE r.id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear nueva reserva
     */
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (cliente_id, mesa_id, fecha_reserva, hora_reserva, num_personas, estado, notas) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        
        $result = $stmt->execute([
            $data['cliente_id'],
            $data['mesa_id'],
            $data['fecha_reserva'],
            $data['hora_reserva'],
            $data['num_personas'],
            $data['estado'] ?? 'pendiente',
            $data['notas'] ?? ''
        ]);
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Actualizar reserva
     */
    public function update($id, $data) {
        $query = "UPDATE {$this->table} 
                  SET fecha_reserva = ?, hora_reserva = ?, num_personas = ?, estado = ?, notas = ?
                  WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            $data['fecha_reserva'],
            $data['hora_reserva'],
            $data['num_personas'],
            $data['estado'],
            $data['notas'] ?? '',
            $id
        ]);
    }
    
    /**
     * Cambiar estado de reserva
     */
    public function cambiarEstado($id, $estado) {
        $query = "UPDATE {$this->table} SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$estado, $id]);
    }
    
    /**
     * Eliminar reserva
     */
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Verificar disponibilidad de mesa
     * Una reserva confirmada bloquea la mesa por 2 horas
     */
    public function verificarDisponibilidad($mesa_id, $fecha, $hora, $excluir_reserva_id = null) {
        // Verificar si hay conflictos con reservas existentes (pendientes, confirmadas, en_curso)
        // Una reserva bloquea la mesa por 2 horas desde su hora de inicio
        $query = "SELECT COUNT(*) FROM {$this->table} 
                  WHERE mesa_id = ? 
                  AND fecha_reserva = ? 
                  AND estado IN ('pendiente', 'confirmada', 'en_curso')
                  AND (
                      -- La nueva reserva comienza durante una reserva existente
                      (? >= hora_reserva AND ? < ADDTIME(hora_reserva, '02:00:00'))
                      OR
                      -- La nueva reserva termina durante una reserva existente
                      (ADDTIME(?, '02:00:00') > hora_reserva AND ADDTIME(?, '02:00:00') <= ADDTIME(hora_reserva, '02:00:00'))
                      OR
                      -- La nueva reserva envuelve completamente una reserva existente
                      (? <= hora_reserva AND ADDTIME(?, '02:00:00') >= ADDTIME(hora_reserva, '02:00:00'))
                  )";
        
        $params = [$mesa_id, $fecha, $hora, $hora, $hora, $hora, $hora, $hora];
        
        if ($excluir_reserva_id) {
            $query .= " AND id != ?";
            $params[] = $excluir_reserva_id;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() == 0;
    }
    
    /**
     * Obtener reservas recientes (últimas 10)
     */
    public function getRecientes($limit = 10) {
        $query = "SELECT r.*, 
                         c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                         m.numero_mesa
                  FROM {$this->table} r
                  LEFT JOIN clientes c ON r.cliente_id = c.id
                  LEFT JOIN mesas m ON r.mesa_id = m.id
                  ORDER BY r.fecha_creacion DESC
                  LIMIT ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
