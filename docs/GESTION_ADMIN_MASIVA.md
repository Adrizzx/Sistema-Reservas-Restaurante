# Gestión Masiva de Mesas y Cancelación con WhatsApp

## 📋 Descripción General

Este documento describe las nuevas funcionalidades implementadas para la gestión masiva de mesas y la cancelación de reservas con notificación automática por WhatsApp.

---

## 🎯 Funcionalidades Implementadas

### 1. Cambio Masivo de Estado de Mesas

**Ubicación**: Panel de Administración → Gestión de Mesas → Botón "Acciones Masivas"

#### Opciones Disponibles:

##### A) Marcar TODAS como Ocupadas (🔴)
- **Uso**: Ideal para horarios pico o cuando el restaurante está lleno
- **Acción**: Cambia el estado de todas las mesas a "ocupada"
- **Casos de uso**: 
  - Walk-in customers (clientes sin reserva)
  - Eventos especiales
  - Horario de comida/cena con alta demanda

##### B) Marcar TODAS como Disponibles (🟢)
- **Uso**: Al final del turno o para resetear el estado general
- **Acción**: Cambia el estado de todas las mesas a "disponible"
- **Casos de uso**:
  - Cierre de turno
  - Inicio de jornada
  - Reseteo rápido del sistema

##### C) Marcar Mesas Específicas (✅)
- **Uso**: Control fino sobre mesas individuales
- **Acción**: Permite seleccionar múltiples mesas y cambiar su estado
- **Características**:
  - Checkboxes para selección múltiple
  - Botones "Seleccionar Todas" / "Deseleccionar Todas"
  - Vista con emojis de estado actual
  - Selector de estado destino (Disponible, Ocupada, Reservada, Mantenimiento)

##### D) Liberar Mesas Ocupadas (🔓)
- **Uso**: Liberar solo las mesas que están en estado "Ocupada"
- **Acción**: Cambia automáticamente todas las mesas ocupadas a disponibles
- **Casos de uso**:
  - Fin de horario de comida
  - Limpieza masiva después de evento

---

### 2. Cancelación de Reservas con Notificación WhatsApp

**Ubicación**: Panel de Administración → Gestión de Reservas → Botón "Cancelar" (rojo)

#### Flujo de Cancelación:

1. **Inicio**: El admin hace clic en el botón de cancelar (🗑️) en una reserva
2. **Modal de Confirmación**: Se muestra un formulario con:
   - Nombre del cliente
   - Campo de texto para motivo de cancelación (obligatorio)
   - Nota: "El cliente recibirá un WhatsApp con este motivo"
3. **Procesamiento**: Al confirmar:
   - Se actualiza el estado de la reserva a "cancelada"
   - Se libera la mesa asociada (pasa a "disponible")
   - Se envía WhatsApp al cliente con los detalles
4. **Confirmación**: Mensaje de éxito indicando:
   - ✅ WhatsApp enviado correctamente
   - ⚠️ Reserva cancelada pero WhatsApp no pudo enviarse (si hay error)

#### Formato del Mensaje WhatsApp:

```
🚫 *RESERVA CANCELADA*

Hola [Nombre del Cliente],

Tu reserva ha sido cancelada por el restaurante.

📋 *Detalles de la reserva:*
• Fecha: [DD/MM/YYYY]
• Hora: [HH:MM]
• Mesa: [Número de Mesa]

❌ *Motivo de cancelación:*
[Motivo ingresado por el admin]

---
Para realizar una nueva reserva, visita nuestro sitio web.

Disculpa las molestias.
Gracias por tu comprensión.
```

---

## 🛠️ Archivos Modificados/Creados

### Backend (PHP)

#### 1. `app/api/cambiar_estado_mesa.php` (NUEVO)
- **Propósito**: API para cambio masivo de estado de mesas
- **Método**: POST
- **Parámetros**:
  ```json
  {
    "mesas": "todas" | [1, 2, 3, 4],
    "estado": "disponible" | "ocupada" | "reservada" | "mantenimiento"
  }
  ```
