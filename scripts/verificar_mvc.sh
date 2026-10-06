#!/bin/bash
# Script de prueba para verificar estructura MVC

echo "🏗️  VERIFICACIÓN DE ESTRUCTURA MVC"
echo "=================================="
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

check_directory() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✓${NC} Directorio: $1"
        return 0
    else
        echo -e "${RED}✗${NC} Directorio faltante: $1"
        return 1
    fi
}

check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} Archivo: $1"
        return 0
    else
        echo -e "${RED}✗${NC} Archivo faltante: $1"
        return 1
    fi
}

echo "📁 DIRECTORIOS:"
check_directory "config"
check_directory "models"
check_directory "controllers"
check_directory "views/pages"
check_directory "views/layouts"
check_directory "views/components"
check_directory "assets/css"
check_directory "assets/js"
check_directory "assets/bootstrap"

echo ""
echo "⚙️  CONFIGURACIÓN:"
check_file "config/config.php"
check_file "config/database.php"

echo ""
echo "🗄️  MODELS:"
check_file "models/Mesa.php"
check_file "models/Cliente.php"
check_file "models/Reserva.php"
check_file "models/Plato.php"
check_file "models/Categoria.php"

echo ""
echo "🎮 CONTROLLERS:"
check_file "controllers/AuthController.php"
check_file "controllers/MesaController.php"
check_file "controllers/ReservaController.php"
check_file "controllers/MenuController.php"

echo ""
echo "📄 VIEWS:"
check_file "views/pages/index.html"
check_file "views/pages/mesas.php"
check_file "views/pages/admin.php"
check_file "views/pages/registro.php"

echo ""
echo "🔌 APIS MVC:"
check_file "app/api/mesas_estado_mvc.php"
check_file "app/api/seleccionar_mesa_mvc.php"
check_file "app/api/obtener_menu_mvc.php"
check_file "app/api/login_cliente_mvc.php"
check_file "app/api/registro_cliente_mvc.php"
check_file "app/api/crear_reserva_mvc.php"

echo ""
echo "📊 RESUMEN:"
echo "==========="

# Contar archivos
CONFIG_FILES=$(find config -name "*.php" 2>/dev/null | wc -l)
MODEL_FILES=$(find models -name "*.php" 2>/dev/null | wc -l)
CONTROLLER_FILES=$(find controllers -name "*.php" 2>/dev/null | wc -l)
VIEW_FILES=$(find views/pages -name "*.php" -o -name "*.html" 2>/dev/null | wc -l)
API_MVC_FILES=$(find app/api -name "*_mvc.php" 2>/dev/null | wc -l)

echo "Config files:     $CONFIG_FILES"
echo "Models:           $MODEL_FILES"
echo "Controllers:      $CONTROLLER_FILES"
echo "Views:            $VIEW_FILES"
echo "APIs MVC:         $API_MVC_FILES"

echo ""
echo -e "${GREEN}✅ Estructura MVC completa!${NC}"
echo ""
echo "📖 Lee MVC_ESTRUCTURA.md para más información"
