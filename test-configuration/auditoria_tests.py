#!/usr/bin/env python3
"""
SISTEMA DE AUDITORÍA DE TESTS - Simplificado
Compara resultados y genera pistas por grupo.
Usuario solo pone puntuación y descripción de cambios.
"""

import json
import os
from datetime import datetime
from pathlib import Path

# Directorios
UNIT_REPORTES = Path(__file__).parent / "unit" / "reportes"
HISTORIAL_FILE = Path(__file__).parent / "auditoria" / "historial_tests.json"

# Grupos y sus archivos
GRUPOS = {
    'admin': {
        'archivos': ['ultimo-resultado-admin.json'],
        'nombre': 'Panel Admin',
        'carpeta_pistas': UNIT_REPORTES / 'admin' / 'pistas-auditoria'
    },
    'cliente': {
        'archivos': [
            'ultimo-resultado-login-cliente.json',
            'ultimo-resultado-registro-cliente.json',
            'ultimo-resultado-cliente.json'
        ],
        'nombre': 'Panel Cliente',
        'carpeta_pistas': UNIT_REPORTES / 'cliente' / 'pistas-auditoria'
    },
    'mesas': {
        'archivos': ['ultimo-resultado-mesas.json'],
        'nombre': 'Gestión Mesas',
        'carpeta_pistas': UNIT_REPORTES / 'mesas' / 'pistas-auditoria'
    },
    'reservas': {
        'archivos': ['ultimo-resultado-reservas-mesas.json'],
        'nombre': 'Reservas',
        'carpeta_pistas': UNIT_REPORTES / 'reservas' / 'pistas-auditoria'
    },
    'validador': {
        'archivos': ['ultimo-resultado-validador-nombres.json'],
        'nombre': 'Validador Nombres',
        'carpeta_pistas': UNIT_REPORTES / 'validador' / 'pistas-auditoria'
    }
}


def cargar_historial():
    """Carga el historial de tests anteriores"""
    if HISTORIAL_FILE.exists():
        with open(HISTORIAL_FILE, 'r', encoding='utf-8') as f:
            return json.load(f)
    return {}


def guardar_historial(historial):
    """Guarda el historial actualizado"""
    with open(HISTORIAL_FILE, 'w', encoding='utf-8') as f:
        json.dump(historial, f, indent=2, ensure_ascii=False)


