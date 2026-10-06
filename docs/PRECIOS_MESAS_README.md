# Sistema de Precios de Mesas - Automático

## ✅ Lo que se implementó:

### 1. Campo `precio_reserva` en tabla `mesas`
- Tipo: DECIMAL(10,2)
- Default: $5.00

### 2. Escala de Precios Automática
```
Capacidad 1-2 personas  → $5.00
Capacidad 3-4 personas  → $6.00
Capacidad 5-6 personas  → $8.00
Capacidad 7-10 personas → $10.00
Capacidad 11+ personas  → $15.00
```

### 3. Triggers Automáticos
El sistema actualiza el precio automáticamente cuando:
- ✅ Se crea una nueva mesa
- ✅ Se edita la capacidad de una mesa existente

**No necesitas hacer nada manualmente**, el trigger se ejecuta automáticamente.

## 📊 Mesas Actuales y sus Precios

| Mesa | Capacidad | Precio | Ubicación | Estado |
|------|-----------|--------|-----------|--------|
| B02  | 1-4       | $6.00  | Bar       | Reservada |
| M03  | 1-6       | $8.00  | Interior  | Disponible |
| M04  | 1-6       | $8.00  | Interior  | Disponible |
| B01  | 1-8       | $10.00 | Bar       | Ocupada |
| M02  | 1-8       | $10.00 | Interior  | Disponible |
| T01  | 1-8       | $10.00 | Terraza   | Disponible |
| T02  | 1-10      | $10.00 | Terraza   | Disponible |
| V01  | 1-10      | $10.00 | VIP       | Disponible |
| V02  | 1-12      | $15.00 | VIP       | Disponible |
| M01  | 1-15      | $15.00 | Interior  | Disponible |

## 🔧 Cómo funciona

### Ejemplo 1: Crear mesa nueva
```sql
INSERT INTO mesas (numero_mesa, capacidad_minima, capacidad_maxima, ubicacion)
VALUES ('T03', 1, 6, 'terraza');
```
→ **Resultado**: Se crea con `precio_reserva = 8.00` automáticamente

### Ejemplo 2: Editar capacidad
```sql
UPDATE mesas SET capacidad_maxima = 12 WHERE numero_mesa = 'M01';
```
→ **Resultado**: El precio se actualiza automáticamente a `$15.00`

### Ejemplo 3: Editar otro campo (NO afecta precio)
```sql
UPDATE mesas SET estado = 'disponible' WHERE numero_mesa = 'M01';
```
→ **Resultado**: El precio NO cambia (solo cambia si modificas `capacidad_maxima`)

## 🛠️ Re-ejecutar el script

Si necesitas volver a aplicar los precios o recrear los triggers:

```bash
cd /opt/lampp
./bin/mysql -u crud_proyecto -p12345 -D crud_proyecto < /opt/lampp/htdocs/PRY_PROYECTO/app/api/actualizar_precios_mesas.sql
```

## 📝 Modificar la escala de precios

Edita el archivo: `/opt/lampp/htdocs/PRY_PROYECTO/app/api/actualizar_precios_mesas.sql`

Busca la sección del CASE y modifica los valores:
```sql
CASE
    WHEN NEW.capacidad_maxima <= 2 THEN 5.00    -- Cambia aquí
    WHEN NEW.capacidad_maxima BETWEEN 3 AND 4 THEN 6.00
    -- ... etc
END;
```

Después de modificar, re-ejecuta el script.

## ✨ Ventajas

1. **Automático**: No necesitas calcular precios manualmente
2. **Consistente**: Todos los precios siguen la misma lógica
3. **Flexible**: Fácil de modificar la escala de precios
4. **Sin errores**: Imposible tener precios incorrectos
5. **Transparente**: Siempre sabes por qué una mesa tiene ese precio
