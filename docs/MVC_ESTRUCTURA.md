# 🏗️ Arquitectura MVC - Le Salon de Lumière

## 📁 Nueva Estructura del Proyecto

```
PRY_PROYECTO/
│
├── 📂 config/                    # Configuración
│   ├── config.php               # Constantes y configuración general
│   └── database.php             # Singleton de conexión PDO
│
├── 📂 models/                    # Modelos (Entidades de BD)
│   ├── Mesa.php                 # Modelo de Mesas
│   ├── Cliente.php              # Modelo de Clientes
│   ├── Reserva.php              # Modelo de Reservas
│   ├── Plato.php                # Modelo de Platos
│   └── Categoria.php            # Modelo de Categorías
│
├── 📂 controllers/               # Controladores (Lógica de Negocio)
│   ├── AuthController.php       # Login/Registro
│   ├── MesaController.php       # CRUD y gestión de mesas
│   ├── ReservaController.php    # CRUD de reservas
│   └── MenuController.php       # Menú gastronómico
│
├── 📂 views/                     # Vistas (Interfaz de Usuario)
│   ├── pages/                   # Páginas principales
│   │   ├── index.html           # Página principal
│   │   ├── mesas.php            # Selección de mesas
│   │   ├── admin.php            # Panel administrativo
│   │   └── registro.php         # Registro de clientes
│   ├── layouts/                 # Plantillas (navbar, footer)
│   └── components/              # Componentes reutilizables
│
├── 📂 assets/                    # Assets organizados
│   ├── css/                     # Estilos CSS
│   ├── js/                      # JavaScript
│   ├── bootstrap/               # Framework Bootstrap
│   └── images/                  # Imágenes
│
├── 📂 app/api/                   # APIs REST
│   ├── *_mvc.php                # Nuevas APIs con MVC
│   └── *.php                    # APIs originales (legacy)
│
├── 📂 public/                    # Archivos públicos
│   ├── uploads/                 # Archivos subidos
│   └── logs/                    # Logs del sistema
│
└── 📂 conexion/                  # Legacy (mantener por compatibilidad)
    └── db.php                   # Conexión original
```

---

## 🎯 Patrón MVC Implementado

### **M - Model (Modelo)**
**Ubicación:** `/models/`

Los modelos representan las entidades de la base de datos y contienen toda la lógica de acceso a datos.

**Ejemplo: Mesa.php**
```php
require_once __DIR__ . '/../config/database.php';

class Mesa {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll() { /* ... */ }
    public function getById($id) { /* ... */ }
    public function create($data) { /* ... */ }
    // ...
}
```

**Modelos Disponibles:**
- ✅ `Mesa.php` - Gestión de mesas
- ✅ `Cliente.php` - Gestión de clientes (con validación de passwords)
- ✅ `Reserva.php` - Reservas con relaciones
- ✅ `Plato.php` - Platos del menú
- ✅ `Categoria.php` - Categorías de platos

---

### **V - View (Vista)**
**Ubicación:** `/views/pages/`

Las vistas contienen solo el código de presentación (HTML/PHP para mostrar datos).

**Archivos de Vista:**
- 📄 `index.html` - Página principal con galería de mesas y menú
- 📄 `mesas.php` - Interfaz de selección de mesas
- 📄 `admin.php` - Panel de administración
- 📄 `registro.php` - Formulario de registro

---

### **C - Controller (Controlador)**
**Ubicación:** `/controllers/`

Los controladores contienen la lógica de negocio y coordinan entre modelos y vistas.

**Ejemplo: MesaController.php**
```php
require_once __DIR__ . '/../models/Mesa.php';

class MesaController {
    private $mesaModel;
    
    public function __construct() {
        $this->mesaModel = new Mesa();
    }
    
    public function getEstadoMesas() {
        return $this->mesaModel->getEstadoMesas();
    }
    
    public function seleccionarMesa($mesa_id) {
        // Validaciones + lógica de negocio
        $mesa = $this->mesaModel->getById($mesa_id);
        
        if ($mesa['estado'] !== 'disponible') {
            return ['success' => false, 'message' => 'Mesa no disponible'];
        }
        
        $_SESSION['mesa_seleccionada_id'] = $mesa_id;
        return ['success' => true, 'mesa' => $mesa];
    }
}
```

