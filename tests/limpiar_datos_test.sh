#!/bin/bash

# Script para limpiar datos de prueba creados durante los tests
# Uso: bash tests/limpiar_datos_test.sh

echo "======================================"
echo "LIMPIEZA DE DATOS DE PRUEBA"
echo "======================================"
echo ""

read -p "⚠️  ¿Estás seguro de eliminar todos los datos de prueba? (s/n): " confirm

if [ "$confirm" != "s" ]; then
    echo "Operación cancelada"
    exit 0
fi

# Conectar a MySQL y eliminar datos de prueba
mysql -u root -p << EOF
USE crud_proyecto;

-- Eliminar mesas de prueba
DELETE FROM mesas WHERE numero_mesa LIKE 'TEST-%' OR numero_mesa LIKE 'MASA-%';

-- Eliminar clientes de prueba
DELETE FROM clientes WHERE usuario LIKE 'test%' OR nombre = 'Test';

-- Mostrar resumen
SELECT 'Mesas restantes' as tabla, COUNT(*) as total FROM mesas
UNION ALL
SELECT 'Clientes restantes', COUNT(*) FROM clientes;

EOF

echo ""
echo "✅ Limpieza completada"
echo ""