- **Respuesta**:
  ```json
  {
    "success": true,
    "message": "Se actualizaron 15 mesas correctamente",
    "mesasActualizadas": 15
  }
  ```

#### 2. `app/api/cancelar_reserva_admin.php` (NUEVO)
- **Propósito**: Cancelar reserva con notificación WhatsApp
- **Método**: POST
- **Parámetros**:
  ```json
  {
    "reserva_id": 123,
    "motivo": "Problema con el horario"
  }
  ```
- **Respuesta**:
  ```json
  {
    "success": true,
    "message": "Reserva cancelada correctamente",
    "whatsapp_enviado": true
  }
  ```

### Frontend (JavaScript)

#### 3. `public/js/gestion-mesas.js` (MODIFICADO)
**Nuevos métodos agregados**:

- `cambiarEstadoMasivo(mesas, nuevoEstado)`
  - Llama a la API para cambiar estados
  - Actualiza la tabla y estadísticas

- `mostrarAccionesMasivas()`
  - Muestra menú principal con 4 opciones
  - Usa SweetAlert2 con selector

- `confirmarCambioMasivo(mesas, estado)`
  - Confirmación de seguridad antes de aplicar cambios masivos

- `seleccionarMesasEspecificas()`
  - Modal con checkboxes para selección múltiple
  - Botones de seleccionar/deseleccionar todas

- `liberarMesasOcupadas()`
  - Filtra mesas en estado "ocupada"
  - Cambia todas a "disponible"

#### 4. `public/js/gestion-reservas.js` (MODIFICADO)
**Nuevos/Modificados métodos**:

- `confirmarEliminar(id, clienteNombre)` (MODIFICADO)
  - Ahora solicita motivo de cancelación
  - Campo de texto obligatorio
  - Llama al nuevo método de notificación

- `cancelarReservaConNotificacion(id, motivo)` (NUEVO)
  - Llama a la API de cancelación con WhatsApp
  - Muestra estado de envío de WhatsApp
  - Actualiza estadísticas y layout

- `eliminarReserva(id)` (MANTENIDO)
  - Cancelación sin notificación (legacy)
  - Se mantiene para compatibilidad

### Frontend (HTML)

#### 5. `admin.php` (MODIFICADO)
**Cambios en modal de Gestión de Mesas**:
```html
<button class="btn btn-warning" onclick="gestionMesas.mostrarAccionesMasivas()">
    <i class="fas fa-tasks me-2"></i>
    Acciones Masivas
</button>
```

---

## 📊 Flujo de Datos

### Cambio Masivo de Mesas
```
[Admin Panel]
    ↓
[Botón "Acciones Masivas"]
    ↓
[SweetAlert: Seleccionar Acción]
    ↓
[Confirmación de Seguridad]
    ↓
[POST app/api/cambiar_estado_mesa.php]
    ↓
[UPDATE mesas SET estado = ?]
    ↓
[Respuesta JSON con cantidad actualizada]
    ↓
[Actualizar tabla + estadísticas]
```

### Cancelación con WhatsApp
```
[Admin Panel]
    ↓
[Botón "Cancelar Reserva"]
    ↓
[SweetAlert: Ingresar Motivo]
    ↓
[POST app/api/cancelar_reserva_admin.php]
    ↓
[Transacción DB:]
    ├─ UPDATE reservas SET estado = 'cancelada'
    └─ UPDATE mesas SET estado = 'disponible'
    ↓
[Formatear mensaje WhatsApp]
    ↓
[Enviar a API Twilio/WhatsApp]
    ↓
[INSERT notificaciones_whatsapp (log)]
    ↓
[Respuesta JSON con estado de envío]
    ↓
[Mostrar confirmación con estado WhatsApp]
```

---

## 🔐 Validaciones Implementadas

### Cambio de Estado de Mesas
- ✅ Verificación de sesión de administrador
- ✅ Validación de estado contra whitelist: `['disponible', 'ocupada', 'reservada', 'mantenimiento']`
- ✅ Validación de formato de IDs de mesas (enteros positivos)
- ✅ Manejo de "todas" como palabra clave especial
- ✅ Transacciones implícitas en consultas múltiples

