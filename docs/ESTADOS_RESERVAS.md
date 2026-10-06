# 📊 Estados de Reservas - Flujo Automático

## 🔄 Ciclo de Vida de una Reserva

```
PENDIENTE → CONFIRMADA → EN_CURSO → FINALIZADA
                ↓
            CANCELADA
```

---

## 📋 Descripción de Estados

### 1. **PENDIENTE** 🟡
- **Cuándo**: Cuando el cliente crea una reserva nueva
- **Duración**: Hasta que el admin confirme o rechace
- **Acciones disponibles**:
  - ✅ Confirmar (admin)
  - ❌ Cancelar (admin/cliente)

### 2. **CONFIRMADA** 🟢
- **Cuándo**: Admin confirma la reserva
- **Duración**: Desde confirmación hasta hora de reserva
- **Cambio automático**: Pasa a "EN_CURSO" cuando llega la hora
- **Acciones disponibles**:
  - ❌ Cancelar (admin/cliente)
  - ✏️ Editar fecha/hora/mesa

### 3. **EN_CURSO** 🔵
- **Cuándo**: Llega la hora de la reserva
- **Duración**: 2 horas desde hora_reserva
- **Cambio automático**: 
  - Inicia: `NOW() >= TIMESTAMP(fecha_reserva, hora_reserva)`
  - Finaliza: `NOW() >= TIMESTAMP(fecha_reserva, hora_reserva + 2 horas)`
- **Estado de mesa**: OCUPADA
- **Acciones disponibles**:
  - ✅ Marcar como completada manualmente
  - ❌ Cancelar (solo admin con motivo)

### 4. **FINALIZADA** ⚪
- **Cuándo**: 
  - Automático: 2 horas después de hora_reserva
  - Manual: Admin marca como completada
- **Estado permanente**: No cambia más
- **Estado de mesa**: DISPONIBLE
- **Acciones disponibles**: Solo visualizar

### 5. **CANCELADA** 🔴
- **Cuándo**: 
  - Admin cancela manualmente
  - Cliente cancela su reserva
  - Sistema cancela por cambio de horarios
- **Requiere**: Motivo de cancelación
- **Estado permanente**: No cambia más
- **WhatsApp**: Se envía notificación al cliente

---

## ⏰ Actualización Automática de Estados

### Script: `app/api/actualizar_estados_reservas.php`

Se ejecuta cada vez que se carga el dashboard de admin.

### Lógica de Actualización:

#### Paso 1: CONFIRMADA → EN_CURSO
```sql
UPDATE reservas 
SET estado = 'en_curso'
WHERE estado = 'confirmada'
AND TIMESTAMP(fecha_reserva, hora_reserva) <= NOW()
AND TIMESTAMP(fecha_reserva, ADDTIME(hora_reserva, '02:00:00')) > NOW()
```

**Condiciones:**
- Estado actual: confirmada
- Ya llegó la hora de la reserva
- Aún no pasaron 2 horas

**Ejemplo:**
- Reserva: 10 dic 2025 a las 17:00
- Hora actual: 10 dic 2025 a las 17:05
- **Resultado**: Cambia a EN_CURSO ✅

#### Paso 2: EN_CURSO → FINALIZADA
```sql
UPDATE reservas 
SET estado = 'finalizada'
WHERE estado = 'en_curso'
AND TIMESTAMP(fecha_reserva, ADDTIME(hora_reserva, '02:00:00')) < NOW()
```

**Condiciones:**
- Estado actual: en_curso
- Ya pasaron 2 horas desde la hora de reserva

**Ejemplo:**
- Reserva: 10 dic 2025 a las 17:00
- Hora actual: 10 dic 2025 a las 19:05
- **Resultado**: Cambia a FINALIZADA ✅

#### Paso 3: Actualizar Estados de Mesas
```sql
-- Todas disponibles por defecto
UPDATE mesas SET estado = 'disponible';

-- Ocupadas si hay reserva EN_CURSO
UPDATE mesas m
INNER JOIN reservas r ON m.id = r.mesa_id
SET m.estado = 'ocupada'
WHERE r.estado = 'en_curso';

-- Reservadas si hay reserva CONFIRMADA para HOY
UPDATE mesas m
INNER JOIN reservas r ON m.id = r.mesa_id
SET m.estado = 'reservada'
WHERE r.estado = 'confirmada'
AND r.fecha_reserva = CURDATE();
```

---

## 🕒 Línea de Tiempo - Ejemplo Práctico

