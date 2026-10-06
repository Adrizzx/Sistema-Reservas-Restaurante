# 🗃️ Script de Datos de Prueba - Dashboard

## 📋 Archivo: `datos_prueba_dashboard.sql`

Este script limpia todas las reservas y carga datos quemados de prueba para ver el comportamiento del dashboard.

---

## ✅ ¿Qué hace el script?

### 1. **Limpieza Total (TRUNCATE)**
```sql
TRUNCATE TABLE pre_pedidos;
TRUNCATE TABLE notas_consumo;
TRUNCATE TABLE auditoria_reservas;
TRUNCATE TABLE reservas;
```
- Elimina **TODAS** las reservas existentes
- Resetea el `AUTO_INCREMENT` a 1
- Limpia tablas relacionadas (pedidos, notas, auditoría)

### 2. **Resetea los IDs**
```sql
ALTER TABLE reservas AUTO_INCREMENT = 1;
```
- El ID vuelve a empezar desde 1

### 3. **Carga Datos de Prueba**
- **Noviembre 2025:** 131 reservas (todo el mes)
- **Diciembre 1-10, 2025:** 44 reservas
- **Total:** 175 reservas

---

## 🚀 Cómo ejecutar el script

### Opción 1: Desde la terminal de Linux
```bash
cd /opt/lampp/htdocs/PRY_PROYECTO
/opt/lampp/bin/mysql -u root crud_proyecto < sql/datos_prueba_dashboard.sql
```

### Opción 2: Desde phpMyAdmin
1. Abrir phpMyAdmin: http://localhost/phpmyadmin
2. Seleccionar la base de datos `crud_proyecto`
3. Click en la pestaña **SQL**
4. Copiar y pegar todo el contenido de `datos_prueba_dashboard.sql`
5. Click en **Continuar**

### Opción 3: Desde MySQL Workbench
1. Conectar a la base de datos
2. File → Open SQL Script
3. Seleccionar `datos_prueba_dashboard.sql`
4. Ejecutar (⚡ o Ctrl+Shift+Enter)

---

## 📊 Datos Generados

### **Noviembre 2025**
- **131 reservas** distribuidas en todo el mes
- Fines de semana con más reservas (viernes y sábados: 9-10 reservas)
- Días normales: 3-6 reservas
- **Estado:** Todas `finalizada`

### **Diciembre 2025 (1-10)**
- **40 reservas finalizadas** (días 1-9)
- **4 reservas confirmadas** (día 10 = HOY)
- Distribución realista por día

### **Estadísticas**
```
Total reservas: 175
Total personas atendidas: 1,054
Promedio personas/reserva: 6
Mesas más usadas: M01, M02, T01, V02
```

---

## 🎯 Para qué sirve esto

### ✅ Probar el Dashboard
- Ver gráficos con datos reales del último mes
- Verificar tendencias y estadísticas
- Analizar ocupación por día/hora

### ✅ Probar Reportes
- Reportes mensuales con datos completos
- Comparativas entre meses
- Análisis de ocupación

### ✅ Probar Validaciones
- Ver cómo se manejan las reservas finalizadas
- Probar estados de reservas
- Verificar disponibilidad de mesas

---

## 📈 Distribución de Datos

### Por Día de la Semana
- **Viernes y Sábado:** 9-10 reservas (días pico)
- **Domingo-Jueves:** 3-6 reservas (días normales)

### Por Estado
- **Finalizada:** 171 reservas (pasadas)
- **Confirmada:** 4 reservas (hoy, 10 de diciembre)

### Por Mesa
Todas las mesas (M01-M10) tienen reservas distribuidas:
- Interior: 40% de reservas
- Terraza: 25% de reservas
- VIP: 20% de reservas
- Bar: 15% de reservas

---

## ⚠️ IMPORTANTE

### 🔴 Este script borra TODO
```sql
TRUNCATE TABLE reservas;
```
- **NO** se puede deshacer
- Se pierden todas las reservas actuales
- Use solo en desarrollo/pruebas

### ✅ Respaldo antes de ejecutar
```bash
# Hacer backup antes
/opt/lampp/bin/mysqldump -u root crud_proyecto reservas > backup_reservas_$(date +%Y%m%d).sql

# Ejecutar el script
/opt/lampp/bin/mysql -u root crud_proyecto < sql/datos_prueba_dashboard.sql

# Si algo sale mal, restaurar:
/opt/lampp/bin/mysql -u root crud_proyecto < backup_reservas_YYYYMMDD.sql
```

---

## 🔍 Verificar que funcionó

Después de ejecutar, verás este resumen:
```
====== RESUMEN DE DATOS CARGADOS ======
mes       total_reservas  finalizadas  confirmadas
2025-11   131            131          0
2025-12   44             40           4

✅ SCRIPT COMPLETADO EXITOSAMENTE
Total reservas: 175
Primera: 2025-11-01
Última: 2025-12-10
```

---

## 🎨 Datos Realistas

Los datos incluyen:
- ✅ IDs de clientes válidos (1-11)
- ✅ IDs de mesas válidos (1-10)
- ✅ Fechas consecutivas sin saltos
- ✅ Horas de operación realistas (17:00-21:30)
- ✅ Número de personas según capacidad de mesa
- ✅ Estados correctos según la fecha
- ✅ Más reservas en fines de semana

---

## 📝 Notas

1. **El día 10 de diciembre tiene 4 reservas `confirmadas`** para simular el día actual
2. **Todas las fechas anteriores están `finalizadas`** como debe ser
3. **Las mesas del día 10 están marcadas como `reservadas`** en la tabla mesas
4. **Los IDs empiezan desde 1** después del TRUNCATE

---

## 🔄 Uso Recomendado

```bash
# 1. Hacer backup
/opt/lampp/bin/mysqldump -u root crud_proyecto reservas > backup.sql

# 2. Ejecutar script de prueba
/opt/lampp/bin/mysql -u root crud_proyecto < sql/datos_prueba_dashboard.sql

# 3. Probar el dashboard
# Abrir: http://localhost/PRY_PROYECTO/admin.php

# 4. Cuando termines de probar, restaurar si quieres:
/opt/lampp/bin/mysql -u root crud_proyecto < backup.sql
```

---

**Fecha de creación:** Diciembre 10, 2025  
**Total registros:** 175 reservas  
**Período:** Nov 1 - Dic 10, 2025  
**Uso:** Desarrollo y pruebas