### Cancelación de Reservas
- ✅ Verificación de sesión de administrador
- ✅ Validación de existencia de reserva
- ✅ Validación de estado de reserva (no cancelar si ya está cancelada/finalizada)
- ✅ Validación de motivo de cancelación (no vacío)
- ✅ Transacción explícita (reserva + mesa)
- ✅ Manejo de errores de envío de WhatsApp sin rollback de DB
- ✅ Logging de todas las notificaciones intentadas

---

## 💾 Estructura de Base de Datos

### Tabla: `notificaciones_whatsapp`
```sql
CREATE TABLE IF NOT EXISTS notificaciones_whatsapp (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reserva_id INT,
    telefono VARCHAR(20),
    mensaje TEXT,
    estado ENUM('enviado', 'fallido', 'pendiente') DEFAULT 'pendiente',
    respuesta_api TEXT,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo_notificacion VARCHAR(50),
    FOREIGN KEY (reserva_id) REFERENCES reservas(id)
);
```

**Campos importantes**:
- `tipo_notificacion`: 'cancelacion_admin', 'confirmacion', 'recordatorio', etc.
- `estado`: Resultado del envío
- `respuesta_api`: JSON completo de la respuesta de Twilio

---

## 🚀 Casos de Uso Prácticos

### Caso 1: Hora Pico con Walk-ins
**Situación**: Son las 13:00, muchos clientes sin reserva llegan para almorzar.

**Solución**:
1. Abrir Gestión de Mesas
2. Click en "Acciones Masivas"
3. Seleccionar "Marcar TODAS como Ocupadas"
4. Confirmar acción
5. ✅ Todas las mesas pasan a estado "ocupada"

### Caso 2: Cliente Llama para Cancelar
**Situación**: Cliente llama diciendo que no puede asistir a su reserva.

**Solución**:
1. Abrir Gestión de Reservas
2. Buscar la reserva del cliente
3. Click en botón "Cancelar" (rojo)
4. Ingresar motivo: "Cliente llamó para cancelar por problema personal"
5. Confirmar
6. ✅ Reserva cancelada + Mesa liberada + WhatsApp enviado

### Caso 3: Fin de Turno de Almuerzo
**Situación**: Son las 15:00, terminó el turno de almuerzo y quieres resetear.

**Solución**:
1. Abrir Gestión de Mesas
2. Click en "Acciones Masivas"
3. Seleccionar "Liberar Mesas Ocupadas"
4. Confirmar
5. ✅ Solo las mesas ocupadas pasan a disponible (las reservadas se mantienen)

### Caso 4: Selección Específica para Mantenimiento
**Situación**: 3 mesas necesitan reparación urgente.

**Solución**:
1. Abrir Gestión de Mesas
2. Click en "Acciones Masivas"
3. Seleccionar "Marcar Mesas Específicas"
4. Marcar checkboxes de mesas 5, 8 y 12
5. Seleccionar estado destino: "Mantenimiento"
6. Confirmar
7. ✅ Solo esas 3 mesas pasan a mantenimiento

---

## ⚙️ Configuración de WhatsApp

### Archivo: `config/whatsapp_config.php`
```php
define('WHATSAPP_API_URL', 'https://api.twilio.com/...');
define('WHATSAPP_FROM', 'whatsapp:+14155238886');
define('TWILIO_ACCOUNT_SID', 'ACxxxxxxxxxxxx');
define('TWILIO_AUTH_TOKEN', 'xxxxxxxxxxxxxxxxxx');
```

**Importante**: Las credenciales de Twilio deben estar configuradas para que las notificaciones funcionen.

---

## 📱 Formato de Teléfono

El sistema maneja automáticamente el formato de teléfonos:

**Entrada del usuario**: `0991234567`  
**Formato procesado**: `593991234567`  
**Formato Twilio**: `whatsapp:+593991234567`

**Función**: `limpiarTelefono()` en `cancelar_reserva_admin.php`

---

## 🐛 Manejo de Errores

### Errores de Base de Datos
- Transacciones con rollback automático
- Mensajes descriptivos al admin
- Logging en error_log de PHP

