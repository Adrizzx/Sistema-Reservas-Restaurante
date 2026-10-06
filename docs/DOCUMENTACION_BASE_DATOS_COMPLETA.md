|p# 📋 DOCUMENTACIÓN COMPLETA DE LA BASE DE DATOS
## Sistema de Restaurante "Le Salon de Lumière"

---

## 📊 DIAGRAMA ENTIDAD-RELACIÓN (ER)

### Estructura General del Sistema

```
┌─────────────────────────────────────────────────────────────────────┐
│                     SISTEMA CRUD_PROYECTO                           │
│                   Le Salon de Lumière Restaurant                     │
└─────────────────────────────────────────────────────────────────────┘

[administradores] ──────┐
    │ id (PK)           │
    │ usuario (UK)      │
    │ password          │
    │ rol               │
    │ email             │
    └───────────────────┘
            │
            │ (registra cambios en)
            ↓
    [historial_estados]
         │ id (PK)
         │ tabla_referencia
         │ registro_id
         │ estado_anterior
         │ estado_nuevo
         │ usuario_id (FK)
         └───────────────


[clientes] ──────────────────────┐
    │ id (PK)                     │
    │ nombre, apellido            │
    │ cedula (UK)                 │
    │ telefono                    │
    │ usuario (UK)                │
    │ email (UK)                  │
    └─────────────────────────────┘
            │
            │ (realiza)
            ↓
    [reservas] ←──────────────────┐
         │ id (PK)                │
         │ cliente_id (FK) ───────┘
         │ mesa_id (FK) ──┐
         │ fecha_reserva   │
         │ hora_reserva    │
         │ estado          │
         └─────────────────┘
                │          │
                │          └──────────────────┐
                │                             │
                ↓                             ↓
    [pre_pedidos]                      [mesas]
         │ id (PK)                         │ id (PK)
         │ reserva_id (FK)                 │ numero_mesa (UK)
         │ plato_id (FK) ──┐               │ capacidad_min/max
         │ cantidad         │               │ precio_reserva
         │ precio_unitario  │               │ ubicacion
         │ subtotal         │               │ estado
         └──────────────────┘               └─────────────
                │
                ↓
    [platos] ────────────────┐
         │ id (PK)           │
         │ categoria_id (FK) │
         │ nombre            │
         │ precio            │
         │ stock             │
         │ tiempo_prep       │
         └───────────────────┘
                │
                ↓
    [categorias_platos]
         │ id (PK)
         │ nombre (UK)
         │ descripcion
         │ orden_menu
         └─────────────

    [reservas] ──────────────────┐
         │                       │
         │ (genera)              │
         ↓                       │
    [notas_consumo]              │
         │ id (PK)               │
         │ reserva_id (FK) ──────┘
         │ numero_nota (UK)
         │ subtotal
         │ impuesto
         │ total
         │ estado
         │ metodo_pago
         └───────────────

    [reservas] ──────────────────┐
         │                       │
         │ (notifica por)        │
         ↓                       │
    [notificaciones_whatsapp]    │
         │ id (PK)               │
         │ reserva_id (FK) ──────┘
         │ telefono
         │ mensaje
         │ estado
         │ sid_twilio
         └───────────────

    [configuracion_restaurante]
         │ id (PK)
         │ clave (UK)
         │ valor
         │ descripcion
         └───────────────
```

---

## 🗂️ DESCRIPCIÓN DE TABLAS

### 1. **administradores**
Gestiona los usuarios con acceso al panel de administración.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `usuario` | VARCHAR(50) UNIQUE | Nombre de usuario para login |
| `password` | VARCHAR(255) | Contraseña (hash recomendado) |
| `nombre` | VARCHAR(100) | Nombre del administrador |
| `apellido` | VARCHAR(100) | Apellido del administrador |
| `email` | VARCHAR(100) UNIQUE | Correo electrónico |
| `rol` | ENUM | Valores: 'admin', 'manager', 'cajero' |
| `activo` | TINYINT(1) | 1=activo, 0=inactivo |
| `ultimo_acceso` | TIMESTAMP | Última vez que inició sesión |
| `fecha_creacion` | TIMESTAMP | Fecha de creación del usuario |

