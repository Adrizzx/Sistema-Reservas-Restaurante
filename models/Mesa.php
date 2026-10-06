<?php
/**
 * Modelo Mesa
 * Gestión de mesas del restaurante
 */

require_once __DIR__ . '/../config/database.php';

class Mesa {
    private $db;
    private $table = 'mesas';
    
    public $id;
    public $numero_mesa;
    public $capacidad_minima;
    public $capacidad_maxima;
    public $precio_reserva;
    public $ubicacion;
    public $estado;
    public $descripcion;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todas las mesas
     */
    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY numero_mesa ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener mesas por estado
     */
    public function getByEstado($estado) {
        $query = "SELECT * FROM {$this->table} WHERE estado = ? ORDER BY numero_mesa ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$estado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener mesa por ID
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener mesas disponibles
     */
    public function getDisponibles() {
        return $this->getByEstado('disponible');
    }
    
    /**
     * Crear nueva mesa
     */
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (numero_mesa, capacidad_minima, capacidad_maxima, ubicacion, descripcion, estado) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            $data['numero_mesa'],
            $data['capacidad_minima'],
            $data['capacidad_maxima'],
            $data['ubicacion'],
            $data['descripcion'] ?? '',
            $data['estado'] ?? 'disponible'
        ]);
    }
    
    /**
     * Actualizar mesa
     */
    public function update($id, $data) {
        $query = "UPDATE {$this->table} 
                  SET numero_mesa = ?, capacidad_minima = ?, capacidad_maxima = ?, 
                      ubicacion = ?, descripcion = ?, estado = ?
                  WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            $data['numero_mesa'],
            $data['capacidad_minima'],
            $data['capacidad_maxima'],
            $data['ubicacion'],
            $data['descripcion'] ?? '',
            $data['estado'] ?? 'disponible',
            $id
        ]);
    }
    
    /**
     * Eliminar mesa
     */
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Cambiar estado de mesa
     */
    public function cambiarEstado($id, $estado) {
        $query = "UPDATE {$this->table} SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$estado, $id]);
    }
    
    /**
     * Obtener estado de todas las mesas (para visualización)
     */
    public function getEstadoMesas() {
        $query = "SELECT id, numero_mesa, capacidad_minima, capacidad_maxima, 
                         precio_reserva, ubicacion, estado, descripcion 
                  FROM {$this->table} 
                  ORDER BY numero_mesa ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear para frontend
        $resultado = [];
        foreach ($mesas as $mesa) {
            $resultado[] = [
                'id' => $mesa['id'],
                'numero' => $mesa['numero_mesa'],
                'capacidad_minima' => $mesa['capacidad_minima'],
                'capacidad_maxima' => $mesa['capacidad_maxima'],
                'precio_reserva' => $mesa['precio_reserva'],
                'ubicacion' => $mesa['ubicacion'],
                'estado' => $mesa['estado'],
                'descripcion' => $mesa['descripcion']
            ];
        }
        
        return $resultado;
    }
}
?>
