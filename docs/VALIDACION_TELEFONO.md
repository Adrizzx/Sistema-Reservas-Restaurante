# Validación de Números de Teléfono/Celular

## Descripción
El sistema ahora incluye validación estricta para números de teléfono/celular, garantizando que todos los números registrados tengan exactamente 10 dígitos y cumplan con el formato de celulares de Ecuador.

## Archivo Validador
**Ubicación:** `/validacion/ValidadorTelefono.php`

## Reglas de Validación

### 1. Campo Requerido
- El número de celular no puede estar vacío

### 2. Solo Dígitos
- Solo se permiten números (0-9)
- Se eliminan automáticamente espacios y guiones durante la limpieza

### 3. Exactamente 10 Dígitos
- El número debe tener **exactamente 10 dígitos**
- No se permiten números con más o menos dígitos

### 4. Formato Ecuador
- El número debe comenzar con **09** (formato de celulares en Ecuador)
- Ejemplos válidos:
  - `0987654321`
  - `0991234567`
  - `0998765432`

## Métodos Disponibles

### `ValidadorTelefono::validar($telefono)`
Valida que el número de teléfono cumpla con todas las reglas.

**Parámetros:**
- `$telefono` (string): Número de teléfono a validar

**Retorna:**
```php
[
    'valido' => bool,    // true si es válido, false si no
    'mensaje' => string  // Mensaje descriptivo del resultado
]
```

**Ejemplo:**
```php
$resultado = ValidadorTelefono::validar('0987654321');
if (!$resultado['valido']) {
    echo $resultado['mensaje']; // Muestra el error
}
```

### `ValidadorTelefono::limpiar($telefono)`
Elimina espacios y guiones del número de teléfono.

**Parámetros:**
- `$telefono` (string): Teléfono a limpiar

**Retorna:**
- (string): Número limpio con solo dígitos

**Ejemplo:**
```php
$limpio = ValidadorTelefono::limpiar('098-765-4321');
// Resultado: "0987654321"
```

### `ValidadorTelefono::formatear($telefono)`
Formatea un número de teléfono para visualización (09XX-XXX-XXX).

**Parámetros:**
- `$telefono` (string): Teléfono a formatear

**Retorna:**
- (string): Número formateado

**Ejemplo:**
```php
$formateado = ValidadorTelefono::formatear('0987654321');
// Resultado: "0987-654-321"
```

## Archivos Actualizados

Los siguientes archivos han sido actualizados para incluir la validación de teléfono:

### 1. `/app/registro_cliente_simple.php`
- ✅ Requiere el validador
- ✅ Valida el teléfono antes de registrar
- ✅ Limpia el teléfono antes de guardar en BD

### 2. `/app/registro_cliente.php`
- ✅ Requiere el validador
- ✅ Valida el teléfono antes de registrar
- ✅ Limpia el teléfono antes de guardar en BD

## Mensajes de Error

El validador puede retornar los siguientes mensajes de error:

| Error | Mensaje |
|-------|---------|
| Campo vacío | "El número de celular es requerido" |
| Contiene caracteres no numéricos | "El número de celular solo puede contener dígitos" |
| Longitud incorrecta | "El número de celular debe tener exactamente 10 dígitos" |
| No comienza con 09 | "El número de celular debe comenzar con 09" |

## Uso en Formularios

### Frontend (HTML)
```html
<input 
    type="tel" 
    name="telefono" 
    placeholder="0987654321"
    pattern="[0-9]{10}"
    maxlength="10"
    required
>
```

### Backend (PHP)
```php
require_once '../validacion/ValidadorTelefono.php';

$telefono = trim($_POST['telefono'] ?? '');

// Validar
$validacion = ValidadorTelefono::validar($telefono);
if (!$validacion['valido']) {
    echo json_encode([
        'success' => false, 
        'message' => $validacion['mensaje']
    ]);
    exit;
}

// Limpiar antes de guardar
$telefono = ValidadorTelefono::limpiar($telefono);

// Guardar en base de datos...
```

## Pruebas

### Casos Válidos ✅
- `0987654321` → Válido
- `0991234567` → Válido
- `098 765 4321` → Válido (se limpia automáticamente)
- `098-765-4321` → Válido (se limpia automáticamente)

### Casos Inválidos ❌
- `987654321` → Inválido (solo 9 dígitos)
- `09876543210` → Inválido (11 dígitos)
- `1234567890` → Inválido (no comienza con 09)
- `0887654321` → Inválido (no comienza con 09)
- `09876abc21` → Inválido (contiene letras)
- `` (vacío) → Inválido (campo requerido)

## Integración con Otros Sistemas

Si se utilizan los números de teléfono para notificaciones por WhatsApp u otros servicios, asegúrate de formatearlos según los requisitos del servicio:

```php
// Para WhatsApp con Twilio (formato internacional)
$telefonoLimpio = ValidadorTelefono::limpiar($telefono);
$telefonoInternacional = '+593' . substr($telefonoLimpio, 1);
// Resultado: +593987654321
```

## Notas Adicionales

1. **Formato de Ecuador**: El validador está configurado específicamente para números de celular de Ecuador (que comienzan con 09).

2. **Limpieza Automática**: El método `limpiar()` debe usarse antes de guardar en la base de datos para asegurar que solo se almacenan dígitos.

3. **Compatibilidad**: El validador es compatible con los otros validadores del sistema (`ValidadorNombres`, `ValidadorCedula`, `ValidadorUsuario`).

4. **Extensibilidad**: Si en el futuro se necesita validar números de otros países, se puede modificar el método `validar()` para aceptar un parámetro de país.