**Relaciones:**
- Registra cambios en `historial_estados` (opcional)

---

### 2. **clientes**
Almacena la información de los clientes registrados.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `nombre` | VARCHAR(100) | Nombre del cliente |
| `apellido` | VARCHAR(100) | Apellido del cliente |
| `cedula` | VARCHAR(20) UNIQUE | Cédula de identidad |
| `telefono` | VARCHAR(20) | Número de teléfono (para WhatsApp) |
| `ciudad` | VARCHAR(100) | Ciudad de residencia |
| `usuario` | VARCHAR(50) UNIQUE | Nombre de usuario |
| `password_hash` | VARCHAR(255) | Contraseña hasheada |
| `email` | VARCHAR(100) UNIQUE | Correo electrónico |

**Relaciones:**
- 1:N con `reservas` (un cliente puede tener muchas reservas)

---

### 3. **categorias_platos**
Categorías del menú para organizar los platos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `nombre` | VARCHAR(50) UNIQUE | Nombre de la categoría |
| `descripcion` | TEXT | Descripción de la categoría |
| `orden_menu` | INT | Orden de aparición en el menú |
| `activo` | TINYINT(1) | 1=activa, 0=inactiva |

**Categorías predefinidas:**
1. Entradas
2. Platos Principales
3. Carnes
4. Mariscos
5. Postres
6. Bebidas

**Relaciones:**
- 1:N con `platos`

---

### 4. **mesas**
Gestión de las mesas del restaurante.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `numero_mesa` | VARCHAR(10) UNIQUE | Código de la mesa (ej: M01, T01, V01) |
| `capacidad_minima` | INT | Número mínimo de personas |
| `capacidad_maxima` | INT | Número máximo de personas |
| `precio_reserva` | DECIMAL(10,2) | Precio automático según capacidad |
| `ubicacion` | ENUM | 'interior', 'terraza', 'vip', 'bar' |
| `estado` | ENUM | 'disponible', 'ocupada', 'reservada', 'mantenimiento' |
| `descripcion` | TEXT | Descripción de la mesa |
| `fecha_creacion` | TIMESTAMP | Fecha de creación |
| `fecha_actualizacion` | TIMESTAMP | Última modificación |

**Precios automáticos por capacidad:**
- 1-2 personas: $5.00
- 3-4 personas: $6.00
- 5-6 personas: $8.00
- 7-10 personas: $10.00
- Más de 10: $15.00

**Relaciones:**
- 1:N con `reservas`

**Triggers asociados:**
- `actualizar_precio_mesa_before_insert`: Calcula precio al crear mesa
- `actualizar_precio_mesa_before_update`: Recalcula si cambia capacidad
- `tr_mesas_cambio_estado`: Registra cambios de estado

---

### 5. **reservas**
Registro de reservas de mesas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `cliente_id` | INT (FK) | Referencia a `clientes` |
| `mesa_id` | INT (FK) | Referencia a `mesas` |
| `fecha_reserva` | DATE | Fecha de la reserva |
| `hora_reserva` | TIME | Hora de la reserva |
| `numero_personas` | INT | Cantidad de comensales |
| `estado` | ENUM | 'pendiente', 'confirmada', 'en_curso', 'finalizada', 'cancelada' |

**Estados del ciclo de vida:**
1. **pendiente**: Reserva creada, esperando confirmación
2. **confirmada**: Reserva confirmada por el sistema
3. **en_curso**: Cliente ya llegó, está en la mesa
4. **finalizada**: Servicio completado
5. **cancelada**: Reserva cancelada

