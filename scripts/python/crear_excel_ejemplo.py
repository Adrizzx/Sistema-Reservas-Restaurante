#!/usr/bin/env python3
"""
Genera un Excel de ejemplo con el formato correcto para subir el menú
"""
import pandas as pd
import sys

# Datos de ejemplo para categorías
categorias_data = {
    'nombre': ['Entradas', 'Platos Fuertes', 'Postres', 'Bebidas'],
    'descripcion': [
        'Aperitivos y entradas para comenzar',
        'Platos principales del menú',
        'Deliciosos postres caseros',
        'Bebidas frías y calientes'
    ],
    'orden_menu': [1, 2, 3, 4],
    'activo': [1, 1, 1, 1]
}

# Datos de ejemplo para platos
platos_data = {
    'categoria': ['Entradas', 'Entradas', 'Platos Fuertes', 'Platos Fuertes', 'Postres', 'Bebidas'],
    'nombre': [
        'Ceviche de Camarón',
        'Empanadas de Queso',
        'Lomo Saltado',
        'Arroz con Mariscos',
        'Tiramisu',
        'Limonada Natural'
    ],
    'descripcion': [
        'Camarones frescos marinados en limón con cebolla morada',
        'Empanadas crujientes rellenas de queso mozzarella',
        'Carne de res salteada con cebolla, tomate y papas fritas',
        'Arroz con mariscos variados en salsa de ají',
        'Postre italiano con café y mascarpone',
        'Refrescante limonada con hielo y menta'
    ],
    'precio': [12.50, 5.00, 18.00, 22.00, 6.50, 3.50],
    'stock_disponible': [50, 100, 30, 25, 40, 999],
    'tiempo_preparacion': [15, 10, 25, 30, 5, 5],
    'imagen_url': [
        'https://example.com/ceviche.jpg',
        'https://example.com/empanadas.jpg',
        'https://example.com/lomo.jpg',
        'https://example.com/arroz.jpg',
        'https://example.com/tiramisu.jpg',
        'https://example.com/limonada.jpg'
    ],
    'ingredientes': [
        'Camarones, limón, cebolla morada, cilantro, ají',
        'Masa, queso mozzarella, aceite',
        'Carne de res, cebolla, tomate, papas, salsa de soja',
        'Arroz, camarones, calamares, mejillones, ají amarillo',
        'Queso mascarpone, café, bizcocho, cacao',
        'Limones, azúcar, agua, menta'
    ],
    'es_especial': [1, 0, 1, 1, 0, 0],
    'activo': [1, 1, 1, 1, 1, 1]
}

# Crear DataFrames
df_categorias = pd.DataFrame(categorias_data)
df_platos = pd.DataFrame(platos_data)

# Guardar en Excel
output_file = '/opt/lampp/htdocs/PRY_PROYECTO/uploads/plantilla_menu.xlsx'
with pd.ExcelWriter(output_file, engine='openpyxl') as writer:
    df_categorias.to_excel(writer, sheet_name='categorias', index=False)
    df_platos.to_excel(writer, sheet_name='platos', index=False)

print(f"✅ Archivo de ejemplo creado: {output_file}")
print("\nEstructura del Excel:")
print("\n📋 Hoja 'categorias':")
print(df_categorias.to_string(index=False))
print("\n📋 Hoja 'platos':")
print(df_platos.to_string(index=False))