### Reserva: 10 Diciembre 2025 a las 18:00 para 4 personas

| Hora | Estado | Mesa | Descripción |
|------|--------|------|-------------|
| **09:00** | PENDIENTE | Disponible | Cliente hace reserva |
| **10:30** | CONFIRMADA | Disponible | Admin confirma reserva |
| **17:30** | CONFIRMADA | Reservada | Mesa marcada como reservada (30 min antes) |
| **18:00** | EN_CURSO | Ocupada | Llega la hora, cliente puede sentarse |
| **18:30** | EN_CURSO | Ocupada | Cliente comiendo |
| **19:30** | EN_CURSO | Ocupada | Aún dentro del tiempo |
| **20:00** | FINALIZADA | Disponible | 2 horas cumplidas, mesa liberada |

---

## 🚨 Casos Especiales

### Reserva para Mañana
- **Estado**: CONFIRMADA
- **Mesa**: DISPONIBLE (hasta el día de la reserva)
- **No se marca EN_CURSO** hasta que llegue el día y hora

### Reserva Pasada (día anterior)
- **Si está CONFIRMADA**: No cambia a EN_CURSO
- **Requiere**: Admin debe marcar manualmente como FINALIZADA o CANCELADA

### Cliente Llega Tarde
- **Estado**: Sigue EN_CURSO
- **Mesa**: Sigue OCUPADA
- **Duración**: Mantiene las 2 horas desde hora original

### Cliente se Va Antes
- **Admin puede**: Marcar como FINALIZADA manualmente
- **Mesa**: Se libera inmediatamente
- **No espera**: Las 2 horas completas

---

## 🛠️ Mantenimiento

### ¿Qué pasa si el sistema no actualiza estados?

**Verificar:**
1. ¿Se está cargando `actualizar_estados_reservas.php` en admin.php?
2. ¿Hay errores en logs de PHP?
3. ¿La zona horaria del servidor es correcta?

**Ejecutar manualmente:**
```bash
curl http://localhost/PRY_PROYECTO/app/api/actualizar_estados_reservas.php?auth=false
```

### Forzar actualización de una reserva específica

```sql
-- Cambiar a EN_CURSO manualmente
UPDATE reservas SET estado = 'en_curso' WHERE id = 47;

-- Cambiar a FINALIZADA manualmente
UPDATE reservas SET estado = 'finalizada' WHERE id = 47;

-- Liberar mesa
UPDATE mesas SET estado = 'disponible' WHERE id = 5;
```

---

## 📊 Estadísticas de Estados

```sql
-- Contar reservas por estado
SELECT estado, COUNT(*) as total 
FROM reservas 
GROUP BY estado;

-- Reservas EN_CURSO ahora mismo
SELECT r.id, r.fecha_reserva, r.hora_reserva, 
       c.nombre, c.apellido, m.nombre as mesa
FROM reservas r
JOIN clientes c ON r.cliente_id = c.id
JOIN mesas m ON r.mesa_id = m.id
WHERE r.estado = 'en_curso';

-- Reservas que deberían estar EN_CURSO pero no lo están
SELECT r.id, r.fecha_reserva, r.hora_reserva, r.estado
FROM reservas r
WHERE r.estado = 'confirmada'
AND TIMESTAMP(r.fecha_reserva, r.hora_reserva) <= NOW()
AND TIMESTAMP(r.fecha_reserva, ADDTIME(r.hora_reserva, '02:00:00')) > NOW();
```

---

## ❓ FAQ

**P: ¿Por qué mi reserva de ayer sigue como CONFIRMADA?**
R: El sistema solo cambia a EN_CURSO si la reserva es del día actual. Debe cambiarla manualmente a FINALIZADA.

**P: ¿Puedo cambiar el tiempo de duración (2 horas)?**
R: Sí, editar `'02:00:00'` en `actualizar_estados_reservas.php` a la duración deseada.

**P: ¿Se puede desactivar la actualización automática?**
R: Sí, comentar la llamada AJAX en `admin.php` que ejecuta el script cada 30 segundos.

**P: ¿Los estados se guardan en auditoría?**
R: Sí, todos los cambios de estado se registran en `auditoria_reservas`.

---

## 🔗 Archivos Relacionados

- `/app/api/actualizar_estados_reservas.php` - Script de actualización
- `/admin.php` - Llama al script cada 30 segundos
- `/controllers/ReservaController.php` - Lógica de negocio
- `/docs/SISTEMA_AUDITORIA.md` - Registro de cambios

