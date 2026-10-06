#!/bin/bash
# Script para ejecutar el procedimiento almacenado y actualizar estados de reservas

# Configuración de la base de datos
DB_USER="root"
DB_NAME="crud_proyecto"

echo "Actualizando procedimiento almacenado..."
mysql -u $DB_USER $DB_NAME < /opt/lampp/htdocs/PRY_PROYECTO/sql/procedimiento_activar_reservas.sql

echo "Ejecutando procedimiento para actualizar estados..."
mysql -u $DB_USER $DB_NAME -e "CALL activar_reservas_programadas();"

echo "Estados actualizados correctamente!"
echo "Reservas finalizadas:"
mysql -u $DB_USER $DB_NAME -e "SELECT COUNT(*) as total FROM reservas WHERE estado = 'finalizada';"
