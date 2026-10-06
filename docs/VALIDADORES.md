# Documentación de Validadores del Sistema

## 📁 Carpeta: `/validacion`

Esta carpeta contiene todos los validadores del sistema de reservas del restaurante.

---

## 📋 Validadores Disponibles

### 1. **ValidadorNombres.php**
Valida nombres y apellidos para asegurar que no contengan números ni caracteres inválidos.

#### ✅ Validaciones que realiza:
- ❌ **No permite números** en nombres y apellidos
- ✅ Solo permite letras (incluyendo ñ, acentos)
- ✅ Permite espacios, apóstrofes (') y guiones (-)
- ✅ Longitud mínima: 2 caracteres
- ✅ Longitud máxima: 50 caracteres
- ✅ No permite espacios múltiples

#### 📝 Ejemplo de uso:
```php
require_once 'validacion/ValidadorNombres.php';

// Validar nombre
$resultado = ValidadorNombres::validar("Juan123", "nombre");
// Retorna: ['valido' => false, 'mensaje' => 'El nombre no puede contener números']

// Validar apellido
$resultado = ValidadorNombres::validar("Pérez-García", "apellido");
// Retorna: ['valido' => true, 'mensaje' => 'Apellido válido']

// Limpiar y formatear
$nombreLimpio = ValidadorNombres::limpiar("  juan   carlos  ");
// Retorna: "Juan Carlos"
```

---

### 2. **ValidadorCedula.php**
Valida cédulas ecuatorianas de 10 dígitos con dígito verificador.

#### ✅ Validaciones que realiza:
- ❌ **No permite letras** en la cédula
- ✅ Debe tener exactamente **10 dígitos**
- ✅ Los primeros 2 dígitos deben ser una provincia válida (01-24)
- ✅ Valida el **dígito verificador** (último dígito) con el algoritmo ecuatoriano
- ✅ Verifica que la cédula no esté duplicada en la base de datos

#### 📝 Ejemplo de uso:
```php
require_once 'validacion/ValidadorCedula.php';
require_once 'conexion/db.php';

// Validar formato y dígito verificador
$resultado = ValidadorCedula::validar("1234567890");
// Retorna: ['valido' => true/false, 'mensaje' => '...']

// Verificar duplicado en BD
$resultado = ValidadorCedula::verificarDuplicado("1234567890", $mysqli);
// Retorna: ['disponible' => true/false, 'mensaje' => '...']
```

#### ⚠️ Mensajes de error posibles:
- "La cédula solo debe contener números"
- "La cédula debe tener exactamente 10 dígitos"
- "Los dos primeros dígitos no corresponden a una provincia válida"
- "La cédula no es válida (dígito verificador incorrecto)"
- "La cédula ya está registrada en el sistema"

---

### 3. **ValidadorUsuario.php**
Valida usuarios y correos electrónicos, asegurando unicidad.

#### ✅ Validaciones de Usuario:
- ✅ Longitud mínima: 4 caracteres
- ✅ Longitud máxima: 30 caracteres
- ✅ Solo permite: letras, números, guiones (-) y guiones bajos (_)
- ✅ Verifica que el usuario no esté duplicado

#### ✅ Validaciones de Correo:
- ✅ Formato válido de email
- ✅ Verifica que el correo no esté duplicado

#### 📝 Ejemplo de uso:
```php
require_once 'validacion/ValidadorUsuario.php';
require_once 'conexion/db.php';

// Validar formato de usuario
$resultado = ValidadorUsuario::validarFormato("juan_123");
// Retorna: ['valido' => true, 'mensaje' => 'Usuario válido']

// Verificar disponibilidad
$resultado = ValidadorUsuario::verificarDisponibilidad("juan_123", $mysqli);
// Retorna: ['disponible' => true/false, 'mensaje' => '...']

// Validar correo
$resultado = ValidadorUsuario::validarCorreo("correo@ejemplo.com");
// Retorna: ['valido' => true, 'mensaje' => 'Correo válido']

// Verificar correo duplicado
$resultado = ValidadorUsuario::verificarCorreoDisponible("correo@ejemplo.com", $mysqli);
// Retorna: ['disponible' => true/false, 'mensaje' => '...']
```

---

### 4. **ValidadorReserva.php**
Valida fechas y horas de reservas con restricciones de tiempo.

#### ✅ Validaciones que realiza:
- ❌ **No permite reservas en días pasados** (solo desde hoy en adelante)
- ⏰ **Requiere 2 horas de anticipación** desde la hora actual
- 📅 Valida que el día no esté cerrado según configuración del restaurante
- 🕐 Valida que la hora esté dentro del horario de apertura/cierre

#### 📝 Ejemplo de uso:
```php
require_once 'validacion/ValidadorReserva.php';
require_once 'conexion/db.php';

// Validar solo fecha
$resultado = ValidadorReserva::validarFecha("2025-12-09");
// Retorna: ['valido' => false, 'mensaje' => 'No se pueden hacer reservas para días pasados...']

// Validar anticipación de 2 horas
$resultado = ValidadorReserva::validarHoraAnticipacion("2025-12-11", "14:00:00");
// Retorna: ['valido' => true/false, 'mensaje' => '...']

// Validar todo junto (fecha + hora + anticipación + horario)
$resultado = ValidadorReserva::validarReservaCompleta("2025-12-15", "19:00:00", $mysqli);
// Retorna: ['valido' => true/false, 'mensaje' => '...', 'errores' => [...]]
```

#### ⚠️ Mensajes de error posibles:
- "No se pueden hacer reservas para días pasados. Solo puede reservar desde hoy en adelante"
- "Recuerde que solo puede reservar con al menos 2 horas de anticipación desde la hora actual"
- "El restaurante está cerrado los [día]"
- "La reserva es antes del horario de apertura (HH:MM)"
- "La reserva es después del horario de cierre (HH:MM)"

---

## 🔧 Integración con el Sistema

### Archivo actualizado: `app/registro_cliente.php`
Ya incluye todas las validaciones:
- ✅ Nombre sin números
- ✅ Apellido sin números
- ✅ Cédula de 10 dígitos con validación de dígito verificador
- ✅ Cédula sin duplicados
- ✅ Usuario sin duplicados
- ✅ Correo sin duplicados (si se usa)

### Archivo actualizado: `controllers/ReservaController.php`
Ya incluye validaciones de reserva:
- ✅ No permite reservas en días pasados
- ✅ Requiere 2 horas de anticipación
- ✅ Valida horario del restaurante

---

## 🎯 Flujo de Validación

### Para Registro de Clientes:
1. **Nombre** → ValidadorNombres (sin números)
2. **Apellido** → ValidadorNombres (sin números)
3. **Cédula** → ValidadorCedula (10 dígitos + verificador + sin duplicados)
4. **Usuario** → ValidadorUsuario (formato + sin duplicados)
5. **Correo** → ValidadorUsuario (formato + sin duplicados)

### Para Reservas:
1. **Fecha** → ValidadorReserva (no pasada)
2. **Hora** → ValidadorReserva (2 horas anticipación)
3. **Horario** → ValidadorReserva (día abierto + hora válida)
4. Todo se valida con: `ValidadorReserva::validarReservaCompleta()`

---

## 📌 Notas Importantes

- ✅ Todos los validadores retornan un array con: `['valido' => bool, 'mensaje' => string]`
- ✅ Los mensajes son claros y específicos para el usuario
- ✅ Se integran fácilmente con respuestas JSON del backend
- ✅ Incluyen validaciones tanto de formato como de base de datos
- ✅ El validador de cédula usa el algoritmo oficial ecuatoriano

---

## 🚀 Uso Rápido

```php
// En cualquier archivo PHP que necesite validar:
require_once __DIR__ . '/../validacion/ValidadorNombres.php';
require_once __DIR__ . '/../validacion/ValidadorCedula.php';
require_once __DIR__ . '/../validacion/ValidadorUsuario.php';
require_once __DIR__ . '/../validacion/ValidadorReserva.php';

// Usar directamente:
$resultado = ValidadorNombres::validar($nombre, 'nombre');
if (!$resultado['valido']) {
    echo json_encode(['success' => false, 'message' => $resultado['mensaje']]);
    exit;
}
```

---

**Fecha de creación:** Diciembre 2025  
**Versión:** 1.0  
**Sistema:** Reservas de Restaurante