def leer_resultado_test(nombre_archivo):
    """Lee un archivo de resultado de test desde reportes/ raíz"""
    archivo = UNIT_REPORTES / nombre_archivo
    if not archivo.exists():
        # Buscar en subcarpetas
        for grupo_info in GRUPOS.values():
            carpeta = grupo_info['carpeta_pistas'].parent
            alt_path = carpeta / nombre_archivo
            if alt_path.exists():
                archivo = alt_path
                break
        
        if not archivo.exists():
            return None
    
    with open(archivo, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    # Detectar formato del JSON
    if isinstance(data, dict) and 'resumen' in data:
        # Formato nuevo (test_admin.py actualizado)
        # {
        #   "resumen": {"total": 150, "pasados": 135, ...},
        #   "por_panel": {...},
        #   "resultados": [...],
        #   "bugs_detectados": [...]
        # }
        return {
            'total': data['resumen']['total'],
            'pasados': data['resumen']['pasados'],
            'fallados': data['resumen']['fallados'],
            'porcentaje': data['resumen']['porcentaje'],
            'tests': data['resultados']
        }
    elif isinstance(data, list):
        # Formato antiguo (lista simple de tests)
        total = len(data)
        pasados = sum(1 for t in data if t.get('paso', False))
        fallados = total - pasados
        porcentaje = (pasados / total * 100) if total > 0 else 0
        
        return {
            'total': total,
            'pasados': pasados,
            'fallados': fallados,
            'porcentaje': round(porcentaje, 1),
            'tests': data
        }
    else:
        # Intentar leer como formato antiguo por defecto
        total = len(data)
        pasados = sum(1 for t in data if t.get('paso', False))
        fallados = total - pasados
        porcentaje = (pasados / total * 100) if total > 0 else 0
        
        return {
            'total': total,
            'pasados': pasados,
            'fallados': fallados,
            'porcentaje': round(porcentaje, 1),
            'tests': data
        }


def calcular_puntuacion(anterior, actual):
    """
    Calcula puntuación según cambios:
    - Dañó algo (empeoró): 3
    - No hizo nada (igual): 0
    - Mejoró parcialmente: 6-9 según porcentaje
    - Corrigió todo: 10
    - Dañó todo: 0
    """
    if anterior is None:
        return 0, "Sin datos anteriores", 0
    
    if actual is None:
        return 0, "Sin datos actuales", 0
    
    pasados_antes = anterior['pasados']
    pasados_ahora = actual['pasados']
    fallados_antes = anterior['fallados']
    fallados_ahora = actual['fallados']
    
    # Caso 1: Empeoró (tiene más tests fallando)
    if pasados_ahora < pasados_antes:
        tests_dañados = pasados_antes - pasados_ahora
        return 3, f"⚠️ EMPEORÓ: {tests_dañados} test(s) ahora fallan", -tests_dañados
    
    # Caso 2: No cambió nada
    if pasados_ahora == pasados_antes:
        return 0, "Sin cambios", 0
    
    # Caso 3: Mejoró (corrigió algunos tests)
    tests_corregidos = pasados_ahora - pasados_antes
    porcentaje_correccion = 0
    
    if fallados_antes > 0:
        porcentaje_correccion = (tests_corregidos / fallados_antes) * 100
    
    # Calcular puntuación según porcentaje de corrección
    if porcentaje_correccion >= 100:
        puntuacion = 10
        estado = f"✅ PERFECTO: Corrigió {tests_corregidos}/{fallados_antes} bugs (100%)"
    elif porcentaje_correccion >= 90:
        puntuacion = 9
        estado = f"✅ EXCELENTE: Corrigió {tests_corregidos}/{fallados_antes} bugs (90%+)"
    elif porcentaje_correccion >= 80:
        puntuacion = 8
        estado = f"✅ MUY BIEN: Corrigió {tests_corregidos}/{fallados_antes} bugs (80%+)"
    elif porcentaje_correccion >= 70:
        puntuacion = 7
        estado = f"✅ BIEN: Corrigió {tests_corregidos}/{fallados_antes} bugs (70%+)"
    elif porcentaje_correccion >= 50:
        puntuacion = 6
        estado = f"⚠️ PARCIAL: Corrigió {tests_corregidos}/{fallados_antes} bugs (50%+)"
    else:
        puntuacion = 5
        estado = f"⚠️ POCO: Corrigió {tests_corregidos}/{fallados_antes} bugs (<50%)"
    
    return puntuacion, estado, tests_corregidos


def generar_reporte_auditoria():
    """Genera pistas de auditoría por grupo"""
    
    historial = cargar_historial()
    fecha_actual = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    
    print("\n" + "="*80)
    print("🔍 AUDITORÍA POR GRUPO")
    print("="*80)
    print(f"Fecha: {fecha_actual}\n")
    
    # Procesar cada grupo
    for grupo_id, grupo_info in GRUPOS.items():
        nombre_grupo = grupo_info['nombre']
        carpeta_pistas = grupo_info['carpeta_pistas']
        carpeta_pistas.mkdir(parents=True, exist_ok=True)
        
        print(f"\n📊 {nombre_grupo}")
        print("-" * 80)
        
        # Combinar resultados de todos los archivos del grupo
        total_tests = 0
        total_pasados = 0
        total_fallados = 0
        todos_tests = []
        
        for archivo in grupo_info['archivos']:
            resultado = leer_resultado_test(archivo)
            if resultado:
                total_tests += resultado['total']
                total_pasados += resultado['pasados']
                total_fallados += resultado['fallados']
                todos_tests.extend(resultado['tests'])
        
        if total_tests == 0:
            print(f"   ⚠️ No hay datos")
            continue
        
        porcentaje_actual = round((total_pasados / total_tests) * 100, 1)
        
        # Datos actuales con tests completos
        actual = {
            'total': total_tests,
            'pasados': total_pasados,
            'fallados': total_fallados,
            'porcentaje': porcentaje_actual,
            'tests': todos_tests
        }
        
        # Obtener datos anteriores del grupo
        hist_key = f"grupo_{grupo_id}"
        anterior = historial.get(hist_key)
        
        # Mostrar comparación
        if anterior:
            print(f"   Anterior: {anterior['pasados']}/{anterior['total']} ({anterior['porcentaje']}%)")
            print(f"   Actual:   {total_pasados}/{total_tests} ({porcentaje_actual}%)")
            
            cambio = total_pasados - anterior['pasados']
            if cambio > 0:
                print(f"   Cambio:   ✅ +{cambio} tests corregidos")
            elif cambio < 0:
                print(f"   Cambio:   ⚠️ {cambio} tests dañados")
            else:
                print(f"   Cambio:   Sin cambios")
        else:
            print(f"   Primera ejecución: {total_pasados}/{total_tests} ({porcentaje_actual}%)")
        
        # Generar pista de auditoría para el grupo
        generar_pista_grupo(
            grupo_id=grupo_id,
            nombre_grupo=nombre_grupo,
            carpeta_pistas=carpeta_pistas,
            anterior=anterior,
            actual=actual,
            fecha=fecha_actual
        )
        
        # Actualizar historial (guardar tests completos para próxima comparación)
        historial[hist_key] = actual
    
    # Guardar historial
    guardar_historial(historial)
    
    print("\n" + "="*80)
    print("✅ Pistas generadas en:")
    for grupo_info in GRUPOS.values():
        print(f"   📁 {grupo_info['carpeta_pistas']}")
    print("="*80 + "\n")


def generar_pista_grupo(grupo_id, nombre_grupo, carpeta_pistas, anterior, actual, fecha):
    """Genera archivo de pista para un grupo específico"""
    
    # Nombre del archivo de pista
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    archivo_pista = carpeta_pistas / f"pista_{timestamp}.md"
    
    # CASO 1: Primera ejecución - no hay datos anteriores
    if not anterior:
        contenido = f"""# 🔍 PISTA DE AUDITORÍA - {nombre_grupo}

**Fecha:** {fecha}  
**Grupo:** {grupo_id}

---

## 📊 Primera Ejecución

| Métrica | Valor |
|---------|-------|
| Tests totales | {actual['total']} |
| Tests pasados | {actual['pasados']} |
| Tests fallados | {actual['fallados']} |
| Porcentaje | {actual['porcentaje']}% |

**Estado:** Primera ejecución - no hay datos anteriores para comparar  
**Puntuación:** N/A

---

## ℹ️ Información

Esta es la primera vez que se ejecutan estos tests. Los resultados actuales servirán como **línea base** para futuras comparaciones.

En la próxima ejecución de auditoría, el sistema comparará automáticamente y mostrará:
- ✅ Tests corregidos (que pasaron de fallar a pasar)
- ❌ Tests dañados (que pasaron de pasar a fallar)
- 🎯 Puntuación sugerida basada en mejoras

---

## 📝 Siguiente Paso

1. Modifica el código PHP para corregir bugs
2. Vuelve a ejecutar: `python3 test_{grupo_id}.py`
3. Ejecuta: `python3 auditoria_tests.py`
4. Verás la comparación automática aquí

---

## 📌 Notas

- Ver bugs detectados: `reportes/{grupo_id}/reportes/DOC_*.md`
- Este resultado se guardó en: `historial_tests.json`
"""
        with open(archivo_pista, 'w', encoding='utf-8') as f:
            f.write(contenido)
        
        print(f"   📄 Pista generada: {archivo_pista.name} (primera ejecución)")
        return
    
    # CASO 2: Ya hay datos anteriores - comparar cambios
    if anterior:
        # DETECTAR SI CAMBIÓ EL NÚMERO DE TESTS (refactoring completo)
        cambio_total_tests = actual['total'] - anterior['total']
        
        if abs(cambio_total_tests) > 10:
            # Cambio significativo en número de tests = refactoring completo
            cambio = 0  # Inicializar para evitar UnboundLocalError
            cambio_fallados = actual['fallados'] - anterior['fallados']
            cambio_pct = actual['porcentaje'] - anterior['porcentaje']
            
            if cambio_fallados > 0:
                # Más tests fallan ahora
                puntuacion_sugerida = 0
                estado_auto = f"🔄 REFACTORING: {actual['total']} tests (antes {anterior['total']}). " \
                             f"Ahora {actual['fallados']} fallan (antes {anterior['fallados']}). " \
                             f"⚠️ Empeoró: +{cambio_fallados} tests fallando"
            elif cambio_fallados < 0:
                # Menos tests fallan ahora
                puntuacion_sugerida = 7
                estado_auto = f"🔄 REFACTORING: {actual['total']} tests (antes {anterior['total']}). " \
                             f"Ahora {actual['fallados']} fallan (antes {anterior['fallados']}). " \
                             f"✅ Mejoró: {abs(cambio_fallados)} tests menos fallando"
            else:
                # Mismo número de tests fallan
                if cambio_pct > 0:
                    puntuacion_sugerida = 5
                    estado_auto = f"🔄 REFACTORING: {actual['total']} tests (antes {anterior['total']}). " \
                                 f"Porcentaje mejoró: {anterior['porcentaje']}% → {actual['porcentaje']}%"
                else:
                    puntuacion_sugerida = 0
                    estado_auto = f"🔄 REFACTORING: {actual['total']} tests (antes {anterior['total']}). " \
                                 f"Estado similar"
            
            tests_corregidos = []
            tests_danados = []
        else:
            # Mismo número de tests aprox = comparación normal
            cambio = actual['pasados'] - anterior['pasados']
            cambio_pct = actual['porcentaje'] - anterior['porcentaje']
            
            # Analizar qué tests cambiaron
            tests_corregidos = []
            tests_danados = []
            
            # Comparar test por test
            if 'tests' in actual and 'tests' in anterior:
                # Crear mapas por nombre de test
                tests_ant = {t['nombre']: t for t in anterior['tests']}
                tests_act = {t['nombre']: t for t in actual['tests']}
                
                for nombre, test_act in tests_act.items():
                    if nombre in tests_ant:
                        test_ant = tests_ant[nombre]
                        # Test que estaba fallando ahora pasa
                        if not test_ant.get('paso', False) and test_act.get('paso', False):
                            tests_corregidos.append(nombre)
                        # Test que estaba pasando ahora falla
                        elif test_ant.get('paso', False) and not test_act.get('paso', False):
                            tests_danados.append(nombre)
            
            # Calcular puntuación sugerida
            if tests_danados:
                puntuacion_sugerida = 3
                estado_auto = f"⚠️ EMPEORÓ: {len(tests_danados)} tests ahora fallan"
            elif cambio < 0:
                puntuacion_sugerida = 3
                estado_auto = f"⚠️ EMPEORÓ: {abs(cambio)} tests menos pasando"
            elif cambio == 0 and not tests_corregidos:
                puntuacion_sugerida = 0
                estado_auto = "Sin cambios"
            elif tests_corregidos:
                # Calcular porcentaje de corrección
                if anterior['fallados'] > 0:
                    pct_correccion = (len(tests_corregidos) / anterior['fallados']) * 100
                    if pct_correccion >= 100:
                        puntuacion_sugerida = 10
                        estado_auto = f"✅ PERFECTO: Corrigió {len(tests_corregidos)}/{anterior['fallados']} bugs (100%)"
                    elif pct_correccion >= 90:
                        puntuacion_sugerida = 9
                        estado_auto = f"✅ EXCELENTE: Corrigió {len(tests_corregidos)}/{anterior['fallados']} bugs (90%+)"
                    elif pct_correccion >= 80:
                        puntuacion_sugerida = 8
                        estado_auto = f"✅ MUY BIEN: Corrigió {len(tests_corregidos)}/{anterior['fallados']} bugs (80%+)"
                    elif pct_correccion >= 70:
                        puntuacion_sugerida = 7
                        estado_auto = f"✅ BIEN: Corrigió {len(tests_corregidos)}/{anterior['fallados']} bugs (70%+)"
                    elif pct_correccion >= 50:
                        puntuacion_sugerida = 6
                        estado_auto = f"⚠️ PARCIAL: Corrigió {len(tests_corregidos)}/{anterior['fallados']} bugs (50%+)"
                    else:
                        puntuacion_sugerida = 5
                        estado_auto = f"⚠️ POCO: Corrigió {len(tests_corregidos)}/{anterior['fallados']} bugs (<50%)"
                else:
                    puntuacion_sugerida = 0
                    estado_auto = "Sin bugs anteriores para corregir"
            else:
                puntuacion_sugerida = 5
                estado_auto = f"Mejoró {cambio} tests pero no detectados individualmente"
    else:
        cambio = 0
        cambio_pct = 0
        tests_corregidos = []
        tests_danados = []
        puntuacion_sugerida = 0
        estado_auto = "Primera ejecución (sin datos anteriores)"
    
    # Generar sección final según si hubo cambios o no
    if cambio == 0 and not tests_corregidos and not tests_danados:
        seccion_final = f"""---

## 🎯 RESULTADO FINAL

**Estado:** Sin cambios  
**Puntuación:** 0/10  
**Archivos modificados:** Ninguno

No se detectaron cambios en los tests. El código no fue modificado o los cambios no afectaron los resultados de las pruebas.

---

## 📝 SOLO PON TU NÚMERO (0-10):

```
0
```

*(Ya está completado - sin cambios = 0)*
"""
    else:
        # Hubo cambios - generar análisis detallado
        archivos_estimados = "Sin determinar (ver cambios en git)"
        if tests_corregidos:
            archivos_estimados = "Posibles archivos modificados para corregir estos tests"
        
        seccion_final = f"""---

## 🎯 RESULTADO FINAL

**Tests corregidos:** {len(tests_corregidos)}  
**Tests dañados:** {len(tests_danados)}  
**Puntuación sugerida:** {puntuacion_sugerida}/10

### Archivos probablemente modificados:
{archivos_estimados}

---

## 📝 SOLO PON TU NÚMERO (0-10):

```
[Pon aquí el número basado en:
  - 0: Sin cambios
  - 3: Empeoró (dañó tests)
  - 6: Corrigió 50% bugs
  - 7: Corrigió 70% bugs
  - 8: Corrigió 80% bugs
  - 9: Corrigió 90% bugs
  - 10: Corrigió 100% bugs
Sugerido: {puntuacion_sugerida}]
```
"""
    
    # Generar contenido
    contenido = f"""# 🔍 PISTA DE AUDITORÍA - {nombre_grupo}

**Fecha:** {fecha}  
**Grupo:** {grupo_id}

---

## 📊 Comparación Automática

| Métrica | Anterior | Actual | Cambio |
|---------|----------|--------|--------|
| Tests totales | {anterior['total'] if anterior else 'N/A'} | {actual['total']} | - |
| Tests pasados | {anterior['pasados'] if anterior else 'N/A'} | {actual['pasados']} | {'+' if cambio > 0 else ''}{cambio if anterior else 'N/A'} |
| Tests fallados | {anterior['fallados'] if anterior else 'N/A'} | {actual['fallados']} | {'-' if cambio > 0 else '+'}{abs(cambio) if anterior else 'N/A'} |
| Porcentaje | {anterior['porcentaje'] if anterior else 'N/A'}% | {actual['porcentaje']}% | {'+' if cambio_pct > 0 else ''}{round(cambio_pct, 1) if anterior else 'N/A'}% |

**Estado:** {estado_auto}  
**Puntuación sugerida:** {puntuacion_sugerida}/10

---

## ✅ Tests Corregidos ({len(tests_corregidos)})

{chr(10).join([f'- {t}' for t in tests_corregidos]) if tests_corregidos else '*(ninguno)*'}

---

## ❌ Tests Dañados ({len(tests_danados)})

{chr(10).join([f'- {t}' for t in tests_danados]) if tests_danados else '*(ninguno)*'}

{seccion_final}

---

## 📌 Notas

- Ver reporte completo: `reportes/{grupo_id}/reportes/DOC_*.md`
- Historial completo en: `test-configuration/auditoria/historial_tests.json`

"""
    
    # Guardar archivo
    with open(archivo_pista, 'w', encoding='utf-8') as f:
        f.write(contenido)
    
    print(f"   📄 Pista generada: {archivo_pista.name}")


def generar_markdown_auditoria(comparaciones, puntuacion_total, fecha):
    """Genera reporte markdown de la auditoría (deprecado - usar pistas por grupo)"""
    pass


if __name__ == "__main__":
    generar_reporte_auditoria()
