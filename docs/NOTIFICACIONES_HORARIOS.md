# 📱 Sistema de Notificaciones WhatsApp por Cambio de Horarios

## 📋 Descripción General

Este sistema automáticamente detecta, cancela y notifica por WhatsApp a los clientes cuando un cambio en los horarios de atención afecta sus reservas confirmadas.

## 🔄 Flujo del Sistema

### 1. **Administrador Cambia Horarios**
El admin accede a "Configurar Horarios" y modifica:
- Horarios de apertura/cierre
- Horarios específicos por día (L-V, Sábado, Domingo)
- Días cerrados

### 2. **Sistema Detecta Reservas Afectadas**
El sistema automáticamente:
- ✅ Verifica todas las reservas futuras (pendientes y confirmadas)
- ✅ Compara con los nuevos horarios
- ✅ Identifica reservas que quedan fuera del horario

### 3. **Advertencia al Administrador**
Si hay reservas afectadas, muestra:
- 📊 Lista de clientes afectados
- 📅 Fecha y hora de cada reserva
- ⚠️ Tipo de conflicto (antes de apertura / después de cierre)
- 🔔 Botón para confirmar cambios

### 4. **Cancelación y Notificación**
Al confirmar el cambio:
- ❌ Cancela automáticamente las reservas afectadas
- 📱 Envía WhatsApp personalizado a cada cliente
- 📝 Registra notificación en base de datos
- ✅ Muestra resumen de notificaciones enviadas

## 📱 Mensaje de WhatsApp

El mensaje enviado incluye:

```
🔔 Le Salon de Lumière

Estimado/a Juan Pérez,

Lamentamos informarle que su reserva ha sido CANCELADA debido a un cambio en nuestros horarios de atención.

📅 Reserva cancelada:
• Fecha: 15/12/2025
• Hora: 09:00
• Mesa: A05
• Personas: 4

⏰ Nuevos horarios de atención:
• Lunes a Viernes: 11:00 - 22:00
• Sábado: 12:00 - 23:00
• Domingo: 13:00 - 21:00

💡 Puede realizar una nueva reserva en nuestros nuevos horarios.

Para más información o realizar una nueva reserva, contáctenos al +593999999999

Disculpe las molestias.
Equipo de Le Salon de Lumière 🍽️
```

## 🏗️ Arquitectura MVC

### 📁 Estructura de Archivos

```
controllers/
  └── NotificacionController.php       # Controlador de notificaciones

app/api/
  └── gestionar_horarios.php          # API para gestionar horarios

models/
  └── Reserva.php                     # Modelo de reservas

sql/
  └── agregar_motivo_cancelacion.sql  # Script SQL

docs/
  └── NOTIFICACIONES_HORARIOS.md      # Esta documentación
```

### 📄 Componentes Principales

#### 1. **NotificacionController.php**
- `enviarNotificacionCancelacionHorarios()` - Procesa y envía notificaciones
- `generarMensajeCancelacionHorarios()` - Genera mensaje personalizado
- `enviarWhatsApp()` - Envía mensaje via Twilio
- `registrarNotificacion()` - Guarda log en BD

#### 2. **gestionar_horarios.php**
- Valida cambios de horarios
- Detecta reservas afectadas
- Coordina cancelación y notificación

## 🗄️ Base de Datos

### Tabla: `reservas`
```sql
ALTER TABLE reservas 
ADD COLUMN motivo_cancelacion VARCHAR(255) NULL DEFAULT NULL;
```

### Tabla: `notificaciones_whatsapp`
Ya existente, registra:
- `reserva_id` - ID de la reserva
- `telefono` - Número del cliente
- `tipo_notificacion` - 'cancelacion_horarios'
- `mensaje` - Contenido del mensaje
- `estado` - 'enviado' o 'fallido'
- `fecha_envio` - Timestamp

## ⚙️ Configuración

### 1. Aplicar Cambios en Base de Datos
```bash
mysql -u root -p crud_proyecto < sql/agregar_motivo_cancelacion.sql
```

### 2. Variables de Entorno
Las credenciales de Twilio están en `utils/security/.env`:
```env
TWILIO_ACCOUNT_SID=tu_account_sid
TWILIO_AUTH_TOKEN=tu_auth_token
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
RESTAURANT_NAME="Le Salon de Lumière"
RESTAURANT_PHONE=+593999999999
COUNTRY_CODE=593
```

## 🧪 Casos de Uso

### **Caso 1: Cambio de Hora de Apertura**
**Antes:** 09:00 - 22:00  
**Después:** 11:00 - 22:00  
**Resultado:** Reservas entre 09:00-10:59 son canceladas y notificadas

### **Caso 2: Cambio de Horario de Sábado**
**Antes:** 10:00 - 23:00  
**Después:** 12:00 - 21:00  
**Resultado:** Reservas de sábado fuera de 12:00-21:00 son canceladas

### **Caso 3: Agregar Día Cerrado**
**Antes:** Lunes abierto  
**Después:** Lunes cerrado  
**Resultado:** Todas las reservas del lunes son canceladas

## 📊 Respuesta de la API

### Detección de Reservas Afectadas:
```json
{
  "success": false,
  "advertencia": true,
  "message": "Hay 3 reserva(s) que quedarían fuera del nuevo horario",
  "reservas_afectadas": [
    {
      "id": 46,
      "cliente": "Juan Pérez",
      "telefono": "0990000001",
      "fecha": "15/12/2025",
      "hora": "09:00",
      "mesa": "A05",
      "personas": 4,
      "nuevo_horario": "11:00 - 22:00",
      "problema": "antes_apertura"
    }
  ],
  "requiere_confirmacion": true
}
```

### Después de Confirmar:
```json
{
  "success": true,
  "message": "Configuración actualizada correctamente. Se cancelaron 3 reserva(s) y se enviaron 3 notificación(es) por WhatsApp.",
  "notificaciones": {
    "total": 3,
    "enviados": 3,
    "fallidos": 0,
    "detalles": [...]
  }
}
```

## 🔍 Logs y Debugging

### Ver Notificaciones Enviadas:
```sql
SELECT * FROM notificaciones_whatsapp 
WHERE tipo_notificacion = 'cancelacion_horarios'
ORDER BY fecha_envio DESC;
```

### Ver Reservas Canceladas:
```sql
SELECT * FROM reservas 
WHERE estado = 'cancelada' 
AND motivo_cancelacion = 'Cambio de horarios de atención'
ORDER BY fecha_reserva DESC;
```

## ✅ Checklist de Implementación

- [x] Crear NotificacionController.php
- [x] Actualizar gestionar_horarios.php
- [x] Crear script SQL
- [x] Agregar documentación
- [ ] Aplicar cambios en base de datos
- [ ] Probar con horarios de prueba
- [ ] Verificar envío de WhatsApp

## 🚀 Cómo Probar

1. **Crear una reserva de prueba para mañana a las 09:00**
2. **En Admin → Configurar Horarios**
3. **Cambiar hora de apertura de 09:00 a 11:00**
4. **Sistema mostrará advertencia con tu reserva**
5. **Confirmar cambio**
6. **Verificar:**
   - ✅ Reserva cancelada en BD
   - ✅ WhatsApp recibido
   - ✅ Log en notificaciones_whatsapp

## 📞 Soporte

Para dudas o problemas:
- Revisar logs en `storage/logs/`
- Consultar tabla `notificaciones_whatsapp`
- Verificar credenciales de Twilio en `.env`

---

**Desarrollado con estructura MVC** 🏗️  
**Última actualización:** 10/12/2025
