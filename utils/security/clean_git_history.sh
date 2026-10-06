#!/bin/bash
# Script para limpiar el historial de Git de archivos sensibles

echo "🧹 Limpiando archivos sensibles del historial de Git..."
echo ""
echo "⚠️  ADVERTENCIA: Este proceso reescribirá el historial de Git"
echo "   Solo ejecuta esto si estás seguro."
echo ""
read -p "¿Deseas continuar? (s/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "Operación cancelada."
    exit 1
fi

echo ""
echo "🔍 Buscando archivos sensibles en el historial..."

# Backup del repositorio
echo "📦 Creando backup..."
cd ..
BACKUP_NAME="PRY_PROYECTO_backup_$(date +%Y%m%d_%H%M%S)"
cp -r PRY_PROYECTO "$BACKUP_NAME"
echo "✅ Backup creado: $BACKUP_NAME"
cd PRY_PROYECTO

# Eliminar archivos del historial usando git filter-branch
echo ""
echo "🗑️  Eliminando archivos sensibles del historial..."

# Lista de archivos a eliminar
FILES_TO_REMOVE=(
    "config/whatsapp_config.php"
    "conexion/db.php"
    ".env"
)

for file in "${FILES_TO_REMOVE[@]}"; do
    if git log --all --pretty=format: --name-only --diff-filter=A | grep -q "^$file$"; then
        echo "   Eliminando: $file"
        git filter-branch --force --index-filter \
            "git rm --cached --ignore-unmatch $file" \
            --prune-empty --tag-name-filter cat -- --all
    fi
done

echo ""
echo "🧹 Limpiando referencias..."
rm -rf .git/refs/original/
git reflog expire --expire=now --all
git gc --prune=now --aggressive

echo ""
echo "✅ Limpieza completada"
echo ""
echo "📝 Próximos pasos:"
echo "   1. Verifica que todo funcione correctamente"
echo "   2. Si hay un repositorio remoto, deberás hacer force push:"
echo "      git push origin --force --all"
echo ""
echo "⚠️  NOTA: El force push afectará a todos los colaboradores"
echo "   Notifícales antes de hacerlo."
