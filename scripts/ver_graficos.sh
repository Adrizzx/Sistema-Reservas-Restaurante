#!/bin/bash
# Script para abrir todos los gráficos generados

echo "🎨 Abriendo visualizaciones de análisis de código..."
echo ""

METRICAS_DIR="/opt/lampp/htdocs/PRY_PROYECTO/docs/metricas"

# Abrir todos los gráficos con el visor de imágenes predeterminado
xdg-open "$METRICAS_DIR/dashboard_resumen.png" 2>/dev/null &
sleep 0.5
xdg-open "$METRICAS_DIR/seguridad.png" 2>/dev/null &
sleep 0.5
xdg-open "$METRICAS_DIR/patrones_solid.png" 2>/dev/null &
sleep 0.5
xdg-open "$METRICAS_DIR/metricas_codigo.png" 2>/dev/null &
sleep 0.5
xdg-open "$METRICAS_DIR/complejidad_ciclomatica.png" 2>/dev/null &
sleep 0.5
xdg-open "$METRICAS_DIR/anidaciones_big_o.png" 2>/dev/null &
sleep 0.5
xdg-open "$METRICAS_DIR/nomenclatura.png" 2>/dev/null &

echo "✅ Gráficos abiertos!"
echo ""
echo "📊 Gráficos disponibles:"
echo "  1. dashboard_resumen.png      - Vista general del proyecto"
echo "  2. seguridad.png              - Análisis de seguridad"
echo "  3. patrones_solid.png         - Patrones de diseño y SOLID"
echo "  4. metricas_codigo.png        - Métricas de calidad"
echo "  5. complejidad_ciclomatica.png - Complejidad por archivo"
echo "  6. anidaciones_big_o.png      - Complejidad algorítmica"
echo "  7. nomenclatura.png           - Convenciones de nombres"
echo ""
echo "📄 Reporte en: $METRICAS_DIR/reporte_analisis.txt"
