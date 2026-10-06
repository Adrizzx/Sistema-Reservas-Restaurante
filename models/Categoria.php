<?php
/**
 * Modelo Categoria
 * Gestión de categorías de platos
 */

require_once __DIR__ . '/../config/database.php';

class Categoria {
    private $db;
    private $table = 'categorias_platos';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtener todas las categorías
     */
    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY orden_menu ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener categorías activas
     */
    public function getActivas() {
        $query = "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY orden_menu ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener categoría por ID
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtener menú completo con platos
     */
    public function getMenuCompleto() {
        $categorias = $this->getActivas();
        
        $platoModel = new Plato();
        
        foreach ($categorias as &$categoria) {
            $categoria['platos'] = $platoModel->getByCategoria($categoria['id']);
        }
        
        return $categorias;
    }
    
    /**
     * Crear nueva categoría
     */
    public function create($data) {
        $query = "INSERT INTO {$this->table} 
                  (nombre, descripcion, orden_menu, activo) 
                  VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? '',
            $data['orden_menu'] ?? 0,
            $data['activo'] ?? 1
        ]);
    }
    
    /**
     * Actualizar categoría
     */
    public function update($id, $data) {
        $query = "UPDATE {$this->table} 
                  SET nombre = ?, descripcion = ?, orden_menu = ?, activo = ?
                  WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? '',
            $data['orden_menu'] ?? 0,
            $data['activo'] ?? 1,
            $id
        ]);
    }
    
    /**
     * Eliminar categoría
     */
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }
}

// Incluir Plato para relaciones
require_once __DIR__ . '/Plato.php';
?>