**Relaciones:**
- N:1 con `clientes`
- N:1 con `mesas`
- 1:N con `pre_pedidos`
- 1:1 con `notas_consumo`
- 1:N con `notificaciones_whatsapp`

**Triggers asociados:**
- `tr_reservas_cambio_estado`: Registra cambios de estado

**Procedimientos asociados:**
- `activar_reservas_programadas()`: Actualiza estados automáticamente

---

### 6. **platos**
Menú de platos disponibles.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `categoria_id` | INT (FK) | Referencia a `categorias_platos` |
| `nombre` | VARCHAR(100) | Nombre del plato |
| `descripcion` | TEXT | Descripción del plato |
| `precio` | DECIMAL(10,2) | Precio unitario |
| `stock_disponible` | INT | Cantidad disponible |
| `imagen_url` | VARCHAR(255) | URL de la imagen |
| `activo` | TINYINT(1) | 1=disponible, 0=no disponible |
| `tiempo_preparacion` | INT | Tiempo en minutos |
| `fecha_creacion` | TIMESTAMP | Fecha de creación |

**Relaciones:**
- N:1 con `categorias_platos`
- 1:N con `pre_pedidos`

---

### 7. **pre_pedidos**
Platos pedidos anticipadamente con la reserva.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `reserva_id` | INT (FK) | Referencia a `reservas` |
| `plato_id` | INT (FK) | Referencia a `platos` |
| `cantidad` | INT | Cantidad solicitada |
| `precio_unitario` | DECIMAL(10,2) | Precio al momento del pedido |
| `subtotal` | DECIMAL(10,2) | cantidad × precio_unitario |
| `observaciones` | TEXT | Notas especiales |
| `fecha_pedido` | TIMESTAMP | Momento del pedido |

**Relaciones:**
- N:1 con `reservas` (CASCADE DELETE)
- N:1 con `platos`

---

### 8. **notas_consumo**
Facturas generadas por cada reserva.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `reserva_id` | INT (FK) | Referencia a `reservas` |
| `numero_nota` | VARCHAR(20) UNIQUE | Número de factura |
| `subtotal` | DECIMAL(10,2) | Suma de platos + mesa |
| `impuesto` | DECIMAL(10,2) | Impuestos aplicados |
| `descuento` | DECIMAL(10,2) | Descuentos aplicados |
| `total` | DECIMAL(10,2) | Total a pagar |
| `estado` | ENUM | 'borrador', 'finalizada', 'pagada', 'anulada' |
| `metodo_pago` | ENUM | 'efectivo', 'tarjeta', 'transferencia', 'mixto' |
| `fecha_generacion` | TIMESTAMP | Fecha de creación |
| `fecha_pago` | TIMESTAMP | Fecha de pago |
| `observaciones` | TEXT | Notas adicionales |

**Relaciones:**
- N:1 con `reservas`

---

### 9. **historial_estados**
Auditoría de cambios de estado (trazabilidad).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `tabla_referencia` | ENUM | 'reservas', 'mesas', 'notas_consumo' |
| `registro_id` | INT | ID del registro modificado |
| `estado_anterior` | VARCHAR(50) | Estado antes del cambio |
| `estado_nuevo` | VARCHAR(50) | Estado después del cambio |
| `usuario_id` | INT (FK) NULL | Administrador que hizo el cambio |
| `motivo` | TEXT | Razón del cambio (opcional) |
| `fecha_cambio` | TIMESTAMP | Momento del cambio |

**Relaciones:**
- N:1 con `administradores` (SET NULL)
- Registros de `mesas`, `reservas`, `notas_consumo`

---

### 10. **configuracion_restaurante**
Configuración dinámica del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `clave` | VARCHAR(100) UNIQUE | Nombre de la configuración |
| `valor` | TEXT | Valor de la configuración |
| `descripcion` | TEXT | Descripción de qué hace |
| `fecha_actualizacion` | TIMESTAMP | Última modificación |

