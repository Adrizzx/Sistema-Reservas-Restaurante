# 🔒 Configuración de Variables de Entorno

Este proyecto utiliza variables de entorno para proteger información sensible como claves API y credenciales de base de datos.

## 📋 Instalación

### 1. Clonar el repositorio
```bash
git clone https://github.com/TU_USUARIO/tu-repositorio.git
cd tu-repositorio
```

### 2. Configurar variables de entorno

Copia el archivo de ejemplo y configúralo con tus credenciales:

```bash
cp .env.example .env
```

### 3. Editar el archivo .env

Abre el archivo `.env` y reemplaza los valores de ejemplo con tus credenciales reales:

```env
# Configuración de Base de Datos
DB_HOST=localhost
DB_NAME=tu_base_de_datos
DB_USER=tu_usuario
DB_PASS=tu_contraseña

# Configuración de Twilio WhatsApp
TWILIO_ACCOUNT_SID=tu_account_sid_real
TWILIO_AUTH_TOKEN=tu_auth_token_real
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
```

## 🔑 Obtener Credenciales de Twilio

1. Regístrate en [Twilio Console](https://www.twilio.com/console)
2. Obtén tu **Account SID** y **Auth Token**
3. Configura el sandbox de WhatsApp en la sección de Messaging
4. Agrega las credenciales al archivo `.env`

## ⚠️ Importante

- **NUNCA** subas el archivo `.env` a GitHub
- El archivo `.env` está incluido en `.gitignore`
- Solo comparte el archivo `.env.example` como referencia
- Cada desarrollador debe crear su propio archivo `.env` local

## 🗄️ Base de Datos

1. Importa el archivo SQL ubicado en `sql/crud_proyecto_COMPLETO_UNIFICADO.sql`
2. Configura las credenciales en el archivo `.env`

```bash
mysql -u root -p < sql/crud_proyecto_COMPLETO_UNIFICADO.sql
```

## 🚀 Ejecutar el Proyecto

1. Asegúrate de tener XAMPP/LAMPP instalado
2. Coloca el proyecto en `htdocs`
3. Inicia Apache y MySQL
4. Accede a: `http://localhost/PRY_PROYECTO`

## 📝 Notas

- Si encuentras errores de conexión, verifica las credenciales en `.env`
- El modo DEBUG puede activarse con `DEBUG_MODE=true`
- Para pruebas de WhatsApp sin enviar mensajes reales, usa `TEST_MODE=true`
