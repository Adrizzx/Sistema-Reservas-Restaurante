# Sistema de Auditoría - Documentación Completa

## 📋 Descripción General

El sistema de auditoría registra **TODAS** las acciones administrativas del sistema, proporcionando un historial completo y detallado de quién hizo qué cambio y cuándo.

## 🎯 Propósito

- **Responsabilidad**: Saber exactamente qué administrador realizó cada cambio
- **Trazabilidad**: Poder rastrear todos los cambios en el sistema
- **Diagnóstico**: Identificar problemas o revertir cambios
- **Cumplimiento**: Mantener registros para auditorías externas

---

## 🗄️ Estructura de Base de Datos

### 1. Tabla `auditoria_horarios`
Registra cambios en los horarios de atención del restaurante.

```sql
CREATE TABLE auditoria_horarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,                    -- ID del administrador
    admin_nombre VARCHAR(255),                -- Nombre completo del admin
    accion VARCHAR(100) DEFAULT 'CAMBIO_HORARIOS',
    fecha_cambio DATETIME DEFAULT CURRENT_TIMESTAMP,
    configuracion_anterior TEXT,              -- JSON con horarios anteriores
    configuracion_nueva TEXT,                 -- JSON con nuevos horarios
    reservas_afectadas INT DEFAULT 0,         -- Cantidad afectadas
    reservas_canceladas INT DEFAULT 0,        -- Cantidad canceladas
    notificaciones_enviadas INT DEFAULT 0,    -- WhatsApp enviados
    observaciones TEXT,                       -- Notas adicionales
    ip_address VARCHAR(45),                   -- IP desde donde se hizo
    user_agent TEXT,                          -- Navegador usado
    FOREIGN KEY (admin_id) REFERENCES usuarios(id)
);
```

**Campos JSON configuracion_anterior/nueva:**
```json
{
    "horario_inicio": "09:00",
    "horario_fin": "23:00",
    "dias_apertura": [1,2,3,4,5,6],
    "fecha_actualizacion": "2024-01-15 14:30:00"
}
```

### 2. Tabla `auditoria_reservas`
Registra todas las acciones sobre reservas individuales.

```sql
CREATE TABLE auditoria_reservas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reserva_id INT NOT NULL,                  -- ID de la reserva afectada
    admin_id INT,                             -- NULL si fue automático
    admin_nombre_completo VARCHAR(255),
    accion VARCHAR(50) NOT NULL,              -- CREAR, CONFIRMAR, CANCELAR, etc.
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado_anterior VARCHAR(20),
    estado_nuevo VARCHAR(20),
    datos_anteriores TEXT,                    -- JSON con datos previos
    datos_nuevos TEXT,                        -- JSON con datos actualizados
    motivo TEXT,                              -- Razón del cambio
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (reserva_id) REFERENCES reservas(id),
    FOREIGN KEY (admin_id) REFERENCES usuarios(id)
);
```

**Acciones registradas:**
- `CREAR`: Nueva reserva creada
- `CONFIRMAR`: Reserva confirmada por admin
- `CANCELAR`: Reserva cancelada
- `MODIFICAR`: Cambios en fecha/hora/mesa
- `COMPLETAR`: Reserva marcada como completada

### 3. Tabla `auditoria_sistema`
Registra acciones generales del sistema.

```sql
CREATE TABLE auditoria_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    admin_nombre VARCHAR(255),
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(50),                       -- MESAS, MENU, CLIENTES, etc.
    entidad_tipo VARCHAR(50),                 -- Mesa, Plato, Cliente
    entidad_id INT,                           -- ID del registro afectado
    descripcion TEXT,
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (admin_id) REFERENCES usuarios(id)
);
```

---

## 🎮 Controlador: `AuditoriaController.php`

### Ubicación
```
/controllers/AuditoriaController.php
```

### Métodos Principales

#### 1. Registrar Cambio de Horarios
```php
public function registrarCambioHorarios(
    $adminId,
    $adminNombre,
    $configAnterior,
    $configNueva,
    $reservasAfectadas = 0,
    $reservasCanceladas = 0,
    $notificacionesEnviadas = 0,
    $observaciones = null
)
```

**Ejemplo de uso:**
```php
$auditoria = new AuditoriaController($conn);
$auditoria->registrarCambioHorarios(
    $_SESSION['admin_id'],
    $_SESSION['admin_nombre'],
    json_encode($configuracionActual),
    json_encode($nuevaConfiguracion),
    5,  // reservas afectadas
    3,  // canceladas
    3,  // WhatsApp enviados
    "Cambio de emergencia por renovación"
);
```

#### 2. Registrar Acción sobre Reserva
```php
public function registrarAccionReserva(
    $reservaId,
    $accion,
    $estadoAnterior = null,
    $estadoNuevo = null,
    $adminId = null,
    $adminNombre = null,
    $datosAnteriores = null,
    $datosNuevos = null,
    $motivo = null
)
```

