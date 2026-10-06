<?php
/**
 * Controller de Menú
 * Gestión de categorías y platos
 */

require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Plato.php';

class MenuController {
    private $categoriaModel;
    private $platoModel;
    
    public function __construct() {
        $this->categoriaModel = new Categoria();
        $this->platoModel = new Plato();
    }
    
    /**
     * Obtener menú completo con categorías y platos
     */
    public function getMenuCompleto() {
        return $this->categoriaModel->getMenuCompleto();
    }
    
    /**
     * Obtener todas las categorías
     */
    public function getCategorias() {
        return $this->categoriaModel->getActivas();
    }
    
    /**
     * Obtener platos por categoría
     */
    public function getPlatosPorCategoria($categoria_id) {
        return $this->platoModel->getByCategoria($categoria_id);
    }
    
    /**
     * Obtener plato por ID
     */
    public function getPlato($id) {
        return $this->platoModel->getById($id);
    }
}
?>
