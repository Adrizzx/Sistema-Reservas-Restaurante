#!/bin/bash

# Script para ejecutar todos los tests de límites
# Uso: bash tests/ejecutar_todos_tests.sh

echo "======================================"
echo "EJECUTANDO SUITE COMPLETA DE TESTS"
echo "======================================"
echo ""

# Verificar que PHP esté instalado
if ! command -v php &> /dev/null; then
    echo "❌ ERROR: PHP no está instalado"
    exit 1
fi

# Verificar que XAMPP esté corriendo
if ! curl -s http://localhost/PRY_PROYECTO/ > /dev/null; then
    echo "❌ ERROR: XAMPP no está corriendo o el proyecto no está accesible"
    echo "   Inicia XAMPP con: sudo /opt/lampp/lampp start"
    exit 1
fi

echo "✅ PHP encontrado: $(php -v | head -n 1)"
echo "✅ XAMPP corriendo"
echo ""

# Ejecutar tests de mesas
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. TESTS DE LÍMITES DEL SISTEMA (MESAS)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
php tests/test_limites_sistema.php

echo ""
echo ""

# Ejecutar tests de clientes
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2. TESTS DE LÍMITES DE CLIENTES"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
php tests/test_limites_clientes.php

echo ""
echo ""
echo "======================================"
echo "TESTS COMPLETADOS"
echo "======================================"
echo ""
echo "📄 Para ver las correcciones sugeridas:"
echo "   cat tests/correcciones_sugeridas.md"
echo ""
echo "🧹 Para limpiar datos de prueba:"
echo "   bash tests/limpiar_datos_test.sh"
echo ""