**Ejemplo de uso:**
```php
$auditoria->registrarAccionReserva(
    $reservaId,
    'CONFIRMAR',
    'pendiente',
    'confirmada',
    $_SESSION['admin_id'],
    $_SESSION['admin_nombre'],
    json_encode(['mesa' => 5, 'personas' => 4]),
    json_encode(['mesa' => 5, 'personas' => 4]),
    'Confirmación manual por admin'
);
```

#### 3. Obtener Historial de Horarios
```php
public function obtenerHistorialHorarios($limite = 50)
```

Retorna array con:
- ID del cambio
- Nombre del administrador
- Fecha del cambio
- Configuraciones antes/después (JSON)
- Impacto (reservas afectadas, canceladas, notificaciones)
- IP y User Agent

#### 4. Obtener Historial de una Reserva
```php
public function obtenerHistorialReserva($reservaId)
```

Retorna timeline completo de una reserva específica.

#### 5. Obtener Acciones de un Admin
```php
public function obtenerAccionesAdmin($adminId, $fechaInicio = null, $fechaFin = null)
```

Lista todas las acciones de un administrador en un periodo.

---

## 🔌 API: `/app/api/auditoria.php`

### Autenticación
Requiere sesión de administrador activa.

### Endpoints

#### 1. Resumen General
```
GET /app/api/auditoria.php?tipo=resumen
```

**Respuesta:**
```json
{
    "success": true,
    "tipo": "resumen",
    "resumen": {
        "total_cambios_horarios": 12,
        "total_acciones_reservas": 156,
        "total_reservas_canceladas": 8,
        "total_notificaciones": 23,
        "ultimos_cambios": [...],
        "admins_mas_activos": [...]
    }
}
```

#### 2. Historial de Horarios
```
GET /app/api/auditoria.php?tipo=horarios&limite=50
```

**Respuesta:**
```json
{
    "success": true,
    "tipo": "horarios",
    "total": 12,
    "datos": [
        {
            "id": 15,
            "admin": "Juan Pérez",
            "fecha": "15/01/2024 14:30:25",
            "reservas_afectadas": 5,
            "reservas_canceladas": 3,
            "notificaciones_enviadas": 3,
            "configuracion_anterior": "{...}",
            "configuracion_nueva": "{...}",
            "observaciones": "...",
            "ip": "192.168.1.100"
        }
    ]
}
```

#### 3. Obtener Detalles de un Cambio Específico
```
GET /app/api/auditoria.php?tipo=horarios&id=15
```

Retorna un solo registro con todos los detalles.

#### 4. Historial de una Reserva
```
GET /app/api/auditoria.php?tipo=reserva&reserva_id=123
```

#### 5. Acciones de un Administrador
```
GET /app/api/auditoria.php?tipo=admin&admin_id=5
GET /app/api/auditoria.php?tipo=admin  (admin actual)
```

---

## 🖥️ Interfaz Web: `/views/auditoria.php`

### Acceso
Desde el dashboard de admin: **Menú lateral → Auditoría**

### Características

#### Panel de Resumen
- **Total de cambios de horarios**
- **Total de acciones en reservas**
- **Reservas canceladas**
- **Notificaciones WhatsApp enviadas**

#### Filtros
- **Tipo de auditoría**: Horarios, Mis Acciones, Resumen General
- **Límite de registros**: 25, 50, 100, 200
- **Botón Exportar CSV** (preparado para implementar)

#### Tarjetas de Auditoría
Cada cambio se muestra en una tarjeta con:
- **Icono y título** según tipo de acción
- **Badge con nombre del admin**
- **Fecha y hora del cambio**
- **IP desde donde se realizó**
- **Indicadores de impacto** (reservas canceladas, WhatsApp enviados)
- **Botón "Ver Detalles"** que muestra modal con:
  - Comparación antes/después en JSON
  - Administrador responsable
  - Impacto total
  - Observaciones

#### Códigos de Color
- **Azul claro**: Cambios de horarios
- **Verde**: Acciones en reservas
- **Rojo**: Cancelaciones

---

## 🔗 Integración en el Sistema

### 1. En `app/api/gestionar_horarios.php`
```php
// Al final del proceso de cambio de horarios
require_once '../../controllers/AuditoriaController.php';
$auditoria = new AuditoriaController($conn);

$auditoria->registrarCambioHorarios(
    $_SESSION['admin_id'],
    $_SESSION['admin_nombre'],
    json_encode($configuracionAnterior),
    json_encode($nuevaConfiguracion),
    count($reservasAfectadas),
    $reservasCanceladas,
    $notificacionesEnviadas,
    $observaciones
);
```

