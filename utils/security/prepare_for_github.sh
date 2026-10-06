#!/bin/bash
# Script para preparar el proyecto para GitHub

echo "🔒 Preparando proyecto para GitHub..."
echo ""

# Verificar si el archivo .env existe
if [ ! -f ".env" ]; then
    echo "❌ Error: No se encontró el archivo .env"
    echo "   Copia .env.example a .env y configura tus credenciales"
    exit 1
fi

# Verificar si .gitignore existe
if [ ! -f ".gitignore" ]; then
    echo "❌ Error: No se encontró el archivo .gitignore"
    exit 1
fi

echo "✅ Archivo .env encontrado"
echo "✅ Archivo .gitignore encontrado"
echo ""

# Verificar que .env esté en .gitignore
if grep -q "^\.env$" .gitignore; then
    echo "✅ .env está protegido en .gitignore"
else
    echo "⚠️  Agregando .env a .gitignore..."
    echo ".env" >> .gitignore
fi

# Verificar archivos sensibles
echo ""
echo "📋 Verificando archivos sensibles..."
if git check-ignore .env > /dev/null 2>&1; then
    echo "✅ .env será ignorado por Git"
else
    echo "⚠️  Advertencia: .env podría no estar siendo ignorado"
fi

# Si .env ya está en el repositorio, mostramos advertencia
if git ls-files --error-unmatch .env > /dev/null 2>&1; then
    echo ""
    echo "⚠️  ¡ADVERTENCIA! El archivo .env ya está en el repositorio"
    echo "   Ejecuta los siguientes comandos para eliminarlo:"
    echo ""
    echo "   git rm --cached .env"
    echo "   git commit -m 'Remove .env from repository'"
    echo ""
fi

echo ""
echo "🎉 Tu proyecto está listo para subir a GitHub"
echo ""
echo "📝 Próximos pasos:"
echo "   1. git add ."
echo "   2. git commit -m 'Add environment variables configuration'"
echo "   3. git push origin main"
echo ""
echo "⚠️  Recuerda: Nunca subas el archivo .env"
