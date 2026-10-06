# 📂 Estructura Organizada del Proyecto

## ✅ Reorganización Completada

### 🏗️ **Estructura Principal MVC**

```
PRY_PROYECTO/
│
├── 📂 config/                    ⚙️ Configuración
│   ├── config.php               → Configuración global
│   └── database.php             → Singleton PDO
│
├── 📂 models/                    🗄️ Modelos (5 archivos)
│   ├── Mesa.php
│   ├── Cliente.php
│   ├── Reserva.php
│   ├── Plato.php
│   └── Categoria.php
│
├── 📂 controllers/               🎮 Controladores (4 archivos)
│   ├── AuthController.php
│   ├── MesaController.php
│   ├── ReservaController.php
│   └── MenuController.php
│
├── 📂 views/                     📺 Vistas
│   ├── pages/                   → (vacío - archivos activos en raíz)
│   ├── layouts/                 → Para futuro
│   └── components/              → Para futuro
│
├── 📂 assets/                    🎨 Assets organizados
│   ├── css/
│   ├── js/
│   ├── bootstrap/
│   └── images/
│
├── 📂 app/                       📡 Aplicación
│   ├── *.php                    → Endpoints legacy (18 archivos)
│   └── api/
│       ├── *_mvc.php            → APIs MVC (6 archivos)
│       └── *.php                → APIs legacy (9 archivos)
│
├── 📂 admin_panel/               👨‍💼 Panel Administrativo
│   ├── admin.php                → Panel principal admin
│   └── login_directo.php        → Login directo admin
│
├── 📂 tests/                     🧪 Tests y Backups
│   ├── test_*.php               → Tests del sistema (6 archivos)
│   ├── generar_hash.php         → Generador de passwords
│   └── mesas.php.backup         → Backup de mesas
│
├── 📂 docs/                      📚 Documentación
│   ├── MVC_ESTRUCTURA.md        → Documentación MVC completa
│   ├── ESTRUCTURA_VISUAL.txt    → Diagrama ASCII
│   ├── PRECIOS_MESAS_README.md  → Sistema de precios
│   ├── FORMATO_EXCEL_MENU.md    → Formato Excel
│   └── INSTRUCCIONES_INSTALACION.txt
│
├── 📂 scripts/                   🔧 Scripts de Utilidad
│   ├── install_dependencies.sh  → Instalación de dependencias
│   └── verificar_mvc.sh         → Verificación estructura MVC
│
├── 📂 conexion/                  🔗 Legacy (mantener)
│   └── db.php                   → Conexión original
│
├── 📂 public/                    📤 Públicos
│   ├── uploads/
│   └── logs/
│
├── 📂 storage/                   💾 Almacenamiento
│   └── logs/
│
├── 📂 utils/                     🛠️ Utilidades
│   └── imagen/
│
└── 📄 ARCHIVOS RAÍZ (ACTIVOS)
    ├── index.html               → ✅ Página principal activa
    ├── mesas.php                → ✅ Selección mesas activa
    └── registro.php             → ✅ Registro activo
```

---

## 🗑️ **Limpieza Realizada**

### ✅ Eliminados:
- ❌ `views/pages/admin.php` (duplicado, movido a admin_panel/)
- ❌ `views/pages/index.html` (duplicado)
- ❌ `views/pages/mesas.php` (duplicado)
- ❌ `views/pages/registro.php` (duplicado)
- ❌ `app/obtener_categorias.php` (archivo vacío)
- ❌ `app/obtener_platos.php` (archivo vacío)

### 📦 Organizados:
- ✅ Tests → `tests/` (7 archivos)
- ✅ Admin → `admin_panel/` (2 archivos)
- ✅ Docs → `docs/` (5 archivos)
- ✅ Scripts → `scripts/` (2 archivos)
- ✅ Backups → `tests/` (1 archivo)

---

## 🎯 **Archivos Activos (Raíz)**

Estos archivos **SIGUEN FUNCIONANDO** y son los que se usan actualmente:

