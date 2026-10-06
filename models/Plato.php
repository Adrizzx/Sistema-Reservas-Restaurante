<?php
/**
 * Modelo Plato
 * Gestión de platos del menú
 */

require_once __DIR__ . '/../config/database.php';

class Plato {
    private $db;
    private $table = 'platos';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todos los platos
     */
    public function getAll() {
        $query = "SELECT p.*, c.nombre as categoria_nombre
                  FROM {$this->table} p
                  LEFT JOIN categorias_platos c ON p.categoria_id = c.id
                  ORDER BY c.orden_menu ASC, p.nombre ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener platos activos
     */
    public function getActivos() {
        $query = "SELECT p.*, c.nombre as categoria_nombre
                  FROM {$this->table} p
                  LEFT JOIN categorias_platos c ON p.categoria_id = c.id
                  WHERE p.activo = 1
                  ORDER BY c.orden_menu ASC, p.nombre ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener platos por categoría
     */
    public function getByCategoria($categoria_id) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE categoria_id = ? AND activo = 1
                  ORDER BY nombre ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$categoria_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener plato por ID
     */
    public function getById($id) {
        $query = "SELECT p.*, c.nombre as categoria_nombre
                  FROM {$this->table} p
                  LEFT JOIN categorias_platos c ON p.categoria_id = c.id
                  WHERE p.id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crear nuevo plato
     */
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (categoria_id, nombre, descripcion, precio, stock_disponible, imagen_url, ingredientes, activo) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            $data['categoria_id'],
            $data['nombre'],
            $data['descripcion'] ?? '',
            $data['precio'],
            $data['stock_disponible'] ?? 100,
            $data['imagen_url'] ?? '',
            $data['ingredientes'] ?? '',
            $data['activo'] ?? 1
        ]);
    }
    
    /**
     * Actualizar plato
     */
    public function update($id, $data) {
        $query = "UPDATE {$this->table} 
                  SET categoria_id = ?, nombre = ?, descripcion = ?, precio = ?, 
                      stock_disponible = ?, imagen_url = ?, ingredientes = ?, activo = ?
                  WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            $data['categoria_id'],
            $data['nombre'],
            $data['descripcion'] ?? '',
            $data['precio'],
            $data['stock_disponible'] ?? 100,
            $data['imagen_url'] ?? '',
            $data['ingredientes'] ?? '',
            $data['activo'] ?? 1,
            $id
        ]);
    }
    
    /**
     * Eliminar plato
     */
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Cambiar estado activo
     */
    public function cambiarEstado($id, $activo) {
        $query = "UPDATE {$this->table} SET activo = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$activo, $id]);
    }
}
?>