**Configuraciones incluidas:**
- `horario_lunes_viernes_inicio/fin`: Horario semanal
- `horario_sabado_inicio/fin`: Horario sábados
- `horario_domingo_inicio/fin`: Horario domingos
- `dias_cerrado`: Fechas cerradas (ej: 25-12,01-01)
- `reservas_activas`: Habilitar/deshabilitar reservas
- `hora_apertura/cierre`: Horarios generales
- `duracion_reserva`: Minutos por reserva
- `intervalo_reservas`: Tiempo entre reservas

---

### 11. **notificaciones_whatsapp**
Registro de mensajes WhatsApp enviados (integración con Twilio).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT (PK) | Identificador único |
| `reserva_id` | INT (FK) | Referencia a `reservas` |
| `telefono` | VARCHAR(20) | Número del destinatario |
| `mensaje` | TEXT | Contenido del mensaje |
| `estado` | ENUM | 'enviado', 'fallido', 'pendiente' |
| `sid_twilio` | VARCHAR(100) | ID del mensaje en Twilio |
| `error_mensaje` | TEXT | Mensaje de error si falló |
| `fecha_envio` | TIMESTAMP | Momento del envío |

**Relaciones:**
- N:1 con `reservas` (CASCADE DELETE)

**Uso:** Cada vez que se crea una reserva, se envía un WhatsApp automático con:
- Número de nota de consumo
- Fecha y hora de reserva
- Mesa asignada
- Número de personas
- Detalle de platos (si aplica)
- Total a pagar

---

## 🔗 RELACIONES ENTRE TABLAS

### Cardinalidad

```
clientes (1) ──────< (N) reservas
mesas (1) ──────< (N) reservas
reservas (1) ──────< (N) pre_pedidos
reservas (1) ────── (1) notas_consumo
reservas (1) ──────< (N) notificaciones_whatsapp
platos (1) ──────< (N) pre_pedidos
categorias_platos (1) ──────< (N) platos
administradores (1) ──────< (N) historial_estados
```

### Claves Foráneas (Foreign Keys)

| Tabla Hija | Campo FK | Tabla Padre | Acción |
|------------|----------|-------------|--------|
| `reservas` | `cliente_id` | `clientes.id` | RESTRICT |
| `reservas` | `mesa_id` | `mesas.id` | RESTRICT |
| `pre_pedidos` | `reserva_id` | `reservas.id` | CASCADE DELETE |
| `pre_pedidos` | `plato_id` | `platos.id` | RESTRICT |
| `platos` | `categoria_id` | `categorias_platos.id` | RESTRICT |
| `notas_consumo` | `reserva_id` | `reservas.id` | RESTRICT |
| `historial_estados` | `usuario_id` | `administradores.id` | SET NULL |
| `notificaciones_whatsapp` | `reserva_id` | `reservas.id` | CASCADE DELETE |

---

## ⚙️ TRIGGERS (AUTOMATIZACIÓN)

### 1. `actualizar_precio_mesa_before_insert`
**Evento:** BEFORE INSERT en `mesas`  
**Función:** Calcula automáticamente `precio_reserva` según `capacidad_maxima`

### 2. `actualizar_precio_mesa_before_update`
**Evento:** BEFORE UPDATE en `mesas`  
**Función:** Recalcula `precio_reserva` si cambia la capacidad

### 3. `tr_mesas_cambio_estado`
**Evento:** AFTER UPDATE en `mesas`  
**Función:** Registra cambios de estado en `historial_estados`

### 4. `tr_reservas_cambio_estado`
**Evento:** AFTER UPDATE en `reservas`  
**Función:** Registra cambios de estado en `historial_estados`

---

## 📦 PROCEDIMIENTOS ALMACENADOS

### `activar_reservas_programadas()`
**Propósito:** Actualizar estados de reservas según la hora actual