**Controladores Disponibles:**
- ✅ `AuthController.php` - Autenticación (login/registro/logout)
- ✅ `MesaController.php` - Gestión completa de mesas
- ✅ `ReservaController.php` - Creación y gestión de reservas
- ✅ `MenuController.php` - Menú gastronómico

---

## 🔌 Nuevas APIs MVC

Las nuevas APIs en `/app/api/*_mvc.php` utilizan los controladores:

| API | Controlador | Descripción |
|-----|-------------|-------------|
| `mesas_estado_mvc.php` | MesaController | Estado de todas las mesas |
| `seleccionar_mesa_mvc.php` | MesaController | Seleccionar mesa en sesión |
| `obtener_menu_mvc.php` | MenuController | Menú completo con categorías |
| `login_cliente_mvc.php` | AuthController | Login de clientes |
| `registro_cliente_mvc.php` | AuthController | Registro de clientes |
| `crear_reserva_mvc.php` | ReservaController | Crear nueva reserva |

---

## ⚙️ Configuración

### **config/config.php**
Constantes globales de la aplicación:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'crud_proyecto');
define('APP_NAME', 'Le Salon de Lumière');
define('BASE_PATH', __DIR__ . '/..');
```

### **config/database.php**
Singleton para conexión PDO reutilizable:
```php
$pdo = Database::getInstance()->getConnection();
```

---

## 🚀 Cómo Usar la Estructura MVC

### **1. Crear nuevo endpoint API**

```php
// app/api/mi_endpoint.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../controllers/MesaController.php';

$mesaController = new MesaController();
$resultado = $mesaController->getDisponibles();

echo json_encode(['success' => true, 'data' => $resultado]);
```

### **2. Agregar método a Controller**

```php
// controllers/MesaController.php
public function getMesasPorUbicacion($ubicacion) {
    return $this->mesaModel->getByUbicacion($ubicacion);
}
```

### **3. Agregar método a Model**

```php
// models/Mesa.php
public function getByUbicacion($ubicacion) {
    $query = "SELECT * FROM mesas WHERE ubicacion = ?";
    $stmt = $this->db->prepare($query);
    $stmt->execute([$ubicacion]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

---

## 🔄 Migración Gradual

**Archivos Originales:** Se mantienen funcionando en la raíz
**Archivos MVC:** Coexisten en las nuevas carpetas

### **Compatibilidad:**
- ✅ APIs originales siguen funcionando (`app/api/*.php`)
- ✅ APIs MVC disponibles con sufijo `_mvc.php`
- ✅ `conexion/db.php` mantiene compatibilidad
- ✅ Archivos raíz siguen accesibles

### **Ventajas MVC:**
- 🎯 Código organizado y mantenible
- 🔒 Lógica de negocio centralizada
- ♻️ Reutilización de código
- 🧪 Facilita testing
- 👥 Trabajo en equipo más eficiente

---

## 📊 Flujo de una Petición MVC

```
Cliente (Frontend)
    ↓
    📡 AJAX Request (fetch)
    ↓
API Endpoint (app/api/mesas_estado_mvc.php)
    ↓
    🎮 Controller (MesaController)
        ↓
        🗄️ Model (Mesa.php)
            ↓
            💾 Database (MySQL)
            ↑
        Model retorna datos
        ↑
    Controller procesa/valida
    ↑
API retorna JSON
    ↑
Cliente recibe respuesta
```

---

## ✅ Completado

- ✅ Estructura de carpetas MVC
- ✅ 5 Models completos (Mesa, Cliente, Reserva, Plato, Categoria)
- ✅ 4 Controllers (Auth, Mesa, Reserva, Menu)
- ✅ Configuración centralizada
- ✅ Database Singleton
- ✅ 6 APIs MVC funcionales
- ✅ Assets reorganizados
- ✅ Views copiadas

---

## 🔮 Próximos Pasos (Opcionales)

1. **Router:** Crear sistema de routing centralizado
2. **Middleware:** Autenticación y validaciones
3. **Templates:** Sistema de plantillas PHP
4. **Validaciones:** Clase Validator reutilizable
5. **Logging:** Sistema de logs estructurado
6. **Testing:** PHPUnit para tests unitarios

---

**📅 Última actualización:** 16 de Noviembre, 2025  
**🏰 Proyecto:** Le Salon de Lumière - Restaurant Management System
