# 🍽️ Sistema de Reservas - Le Salon de Lumière

## ✅ Funcionalidad Implementada: Notificación de Reservas Nuevas

### 📋 ¿Cómo Funciona?

#### Cuando un Cliente Hace una Reserva:
1. El cliente hace una reserva desde `mesas.php` o `perfil_cliente.php`
2. La reserva se crea con estado **"pendiente"**
3. NO se envía WhatsApp automáticamente

#### En el Panel de Administrador:
1. Al entrar a `admin.php`, aparece una **ALERTA DESTACADA** en la parte superior
2. La alerta muestra todas las reservas pendientes con:
   - 🔔 Ícono de campana animado
   - Contador de reservas pendientes
   - Tarjetas individuales con datos del cliente

#### Cuando el Admin Confirma:
1. Click en botón **"Confirmar"** ✅
2. El sistema:
   - Cambia el estado de "pendiente" a "confirmada"
   - **Envía automáticamente WhatsApp al cliente**
   - Muestra notificación de éxito
   - Actualiza las estadísticas
   - Remueve la reserva de la lista de pendientes

### 🎯 Características Clave:

- ✅ **Alerta Visual Destacada** con animación
- ✅ **Actualización Automática** cada 2 minutos
- ✅ **Botón Manual** para actualizar cuando se desee
- ✅ **Integración con WhatsApp** al confirmar
- ✅ **Botón de Rechazar** para cancelar reservas no deseadas
- ✅ **Contador en Tiempo Real** de reservas pendientes

### 📁 Estructura MVC:

```
models/
  ├── Reserva.php          # Modelo de reservas
  └── Cliente.php          # Modelo de clientes

controllers/
  └── ReservaController.php # Controlador de reservas

views/
  ├── admin.php            # Vista principal del admin
  └── components/          # Componentes reutilizables

app/
  ├── obtener_reservas.php         # API para obtener reservas
  └── api/
      ├── confirmar_reserva_admin.php  # API para confirmar
      └── enviar_whatsapp.php          # API para WhatsApp

utils/
  └── security/            # Archivos de seguridad (.env, .gitignore)
```

### 🔧 APIs Utilizadas:

1. **`app/obtener_reservas.php?estado=pendiente`**
   - Obtiene todas las reservas pendientes
   - Se llama automáticamente cada 2 minutos

2. **`app/api/confirmar_reserva_admin.php`**
   - POST: `{reserva_id: 123}`
   - Confirma la reserva y envía WhatsApp

3. **`app/api/enviar_whatsapp.php`**
   - Envía notificación por WhatsApp usando Twilio

### 🎨 Elementos Visuales:

- Card con borde amarillo y gradiente
- Badge con contador de reservas
- Ícono de campana con animación swing
- Botones de acción: Confirmar (verde) y Rechazar (rojo)
- Información completa de cada reserva

### ⚙️ Configuración:

Las credenciales de Twilio están en `utils/security/.env`:
```env
TWILIO_ACCOUNT_SID=tu_account_sid
TWILIO_AUTH_TOKEN=tu_auth_token
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
```

### 🚀 Próximos Pasos:

Para subir a GitHub de forma segura:
```bash
cd utils/security
cat GUIA_GITHUB.md
```

---

**Desarrollado con estructura MVC** 🏗️