**Lógica:**
1. Cambia `confirmada` → `en_curso` cuando llega la hora de la reserva (±30 min)
2. Cambia `en_curso` → `finalizada` si pasaron más de 3 horas

**Ejecución:**
```sql
CALL activar_reservas_programadas();
```

**Recomendación:** Ejecutar cada 5-15 minutos con cron job o desde el panel admin.

---

## 📊 VISTAS (VIEWS)

### 1. `v_reservas_completas`
Muestra todas las reservas con información completa de cliente, mesa y totales.

**Columnas:**
- `id`, `fecha_reserva`, `hora_reserva`, `numero_personas`, `estado`
- `cliente_nombre`, `cliente_apellido`, `cliente_telefono`, `cliente_email`
- `numero_mesa`, `ubicacion`, `precio_mesa`
- `total_platos`, `total_reserva`

### 2. `v_mesas_disponibles`
Lista de mesas disponibles con capacidad y precio.

**Columnas:**
- `numero_mesa`, `capacidad`, `precio_reserva`, `ubicacion`, `descripcion`

---

## 🔐 ÍNDICES (PERFORMANCE)

### Índices creados:

**Tabla `mesas`:**
- `idx_mesas_estado` en `estado`

**Tabla `reservas`:**
- `idx_fecha_hora` en `(fecha_reserva, hora_reserva)`
- `idx_mesa_fecha` en `(mesa_id, fecha_reserva)`
- `idx_reservas_fecha_estado` en `(fecha_reserva, estado)`

**Tabla `platos`:**
- `idx_platos_categoria` en `(categoria_id, activo)`

**Tabla `notificaciones_whatsapp`:**
- `idx_reserva` en `reserva_id`
- `idx_fecha` en `fecha_envio`

---

## 🎯 CASOS DE USO PRINCIPALES

### Flujo 1: Cliente hace una reserva
1. Cliente elige mesa disponible (desde `v_mesas_disponibles`)
2. Selecciona fecha/hora y número de personas
3. Opcionalmente selecciona platos del menú
4. Se crea registro en `reservas` (estado: `pendiente`)
5. Se crean registros en `pre_pedidos` (si hay platos)
6. **Automáticamente** se envía WhatsApp (registro en `notificaciones_whatsapp`)
7. Se genera `notas_consumo` con el total

### Flujo 2: Activación automática de reserva
1. Cron job ejecuta `CALL activar_reservas_programadas()`
2. Si la hora actual coincide con una reserva confirmada:
   - Estado cambia a `en_curso`
   - Se registra en `historial_estados`

### Flujo 3: Administrador cambia estado de mesa
1. Admin cambia `mesas.estado` desde el panel
2. Trigger `tr_mesas_cambio_estado` se dispara
3. Se registra el cambio en `historial_estados`

---

## 📱 INTEGRACIÓN WHATSAPP (TWILIO)

### Configuración
- **Cuenta Twilio:** Account SID en `config/whatsapp_config.php`
- **Sandbox:** `whatsapp:+14155238886`
- **Código país:** Ecuador (+593)

### Formato de mensaje
```
🍽️ RESERVA CONFIRMADA - Le Salon de Lumière

📋 Nota: NC-20251209-000001
📅 Fecha: 2025-12-15
🕒 Hora: 19:30
🪑 Mesa: M01 (Interior)
👥 Personas: 4

🍴 PLATOS SELECCIONADOS:
• Croquetas de jamón × 2 = $15.00
• Lomo fino × 1 = $22.50

💰 RESUMEN:
Subtotal platos: $37.50
Precio mesa: $8.00
Impuesto (15%): $6.83
━━━━━━━━━━━━━━━
TOTAL: $52.33

¡Gracias por su reserva!
```

### Registro en base de datos
Cada mensaje enviado se guarda en `notificaciones_whatsapp`:
- `estado`: 'enviado' | 'fallido' | 'pendiente'
- `sid_twilio`: ID retornado por Twilio API
- `error_mensaje`: Si el envío falla

