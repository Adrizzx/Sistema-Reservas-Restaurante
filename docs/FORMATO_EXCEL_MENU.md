# 📋 Formato del Excel para Cargar Menú

## 📦 Estructura del Archivo

El archivo Excel debe tener **DOS hojas (pestañas)**:

### 📑 Hoja 1: `categorias`

**Columnas requeridas:**

| nombre | descripcion | orden_menu | activo |
|--------|-------------|------------|--------|
| Entradas | Deliciosos aperitivos para comenzar | 1 | 1 |
| Platos Fuertes | Nuestros mejores platos principales | 2 | 1 |
| Postres | Dulces tentaciones | 3 | 1 |
| Bebidas | Refrescantes bebidas | 4 | 1 |
| Especiales del Chef | Creaciones únicas del chef | 5 | 1 |

**Descripción de columnas:**
- `nombre` (texto, REQUERIDO): Nombre de la categoría (debe ser único)
- `descripcion` (texto, opcional): Descripción de la categoría
- `orden_menu` (número, opcional): Orden de aparición en el menú (1, 2, 3...)
- `activo` (número, opcional): 1 = activo, 0 = inactivo (por defecto 1)

---

### 📑 Hoja 2: `platos`

**Columnas requeridas:**

| categoria | nombre | descripcion | precio | stock_disponible | tiempo_preparacion | imagen_url | ingredientes | es_especial | activo |
|-----------|--------|-------------|--------|------------------|-------------------|------------|--------------|-------------|--------|
| Entradas | Ceviche de Camarón | Camarones frescos marinados en limón | 12.50 | 50 | 15 | /img/ceviche.jpg | Camarón, limón, cebolla, cilantro | 1 | 1 |
| Platos Fuertes | Lomo Saltado | Trozos de lomo con papas fritas | 15.00 | 30 | 20 | /img/lomo.jpg | Lomo, papa, cebolla, tomate, arroz | 0 | 1 |
| Postres | Tres Leches | Pastel húmedo con tres tipos de leche | 5.50 | 20 | 5 | /img/tres-leches.jpg | Leche condensada, leche evaporada, crema | 0 | 1 |
| Bebidas | Chicha Morada | Bebida tradicional peruana | 3.00 | 100 | 3 | /img/chicha.jpg | Maíz morado, piña, canela | 0 | 1 |
| Especiales del Chef | Pulpo al Olivo | Pulpo tierno con salsa de olivo | 18.00 | 15 | 25 | /img/pulpo.jpg | Pulpo, aceitunas, mayonesa, limón | 1 | 1 |

**Descripción de columnas:**
- `categoria` (texto, REQUERIDO): Nombre de la categoría (debe existir en hoja "categorias")
- `nombre` (texto, REQUERIDO): Nombre del plato
- `descripcion` (texto, opcional): Descripción del plato
- `precio` (número decimal, REQUERIDO): Precio del plato (ej: 12.50)
- `stock_disponible` (número entero, opcional): Cantidad disponible
- `tiempo_preparacion` (número entero, opcional): Minutos de preparación
- `imagen_url` (texto, opcional): URL o ruta de la imagen
- `ingredientes` (texto, opcional): Lista de ingredientes separados por coma
- `es_especial` (número, opcional): 1 = plato especial, 0 = normal
- `activo` (número, opcional): 1 = activo, 0 = inactivo

---

## ⚠️ REGLAS IMPORTANTES

1. **Nombres de hojas**: DEBEN ser exactamente `categorias` y `platos` (minúsculas)
2. **Nombres de columnas**: DEBEN coincidir exactamente (minúsculas, sin acentos)
3. **Orden**: Primero se procesan categorías, luego platos
4. **Unicidad**: El nombre de cada categoría debe ser único
5. **Relación**: Cada plato debe tener una categoría que exista en la hoja "categorias"

---

## 💡 EJEMPLO MÍNIMO

### Hoja "categorias":
```
nombre          | descripcion        | orden_menu | activo
Entradas        | Aperitivos         | 1          | 1
Platos Fuertes  | Platos principales | 2          | 1
```

### Hoja "platos":
```
categoria      | nombre           | descripcion          | precio
Entradas       | Ensalada César   | Ensalada clásica     | 8.50
Platos Fuertes | Pollo a la brasa | Pollo rostizado      | 12.00
```

---

## 🚀 CÓMO USAR

1. Crea un archivo Excel (.xlsx o .xls)
2. Crea dos hojas con los nombres exactos: `categorias` y `platos`
3. Llena las columnas según el formato indicado
4. Guarda el archivo
5. Súbelo desde el dashboard admin → "Cargar Menú Excel"

---

## 📊 RESULTADO

El script:
- ✅ Crea nuevas categorías si no existen
- ✅ Actualiza categorías existentes (por nombre)
- ✅ Crea nuevos platos si no existen
- ✅ Actualiza platos existentes (por nombre + categoría)
- ✅ Mantiene los IDs de registros existentes

---

## ❌ ERRORES COMUNES

1. **"No module named 'pymysql'"** → Las librerías ya están instaladas ✅
2. **"Hoja no encontrada"** → Verifica que las hojas se llamen exactamente `categorias` y `platos`
3. **"Categoría no existe"** → La categoría del plato no está en la hoja "categorias"
4. **"Columna no encontrada"** → Verifica que los nombres de columnas sean exactos

---

## 📥 DESCARGAR PLANTILLA

Puedes crear tu Excel siguiendo la estructura de arriba, o usa este contenido como guía.
