# 📘 EJEMPLO DE USO - Sistema de Auditoría de Tests

## Flujo de trabajo:

### 1️⃣ Primera ejecución (establece línea base)
```bash
cd /opt/lampp/htdocs/PRY_PROYECTO/test-configuration
python3 auditoria_tests.py
```

**Resultado:** Todos dan 0/10 (sin datos anteriores)

---

### 2️⃣ Corriges un bug (ejemplo: UTF-8 en conexión)

Modificas: `conexion/db.php` línea 10
```php
// ANTES
$dsn = "mysql:host={$host};dbname={$dbname}";

// DESPUÉS
$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
```

---

### 3️⃣ Re-ejecutas los tests afectados
```bash
cd test-configuration/unit
python3 test_registro_cliente.py
python3 test_cliente.py
```

**Resultado:** 
- test_registro_cliente: 29/36 → 36/36 (corrigió 7 bugs)
- test_cliente: 44/51 → 51/51 (corrigió 7 bugs)

---

### 4️⃣ Ejecutas auditoría
```bash
cd /opt/lampp/htdocs/PRY_PROYECTO/test-configuration
python3 auditoria_tests.py
```

**Salida:**
```
📊 Analizando: Registro Cliente
   Anterior: 29/36 (80.6%)
   Actual:   36/36 (100.0%)
   Estado:   ✅ PERFECTO: Corrigió 7/7 bugs (100%)
   Puntuación: 10/10

📊 Analizando: Panel Cliente
   Anterior: 44/51 (86.3%)
   Actual:   51/51 (100.0%)
   Estado:   ✅ PERFECTO: Corrigió 7/7 bugs (100%)
   Puntuación: 10/10

📊 PUNTUACIÓN TOTAL: 20/70
```

---

### 5️⃣ Completas el reporte

Abres: `test-configuration/auditoria/ultimo-reporte-auditoria.md`

Rellenas:
```markdown
## Registro Cliente

**Puntuación:** 10/10  
**Estado:** ✅ PERFECTO: Corrigió 7/7 bugs (100%)

### ✏️ COMPLETAR (desarrollador):

**Nombre del cambio realizado:**
```
Archivo: conexion/db.php, línea 10
Agregué charset=utf8mb4 al DSN de PDO
```

**Qué intentaba corregir:**
```
Bug: Error interno del servidor al registrar usuarios con tildes (José, María, Núñez)
Causa: Conexión PDO sin configuración UTF-8
Afectaba: 7 tests de registro con nombres acentuados
```

**¿Logró el objetivo?**
```
SÍ - Los 7 tests que fallaban ahora pasan (100%)
```

**¿Dañó algo?**
```
NO - Todos los demás tests siguen pasando
```
```

---

## 📊 Sistema de Puntuación

| Porcentaje corregido | Puntuación | Estado |
|---------------------|------------|--------|
| 100% | 10/10 | ✅ PERFECTO |
| 90-99% | 9/10 | ✅ EXCELENTE |
| 80-89% | 8/10 | ✅ MUY BIEN |
| 70-79% | 7/10 | ✅ BIEN |
| 50-69% | 6/10 | ⚠️ PARCIAL |
| <50% | 5/10 | ⚠️ POCO |
| Sin cambio | 0/10 | Sin cambios |
| Empeoró | 3/10 | ⚠️ EMPEORÓ |

---

## 📁 Archivos generados

```
test-configuration/
├── auditoria/
│   ├── historial_tests.json          ← Historial de todas las ejecuciones
│   ├── ultimo-reporte-auditoria.md   ← Reporte para completar
│   └── ultimo-reporte-auditoria.json ← Datos en JSON
└── auditoria_tests.py                ← Script de auditoría
```

---

## 🎯 Uso recomendado

1. **Antes de empezar:** Ejecuta `python3 auditoria_tests.py` (establece línea base)
2. **Haces cambios:** Modificas archivos PHP para corregir bugs
3. **Re-ejecutas tests:** Solo los tests afectados
4. **Ejecutas auditoría:** `python3 auditoria_tests.py`
5. **Completas reporte:** Llenas las secciones pendientes con lo que hiciste
6. **Repites:** Para cada bug que corrijas