---

## 🛠️ MANTENIMIENTO Y BACKUPS

### Backup completo
```bash
/opt/lampp/bin/mysqldump -u crud_proyecto -p12345 crud_proyecto > backup_$(date +%Y%m%d).sql
```

### Restaurar
```bash
/opt/lampp/bin/mysql -u crud_proyecto -p12345 crud_proyecto < backup.sql
```

### Limpiar datos de prueba
```sql
DELETE FROM pre_pedidos;
DELETE FROM notificaciones_whatsapp;
DELETE FROM notas_consumo;
DELETE FROM reservas;
DELETE FROM historial_estados;
ALTER TABLE reservas AUTO_INCREMENT = 1;
```

---

## 📈 ESTADÍSTICAS ÚTILES

### Ver reservas del día
```sql
SELECT * FROM v_reservas_completas
WHERE fecha_reserva = CURDATE()
ORDER BY hora_reserva;
```

### Mesas más reservadas
```sql
SELECT m.numero_mesa, COUNT(*) as total_reservas
FROM reservas r
JOIN mesas m ON r.mesa_id = m.id
GROUP BY m.id
ORDER BY total_reservas DESC
LIMIT 5;
```

### Platos más vendidos
```sql
SELECT p.nombre, SUM(pp.cantidad) as total_vendido
FROM pre_pedidos pp
JOIN platos p ON pp.plato_id = p.id
GROUP BY p.id
ORDER BY total_vendido DESC
LIMIT 10;
```

### Total de ingresos por período
```sql
SELECT DATE(r.fecha_reserva) as fecha,
       SUM(nc.total) as ingresos_dia
FROM notas_consumo nc
JOIN reservas r ON nc.reserva_id = r.id
WHERE r.fecha_reserva BETWEEN '2025-01-01' AND '2025-12-31'
GROUP BY DATE(r.fecha_reserva)
ORDER BY fecha;
```

---

## 🔧 CONFIGURACIÓN INICIAL

### 1. Crear base de datos
```bash
/opt/lampp/bin/mysql -u root < crud_proyecto_COMPLETO_UNIFICADO.sql
```

### 2. Crear usuario
```sql
CREATE USER 'crud_proyecto'@'localhost' IDENTIFIED BY '12345';
GRANT ALL PRIVILEGES ON crud_proyecto.* TO 'crud_proyecto'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Verificar instalación
```sql
USE crud_proyecto;
SHOW TABLES;
SELECT COUNT(*) FROM platos; -- Debe retornar 24
SELECT COUNT(*) FROM mesas;  -- Debe retornar 10
```

---

## 📞 SOPORTE

**Desarrollador:** Joffre Gómez  
**Email:** cliente.demo@example.com  
**Teléfono:** 0990000001  
**Proyecto:** Le Salon de Lumière - Sistema de Gestión de Restaurante  
**Versión Base de Datos:** 2.0 Unificada  
**Fecha:** Diciembre 2025

---

## 📝 CHANGELOG

### Versión 2.0 (Diciembre 2025)
- ✅ Tabla `notificaciones_whatsapp` para integración Twilio
- ✅ Triggers automáticos para precio de mesas
- ✅ Procedimiento `activar_reservas_programadas`
- ✅ Vistas `v_reservas_completas` y `v_mesas_disponibles`
- ✅ Tabla `configuracion_restaurante` para horarios dinámicos
- ✅ Charset UTF8MB4 para soporte de emojis
- ✅ Auditoría completa con `historial_estados`

### Versión 1.0 (Noviembre 2025)
- ✅ Estructura básica: clientes, mesas, reservas
- ✅ Sistema de menú con categorías y platos
- ✅ Notas de consumo y facturación

---

**FIN DE LA DOCUMENTACIÓN**

