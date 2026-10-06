# 🔒 Guía: Cómo Subir tu Proyecto a GitHub de Forma Segura

## ⚠️ Problema Detectado

Git ha detectado que los archivos `config/whatsapp_config.php` y `conexion/db.php` contienen credenciales sensibles (API keys de Twilio).

## ✅ Solución Implementada

He configurado tu proyecto para usar variables de entorno y proteger tus credenciales.

### Archivos Creados:

1. **`.env`** - Contiene tus credenciales reales (NUNCA se sube a GitHub)
2. **`.env.example`** - Plantilla sin credenciales (se sube a GitHub)
3. **`.gitignore`** - Protege archivos sensibles
4. **`config/env_loader.php`** - Carga variables de entorno
5. **`*.example`** - Plantillas de archivos de configuración

### Archivos Modificados:

- `config/config.php` - Ahora usa variables de entorno
- `config/whatsapp_config.php` - Ahora usa variables de entorno
- `conexion/db.php` - Ahora usa variables de entorno

## 📋 Pasos para Subir a GitHub

### Opción 1: Empezar Desde Cero (Recomendado para Nuevos Repos)

Si aún no has subido nada a GitHub o quieres empezar limpio:

```bash
# 1. Descartar cambios en archivos sensibles
git restore config/whatsapp_config.php conexion/db.php

# 2. Agregar archivos al .gitignore
# Ya está hecho ✅

# 3. Agregar solo los archivos seguros
git add .env.example
git add .gitignore
git add CONFIGURACION.md
git add config/env_loader.php
git add config/config.php
git add config/whatsapp_config.php.example
git add conexion/db.php.example
git add prepare_for_github.sh

# 4. Agregar el resto de archivos modificados (sin credenciales)
git add admin.php index.html mesas.php
git add app/
git add models/
git add controllers/
git add views/
git add assets/
git add docs/

# 5. Hacer commit
git commit -m "Add environment variables configuration and secure credential management"

# 6. Subir a GitHub
git push origin main
```

### Opción 2: Limpiar Historial Existente

Si ya subiste archivos con credenciales a GitHub:

```bash
# 1. Ejecutar el script de limpieza
./clean_git_history.sh

# 2. Hacer force push (¡CUIDADO! Esto reescribe el historial)
git push origin --force --all

# 3. Continuar con los pasos de la Opción 1
```

## 🔑 Configuración para Otros Desarrolladores

Cuando alguien clone tu repositorio, deberá:

```bash
# 1. Clonar el repositorio
git clone https://github.com/JoffreG2004/MODELOS-DE-PROCESOS.git
cd MODELOS-DE-PROCESOS

# 2. Copiar los archivos de ejemplo
cp .env.example .env
cp config/whatsapp_config.php.example config/whatsapp_config.php
cp conexion/db.php.example conexion/db.php

# 3. Editar .env con sus credenciales reales
nano .env
```

## ✅ Verificación

Antes de hacer push, verifica:

```bash
# Ver archivos que se van a subir
git status

# Verificar que .env NO aparezca
# Verificar que config/whatsapp_config.php NO aparezca
# Verificar que conexion/db.php NO aparezca

# Ver el contenido que se va a subir
git diff --cached

# Ejecutar script de verificación
./prepare_for_github.sh
```

## 🚫 NUNCA Subas:

- ❌ `.env`
- ❌ `config/whatsapp_config.php` (solo sube el .example)
- ❌ `conexion/db.php` (solo sube el .example)
- ❌ Archivos con contraseñas o API keys

## ✅ SIEMPRE Sube:

- ✅ `.env.example`
- ✅ `.gitignore`
- ✅ `*.example` (archivos de plantilla)
- ✅ Documentación (README, CONFIGURACION.md)

## 🆘 Si Accidentalmente Subiste Credenciales

1. **Cambia INMEDIATAMENTE** tus credenciales en Twilio
2. Ejecuta `./clean_git_history.sh`
3. Haz force push: `git push origin --force --all`
4. Actualiza tus credenciales en `.env`

## 📞 Obtener Nuevas Credenciales de Twilio

Si necesitas regenerar tus credenciales:

1. Ve a [Twilio Console](https://console.twilio.com/)
2. Navega a Account > API keys & tokens
3. Genera un nuevo Auth Token
4. Actualiza tu archivo `.env` local

## 📝 Notas Importantes

- El archivo `.env` solo existe en tu máquina local
- Cada desarrollador debe crear su propio `.env`
- Git ignorará automáticamente los archivos listados en `.gitignore`
- GitHub Secret Scanning detecta credenciales expuestas

---

## ⚡ Comando Rápido

Para hacer todo en un solo comando (opción segura):

```bash
# Resetear archivos sensibles y agregar solo lo seguro
git restore config/whatsapp_config.php conexion/db.php && \
git add .env.example .gitignore CONFIGURACION.md config/env_loader.php \
config/config.php config/*.example conexion/*.example prepare_for_github.sh && \
git commit -m "feat: Add secure environment variable configuration" && \
echo "✅ Listo para push. Revisa con 'git status' y luego 'git push origin main'"
```

---

💡 **Tip**: Lee `CONFIGURACION.md` para más detalles sobre la configuración del proyecto.
