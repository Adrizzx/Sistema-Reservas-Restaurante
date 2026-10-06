# 🔐 Guía de Seguridad y Configuración de Credenciales

## 📚 PROYECTO DE CLASE/GRUPO - Configuración Simplificada

Para proyectos de clase o grupos pequeños, **los archivos de configuración SÍ están en Git** para facilitar el trabajo colaborativo.

### ✅ Archivos Incluidos en Git (Para Desarrollo/Clase):
- ✅ `conexion/db.php` - Configuración de base de datos (credenciales de desarrollo compartidas)
- ✅ `config/whatsapp_config.php` - Configuración de Twilio (credenciales de desarrollo)
- ✅ Todos los archivos `.example` como referencia

### ❌ Solo se Ignoran (Para Producción):
- ❌ `.env.production` - Variables de entorno de servidor
- ❌ `.env.server` - Credenciales de producción

## ✅ Configuración Correcta

### 1️⃣ Para Desarrollo Local

```bash
# 1. Copia el archivo de ejemplo
cp .env.example .env

# 2. Edita .env con tus credenciales reales
nano .env

# 3. Copia los archivos de configuración
cp conexion/db.php.example conexion/db.php
cp config/whatsapp_config.php.example config/whatsapp_config.php

# 4. Edita cada archivo con tus credenciales
```

### 2️⃣ Para Subir a Producción/Servidor

**LO QUE SÍ SUBES A GIT:**
- ✅ `.env.example`
- ✅ `conexion/db.php.example`
- ✅ `config/whatsapp_config.php.example`
- ✅ `.gitignore`

**LO QUE CREAS MANUALMENTE EN EL SERVIDOR:**
```bash
# En el servidor, después de clonar el repo:

# 1. Crea el archivo .env con credenciales del servidor
nano /ruta/proyecto/.env

# 2. Crea los archivos de configuración
cp conexion/db.php.example conexion/db.php
nano conexion/db.php  # Editar con credenciales del servidor

cp config/whatsapp_config.php.example config/whatsapp_config.php
nano config/whatsapp_config.php  # Editar con credenciales de producción
```

## 🔍 Verificar que .env no se suba

```bash
# Verifica que .env está en .gitignore
cat .gitignore | grep .env

# Verifica el estado de Git
git status

# Si aparece .env, agrégalo a .gitignore inmediatamente
echo ".env" >> .gitignore
git rm --cached .env  # Si ya lo habías agregado antes
```

## 🚨 ¿Qué hacer si accidentalmente subiste credenciales?

Si ya subiste un `.env` o archivos con credenciales:

### Opción 1: Remover del último commit (SI NO HAS HECHO PUSH)
```bash
git rm --cached .env
git rm --cached conexion/db.php
git rm --cached config/whatsapp_config.php
git commit --amend -m "Remove sensitive files"
```

### Opción 2: Si ya hiciste PUSH (MÁS GRAVE)
```bash
# 1. Cambia TODAS tus credenciales inmediatamente
# 2. Elimina los archivos del repo
git rm .env conexion/db.php config/whatsapp_config.php
git commit -m "Remove sensitive files from repository"
git push

# 3. IMPORTANTE: Cambiar contraseñas de:
# - Base de datos
# - API keys de Twilio
# - Cualquier otra credencial expuesta
```

## 📝 Buenas Prácticas

1. **Dos repositorios**:
   - Repositorio público: Sin credenciales, solo código
   - Archivo privado separado: Tus credenciales (NO en Git)

2. **Variables de entorno**:
   - En servidor compartido: Usa `.env` fuera del directorio web público
   - En servidor VPS: Usa variables de entorno del sistema

3. **Diferentes credenciales por entorno**:
   - Desarrollo: Base de datos local
   - Producción: Base de datos del servidor
   - Nunca uses las mismas contraseñas

4. **Documentación**:
   - Mantén `.env.example` actualizado
   - Documenta qué variables son necesarias
   - No pongas valores reales en los .example

## 📂 Estructura de Archivos del Proyecto

```
proyecto/
├── .env                        ❌ NO SUBIR (Git ignora)
├── .env.example               ✅ SÍ SUBIR (plantilla)
├── .gitignore                 ✅ SÍ SUBIR
├── conexion/
│   ├── db.php                 ❌ NO SUBIR (Git ignora)
│   └── db.php.example         ✅ SÍ SUBIR (plantilla)
└── config/
    ├── whatsapp_config.php    ❌ NO SUBIR (Git ignora)
    └── whatsapp_config.php.example ✅ SÍ SUBIR (plantilla)
```

## 🎯 Resumen

| Archivo | Subir a Git? | Crear en Servidor? |
|---------|-------------|-------------------|
| `.env.example` | ✅ SÍ | ❌ NO (ya viene del repo) |
| `.env` | ❌ NUNCA | ✅ SÍ (manualmente) |
| `db.php.example` | ✅ SÍ | ❌ NO (ya viene del repo) |
| `db.php` | ❌ NUNCA | ✅ SÍ (copiar y editar) |
| `.gitignore` | ✅ SÍ | ❌ NO (ya viene del repo) |

## 📞 Preguntas Frecuentes

**P: ¿Cómo funcionará en el servidor si no subo el .env?**
R: Lo creas manualmente en el servidor después de subir el código.

**P: ¿Y si trabajo en equipo?**
R: Cada desarrollador crea su propio .env basándose en .env.example.

**P: ¿Puedo usar variables de entorno del servidor en vez de .env?**
R: Sí, es incluso más seguro. Modifica `env_loader.php` para leer de `$_ENV`.

**P: ¿El .gitignore funciona en subcarpetas?**
R: Sí, el .gitignore en la raíz afecta todo el proyecto.
