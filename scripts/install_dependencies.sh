#!/bin/bash
###############################################################################
# Script de instalación de dependencias para el Sistema de Reservas
# Compatible con: Ubuntu, Debian, WSL, Máquinas Virtuales Linux
###############################################################################

echo "=========================================="
echo "  Instalador de Dependencias"
echo "  Sistema de Reservas de Restaurante"
echo "=========================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir con color
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

# Verificar si se ejecuta como root o con sudo
if [ "$EUID" -ne 0 ]; then 
    print_error "Este script debe ejecutarse con sudo"
    echo "Uso: sudo bash install_dependencies.sh"
    exit 1
fi

echo "1. Verificando sistema operativo..."
if [ -f /etc/os-release ]; then
    . /etc/os-release
    print_success "Sistema detectado: $NAME $VERSION"
else
    print_error "No se pudo detectar el sistema operativo"
    exit 1
fi

echo ""
echo "2. Actualizando lista de paquetes..."
apt-get update -qq
print_success "Lista de paquetes actualizada"

echo ""
echo "3. Verificando Python3..."
if command -v python3 &> /dev/null; then
    PYTHON_VERSION=$(python3 --version)
    print_success "Python3 ya está instalado: $PYTHON_VERSION"
else
    print_info "Instalando Python3..."
    apt-get install -y python3 python3-full
    print_success "Python3 instalado"
fi

echo ""
echo "4. Instalando dependencias de Python..."

PACKAGES=(
    "python3-pymysql"
    "python3-openpyxl"
    "python3-pandas"
    "python3-slugify"
)

for package in "${PACKAGES[@]}"; do
    if dpkg -l | grep -q "^ii  $package "; then
        print_success "$package ya está instalado"
    else
        print_info "Instalando $package..."
        apt-get install -y "$package" -qq
        if [ $? -eq 0 ]; then
            print_success "$package instalado correctamente"
        else
            print_error "Error al instalar $package"
        fi
    fi
done

echo ""
echo "5. Verificando instalación..."
python3 << 'EOF'
import sys
errors = []

try:
    import pymysql
    print("✓ pymysql: OK")
except ImportError:
    print("✗ pymysql: FALTA")
    errors.append("pymysql")

try:
    import openpyxl
    print("✓ openpyxl: OK")
except ImportError:
    print("✗ openpyxl: FALTA")
    errors.append("openpyxl")

try:
    import pandas
    print("✓ pandas: OK")
except ImportError:
    print("✗ pandas: FALTA")
    errors.append("pandas")

try:
    from slugify import slugify
    print("✓ python-slugify: OK")
except ImportError:
    print("✗ python-slugify: FALTA")
    errors.append("python-slugify")

if errors:
    print("\nERROR: Faltan módulos:", ", ".join(errors))
    sys.exit(1)
else:
    print("\n✓ Todas las dependencias de Python están instaladas correctamente")
EOF

if [ $? -ne 0 ]; then
    print_error "Algunas dependencias no se instalaron correctamente"
    exit 1
fi

echo ""
echo "6. Configurando permisos..."
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Dar permisos de ejecución a scripts Python
if [ -f "$SCRIPT_DIR/app/api/update_from_excel.py" ]; then
    chmod +x "$SCRIPT_DIR/app/api/update_from_excel.py"
    print_success "Permisos actualizados: update_from_excel.py"
fi

if [ -f "$SCRIPT_DIR/app/api/crear_excel_ejemplo.py" ]; then
    chmod +x "$SCRIPT_DIR/app/api/crear_excel_ejemplo.py"
    print_success "Permisos actualizados: crear_excel_ejemplo.py"
fi

# Crear directorios necesarios
mkdir -p "$SCRIPT_DIR/uploads"
mkdir -p "$SCRIPT_DIR/storage/logs"
chmod 777 "$SCRIPT_DIR/uploads"
chmod 777 "$SCRIPT_DIR/storage/logs"
print_success "Directorios creados con permisos correctos"

echo ""
echo "=========================================="
print_success "¡Instalación completada exitosamente!"
echo "=========================================="
echo ""
echo "Información del sistema:"
echo "  - Python: $(python3 --version)"
echo "  - Ruta Python: $(which python3)"
echo "  - Sistema: $NAME $VERSION"
echo ""
echo "Puedes ejecutar el script de ejemplo con:"
echo "  python3 $SCRIPT_DIR/app/api/crear_excel_ejemplo.py"
echo ""