### Errores de WhatsApp
- La reserva se cancela incluso si WhatsApp falla
- Se notifica al admin del estado de envío
- Se registra en `notificaciones_whatsapp` con estado 'fallido'
- No se bloquea la operación principal

### Errores de Validación
- Validación de motivo de cancelación (obligatorio)
- Validación de estado de mesa (whitelist)
- Validación de existencia de reserva
- Mensajes claros con SweetAlert2

---

## 🎨 Elementos Visuales

### Emojis Utilizados:
- 🟢 Disponible
- 🔴 Ocupada
- 🟡 Reservada
- ⚫ Mantenimiento
- 🔧 Acciones Masivas
- ✅ Confirmación exitosa
- ⚠️ Advertencia
- 🚫 Cancelación

### Colores de Botones:
- **Verde** (#198754): Crear/Agregar
- **Azul** (#0d6efd): Actualizar/Refrescar
- **Amarillo** (#ffc107): Acciones Masivas
- **Rojo** (#dc3545): Cancelar/Eliminar
- **Gris** (#6c757d): Cancelar acción

---

## 📈 Métricas y Logging

### Registros en Base de Datos:
1. **mesas**: Campo `fecha_actualizacion` se actualiza en cada cambio
2. **reservas**: Campo `estado` y `hora_cancelacion` para canceladas
3. **notificaciones_whatsapp**: Registro completo de cada envío

### Logs de Consola:
```javascript
console.log('Inicializando Gestión de Reservas...');
console.error('Error cancelando reserva:', error);
```

---

## 🔄 Actualizaciones Automáticas

Después de cada operación masiva, se actualizan automáticamente:

1. **Tabla de mesas**: `gestionMesas.cargarMesas()`
2. **Tabla de reservas**: `gestionReservas.renderTabla()`
3. **Layout visual**: `window.restaurantLayout.refresh()`
4. **Estadísticas dashboard**: `actualizarEstadisticas()`

---

## 📝 Notas de Desarrollo

### Compatibilidad Backward
- Se mantiene `eliminarReserva()` para cancelación sin WhatsApp
- APIs antiguas siguen funcionando
- Nuevas funcionalidades no rompen código existente

### Extensibilidad
- Fácil agregar nuevos estados de mesa
- Fácil agregar nuevos tipos de notificaciones
- Estructura modular en JavaScript (clases)

### Performance
- Consultas optimizadas con prepared statements
- Uso de IN clause para múltiples IDs
- Caché de configuración de horarios

---

## 🧪 Testing Recomendado

### Test 1: Cambio Masivo
1. Crear 10 mesas de prueba
2. Marcar todas como ocupadas
3. Verificar en DB: `SELECT * FROM mesas WHERE estado = 'ocupada'`
4. Liberar mesas ocupadas
5. Verificar cambio a 'disponible'

### Test 2: Cancelación con WhatsApp
1. Crear reserva de prueba con teléfono válido
2. Cancelar desde panel admin con motivo
3. Verificar cambio de estado en DB
4. Verificar liberación de mesa
5. Revisar registro en `notificaciones_whatsapp`
6. Confirmar recepción de WhatsApp

### Test 3: Manejo de Errores
1. Intentar cancelar reserva ya cancelada
2. Intentar cambiar estado a valor inválido
3. Verificar mensajes de error apropiados

---

## 📞 Soporte

Para problemas relacionados con:
- **Cambio de estado de mesas**: Revisar `cambiar_estado_mesa.php` y logs de PHP
- **Notificaciones WhatsApp**: Revisar credenciales en `whatsapp_config.php`
- **UI/UX**: Revisar console del navegador (F12)

---

## 🔮 Futuras Mejoras

- [ ] Programar cambios de estado (ej: "liberar mesas a las 16:00")
- [ ] Plantillas personalizables de mensajes WhatsApp
- [ ] Dashboard de notificaciones enviadas
- [ ] Reenvío de notificaciones fallidas
- [ ] Notificaciones SMS como fallback
- [ ] Integración con Google Calendar
- [ ] Historial de cambios masivos (audit log)

---

**Última actualización**: 2024  
**Versión**: 1.0.0
