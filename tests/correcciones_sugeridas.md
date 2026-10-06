# 🔧 CORRECCIONES SUGERIDAS PARA VULNERABILIDADES

## 📋 RESUMEN
Este documento contiene las correcciones necesarias para los problemas encontrados en las pruebas de límites.

---

## 1. VALIDACIÓN DE CAPACIDAD DE MESAS

### ❌ Problema Actual
```php
// app/agregar_mesa.php - NO HAY VALIDACIÓN DE LÍMITES
$capacidad_maxima = $data['capacidad_maxima'] ?? null;
```

### ✅ Solución Recomendada
```php
// Agregar después de la línea 18 en app/agregar_mesa.php

// VALIDAR CAPACIDAD MÁXIMA
if ($capacidad_maxima < 1 || $capacidad_maxima > 50) {
    throw new Exception('La capacidad máxima debe estar entre 1 y 50 personas');
}

// VALIDAR CAPACIDAD MÍNIMA
if ($capacidad_minima < 1 || $capacidad_minima > $capacidad_maxima) {
    throw new Exception('La capacidad mínima debe ser entre 1 y la capacidad máxima');
}

// VALIDAR LONGITUD DE NÚMERO DE MESA
if (strlen($numero_mesa) > 20) {
    throw new Exception('El número de mesa no puede exceder 20 caracteres');
}
```

---

## 2. VALIDACIÓN DE NÚMERO DE MESA

### ❌ Problema Actual
No hay sanitización ni validación de caracteres especiales

### ✅ Solución
```php
// Agregar validación de formato
if (!preg_match('/^[A-Z0-9\-]+$/i', $numero_mesa)) {
    throw new Exception('El número de mesa solo puede contener letras, números y guiones');
}

// Sanitizar para prevenir XSS
$numero_mesa = htmlspecialchars($numero_mesa, ENT_QUOTES, 'UTF-8');
```

---

## 3. LIMITAR TOTAL DE MESAS EN EL SISTEMA

### ✅ Solución
```php
// Agregar antes de insertar en app/agregar_mesa.php

// Verificar límite total de mesas
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM mesas");
$stmt->execute();
$count = $stmt->fetch()['total'];

if ($count >= 100) {
    throw new Exception('Se ha alcanzado el límite máximo de 100 mesas en el sistema');
}
```

---

## 4. VALIDACIÓN DE CAMPOS DE TEXTO (NOMBRES, APELLIDOS)

### ✅ Solución para validacion/ValidadorNombres.php
```php
// Agregar límite de longitud
public static function validar($valor, $campo = 'campo') {
    // ... código existente ...
    
    // AGREGAR ESTA VALIDACIÓN
    if (strlen($valor) > 50) {
        return [
            'valido' => false,
            'mensaje' => "El $campo no puede exceder 50 caracteres"
        ];
    }
    
    // ... resto del código ...
}
```

---

## 5. VALIDACIÓN DE TELÉFONO

### ✅ Solución
```php
// Agregar en app/registro_cliente.php después de validar usuario

// VALIDAR LONGITUD DE TELÉFONO
if (strlen($telefono) > 15) {
    echo json_encode(['success' => false, 'message' => 'El teléfono no puede exceder 15 dígitos']);
    exit;
}

// VALIDAR FORMATO DE TELÉFONO (solo números, +, -, espacios)
if (!preg_match('/^[\d\s\+\-\(\)]+$/', $telefono)) {
    echo json_encode(['success' => false, 'message' => 'El teléfono contiene caracteres inválidos']);
    exit;
}
```

---

## 6. RATE LIMITING (PREVENIR SPAM)

### ✅ Crear archivo: utils/RateLimiter.php
```php
<?php
class RateLimiter {
    private static function getClientIP() {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public static function check($action, $maxAttempts = 10, $timeWindow = 60) {
        session_start();
        $ip = self::getClientIP();
        $key = $action . '_' . $ip;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }
        
        $data = $_SESSION[$key];
        
        // Resetear si pasó el tiempo
        if (time() - $data['time'] > $timeWindow) {
            $_SESSION[$key] = ['count' => 1, 'time' => time()];
            return true;
        }
        
        // Verificar límite
        if ($data['count'] >= $maxAttempts) {
            return false;
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }
}
```

### Usar en archivos críticos:
```php
// Al inicio de agregar_mesa.php, registro_cliente.php, etc.
require_once '../utils/RateLimiter.php';

if (!RateLimiter::check('agregar_mesa', 10, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Demasiadas solicitudes. Espere un momento.']);
    exit;
}
```

---

## 7. VALIDACIÓN A NIVEL DE BASE DE DATOS

### ✅ Actualizar tabla mesas
```sql
ALTER TABLE mesas 
  MODIFY COLUMN capacidad_maxima INT NOT NULL CHECK (capacidad_maxima BETWEEN 1 AND 50),
  MODIFY COLUMN capacidad_minima INT NOT NULL CHECK (capacidad_minima BETWEEN 1 AND 50),
  MODIFY COLUMN numero_mesa VARCHAR(20) NOT NULL;
```

---

## 📊 PRIORIDAD DE IMPLEMENTACIÓN

### 🔴 CRÍTICO (Implementar inmediatamente)
1. Validación de capacidad de mesas (1-50)
2. Sanitización de inputs (XSS/SQL Injection)
3. Rate Limiting

### 🟡 IMPORTANTE (Implementar pronto)
4. Límite total de mesas en sistema
5. Validación de longitud de campos
6. Validación a nivel de BD

### 🟢 RECOMENDADO (Mejora general)
7. Logs de intentos sospechosos
8. Alertas de actividad anormal

---

## 🧪 VERIFICAR DESPUÉS DE CORRECCIONES

Ejecutar nuevamente los tests:
```bash
php tests/test_limites_sistema.php
php tests/test_limites_clientes.php
```

Todas las vulnerabilidades deberían mostrar ✅ PROTEGIDO.