| Archivo | Descripción | Ubicación |
|---------|-------------|-----------|
| `index.html` | 🏠 Página principal | Raíz (120KB) |
| `mesas.php` | 🪑 Selección de mesas | Raíz (20KB) |
| `registro.php` | ✍️ Registro de clientes | Raíz (5KB) |

---

## 🔌 **APIs Disponibles**

### **APIs MVC (Recomendadas)** - `app/api/`
```
✅ mesas_estado_mvc.php        → GET estado de mesas
✅ seleccionar_mesa_mvc.php    → POST seleccionar mesa
✅ obtener_menu_mvc.php        → GET menú completo
✅ login_cliente_mvc.php       → POST login cliente
✅ registro_cliente_mvc.php    → POST registro cliente
✅ crear_reserva_mvc.php       → POST crear reserva
```

### **APIs Legacy (Funcionando)** - `app/api/`
```
📜 mesas_estado.php           → Original
📜 seleccionar_mesa.php       → Original
📜 deseleccionar_mesa.php     → Original
📜 obtener_menu.php          → Original
📜 dashboard_stats.php       → Estadísticas
📜 reservas_recientes.php    → Reservas recientes
📜 subir_excel.php           → Upload Excel menú
📜 inspect_excel.php         → Inspeccionar Excel
📜 add_tiempo_preparacion.php → Tiempos preparación
```

---

## 👨‍💼 **Panel Administrativo**

**Ubicación:** `admin_panel/`

- **admin.php** - Panel principal de administración
  - Gestión de mesas
  - Gestión de reservas
  - Upload menú Excel
  - Estadísticas dashboard
  
- **login_directo.php** - Login rápido para admin

**Acceso:** `http://localhost/PRY_PROYECTO/admin_panel/admin.php`

---

## 🧪 **Tests Disponibles**

**Ubicación:** `tests/`

| Test | Descripción |
|------|-------------|
| `test_connection.php` | Prueba conexión BD |
| `test_db.php` | Prueba operaciones DB |
| `test_admin_login.php` | Prueba login admin |
| `test_password.php` | Prueba hashing passwords |
| `test_sistema.php` | Prueba sistema completo |
| `generar_hash.php` | Genera hash para passwords |

---

## 📊 **Estadísticas del Proyecto**

```
Total de archivos organizados: 50+
Models:           5
Controllers:      4
APIs MVC:         6
APIs Legacy:      9
Tests:            7
Docs:             5
Scripts:          2
Archivos activos: 3
```

---

## 🚀 **Cómo Usar**

### **1. Acceso Principal**
```
http://localhost/PRY_PROYECTO/index.html
```

### **2. Panel Admin**
```
http://localhost/PRY_PROYECTO/admin_panel/admin.php
```

### **3. APIs MVC**
```javascript
// Ejemplo: Obtener mesas
fetch('app/api/mesas_estado_mvc.php')
  .then(res => res.json())
  .then(data => console.log(data));
```

### **4. Verificar Estructura**
```bash
cd /opt/lampp/htdocs/PRY_PROYECTO
./scripts/verificar_mvc.sh
```

---

## ⚠️ **Importante**

- ✅ **Archivos raíz (index.html, mesas.php, registro.php):** SIGUEN FUNCIONANDO
- ✅ **APIs legacy:** Funcionan normalmente
- ✅ **APIs MVC:** Nuevas, coexisten con las legacy
- ✅ **Conexión DB:** `conexion/db.php` sigue funcionando
- ✅ **Sin duplicados:** Todos los archivos duplicados eliminados

---

## 🎉 **Resultado Final**

✅ Proyecto completamente organizado  
✅ Arquitectura MVC implementada  
✅ Sin archivos duplicados  
✅ Código legacy preservado  
✅ APIs nuevas disponibles  
✅ Todo funcionando correctamente  

---

**📅 Última actualización:** 16 de Noviembre, 2025  
**🏰 Proyecto:** Le Salon de Lumière - Restaurant Management System