### 2. En `app/api/confirmar_reserva_admin.php`
```php
require_once '../../controllers/AuditoriaController.php';
$auditoria = new AuditoriaController($conn);

$auditoria->registrarAccionReserva(
    $reservaId,
    'CONFIRMAR',
    'pendiente',
    'confirmada',
    $_SESSION['admin_id'],
    $_SESSION['admin_nombre'],
    null,
    null,
    'Confirmación desde dashboard admin'
);
```

---

## 📊 Casos de Uso

### Caso 1: Restaurante cambia horario de cierre
**Situación**: Admin modifica horario de cierre de 23:00 a 21:00

**Qué se registra:**
1. En `auditoria_horarios`:
   - Admin que hizo el cambio
   - Hora anterior: 23:00
   - Hora nueva: 21:00
   - 5 reservas afectadas
   - 3 canceladas automáticamente
   - 3 WhatsApp enviados

2. En `auditoria_reservas` (por cada reserva cancelada):
   - Reserva ID
   - Admin: NULL (automático)
   - Acción: CANCELAR
   - Estado: confirmada → cancelada
   - Motivo: "Su reserva fue cancelada automáticamente debido a cambio en horarios de atención"

**Resultado**: Se puede ver exactamente quién cambió los horarios, cuándo, qué reservas se afectaron y cuántos clientes fueron notificados.

### Caso 2: Admin confirma una reserva nueva
**Situación**: Llega reserva nueva, admin la confirma

**Qué se registra:**
1. En `auditoria_reservas`:
   - Reserva ID
   - Admin que confirmó
   - Acción: CONFIRMAR
   - Estado: pendiente → confirmada
   - Fecha/hora de acción
   - IP del admin

**Resultado**: Queda constancia de quién confirmó la reserva manualmente.

### Caso 3: Investigar problema
**Situación**: Cliente dice que su reserva fue cancelada sin previo aviso

**Cómo investigar:**
1. Ir a **Auditoría** en el dashboard
2. Filtrar por "Mis Acciones" o buscar en historial de horarios
3. Ver si hubo cambio de horarios ese día
4. Verificar en los detalles:
   - ¿Qué admin hizo el cambio?
   - ¿Cuántas reservas se cancelaron?
   - ¿Se enviaron las notificaciones WhatsApp?
5. Si hay error, ver IP y User Agent para contexto adicional

---

## 🔐 Seguridad

### Datos Capturados
- **IP Address**: Para rastrear desde dónde se hizo el cambio
- **User Agent**: Navegador y dispositivo usado
- **Timestamp exacto**: Fecha y hora precisa
- **Admin ID + Nombre**: Responsable del cambio

### Protección de Datos
- Los registros de auditoría **NO SE PUEDEN BORRAR** desde la interfaz
- Solo administradores pueden ver la auditoría
- Los datos JSON están codificados para prevenir inyección

### Integridad
- Llaves foráneas con `ON DELETE SET NULL` para conservar el registro aunque se elimine el admin
- Timestamps automáticos con `DEFAULT CURRENT_TIMESTAMP`

---

## 🚀 Próximas Mejoras

### Planificadas
- [ ] Exportación a CSV/Excel
- [ ] Filtro por rango de fechas
- [ ] Búsqueda por palabra clave
- [ ] Comparación visual de JSON (diff colorizado)
- [ ] Restaurar configuración anterior (rollback)
- [ ] Alertas automáticas por cambios críticos
- [ ] Dashboard de analíticas de auditoría

### Posibles Extensiones
- Auditar cambios en menú
- Auditar cambios en mesas
- Auditar acciones de clientes
- Integración con sistema de respaldos

---

## 📝 Notas Importantes

1. **Rendimiento**: Las tablas tienen índices en `admin_id`, `fecha_cambio` y `accion` para consultas rápidas

2. **Almacenamiento**: Los datos JSON se guardan como TEXT para máxima flexibilidad

3. **Mantenimiento**: Considerar script de limpieza automática de registros antiguos (>1 año)

4. **Cumplimiento**: Este sistema cumple con requisitos básicos de trazabilidad para auditorías

---

## ❓ Preguntas Frecuentes

**P: ¿Puedo borrar registros de auditoría?**
R: No desde la interfaz. Solo con acceso directo a la base de datos.

**P: ¿Qué pasa si cambio horarios pero no afecta reservas?**
R: Se registra igual con `reservas_afectadas = 0`.

**P: ¿Se auditan acciones automáticas del sistema?**
R: Sí, con `admin_id = NULL` y acción = 'SISTEMA'.

**P: ¿Cuánto espacio ocupa?**
R: Aproximadamente 1-2 KB por registro. Con 1000 registros = ~1-2 MB.

---

## 🆘 Soporte

Para problemas o dudas sobre el sistema de auditoría, verificar:
1. Sesión de admin activa
2. Permisos de base de datos
3. Logs en `/storage/logs/`
4. Consola del navegador (F12)

