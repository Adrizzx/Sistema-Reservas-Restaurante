#!/usr/bin/env python3
"""
GENERADOR DE REPORTES AUTOMÁTICOS
Lee los JSON de resultados y genera archivos DOC_test_*.md
"""

import json
from datetime import datetime
from pathlib import Path

UNIT_REPORTES = Path(__file__).parent / "unit" / "reportes"

GRUPOS = {
    'admin': {
        'archivo_json': 'ultimo-resultado-admin.json',
        'archivo_doc': 'admin/reportes/DOC_test_admin.md',
        'nombre': 'Panel de Administración',
        'script': 'test_admin.py'
    },
    'cliente': {
        'archivo_json': 'ultimo-resultado-cliente.json',
        'archivo_doc': 'cliente/reportes/DOC_test_cliente.md',
        'nombre': 'Panel de Cliente',
        'script': 'test_cliente.py'
    },
    'mesas': {
        'archivo_json': 'ultimo-resultado-mesas.json',
        'archivo_doc': 'mesas/reportes/DOC_test_mesas.md',
        'nombre': 'Gestión de Mesas',
        'script': 'test_mesas.py'
    },
    'reservas': {
        'archivo_json': 'ultimo-resultado-reservas-mesas.json',
        'archivo_doc': 'reservas/reportes/DOC_test_reservas.md',
        'nombre': 'Reservas y Mesas',
        'script': 'test_reservas_mesas.py'
    }
}


def leer_json_resultado(archivo):
    """Lee el JSON de resultados"""
    ruta = UNIT_REPORTES / archivo
    if not ruta.exists():
        return None
    
    with open(ruta, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    # Detectar formato
    if isinstance(data, dict) and 'resumen' in data:
        # Formato nuevo
        return {
            'resumen': data['resumen'],
            'por_panel': data.get('por_panel', {}),
            'resultados': data['resultados'],
            'bugs_detectados': data.get('bugs_detectados', []),
            'fecha': data.get('fecha', datetime.now().isoformat())
        }
    elif isinstance(data, list):
        # Formato antiguo
        total = len(data)
        pasados = sum(1 for t in data if t.get('paso', False))
        fallados = total - pasados
        porcentaje = (pasados / total * 100) if total > 0 else 0
        
        return {
            'resumen': {
                'total': total,
                'pasados': pasados,
                'fallados': fallados,
                'porcentaje': round(porcentaje, 2)
            },
            'por_panel': {},
            'resultados': data,
            'bugs_detectados': [],
            'fecha': datetime.now().isoformat()
        }
    
    return None


def generar_doc_admin(data, nombre_grupo, script):
    """Genera documentación para test_admin.py"""
    
    resumen = data['resumen']
    por_panel = data.get('por_panel', {})
    bugs = data.get('bugs_detectados', [])
    fecha = datetime.fromisoformat(data['fecha']).strftime('%Y-%m-%d')
    
    # Agrupar tests por resultado
    tests_pasados = [t for t in data['resultados'] if t['paso']]
    tests_fallados = [t for t in data['resultados'] if not t['paso']]
    
    # Detectar bugs críticos
    bugs_criticos = [b for b in bugs if '🐛' in b or 'CRÍTICO' in b.upper()]
    bugs_seguridad = [b for b in bugs if '🛡️' in b or 'SQL' in b.upper() or 'XSS' in b.upper()]
    
    contenido = f"""# 📋 DOCUMENTACIÓN: {script}

**Archivo de test:** `test-configuration/unit/{script}`  
**Panel evaluado:** `{nombre_grupo}`  
**Fecha:** {fecha}

---

## 📊 Resumen

- **Total tests:** {resumen['total']}
- **Pasados:** {resumen['pasados']} ✅
- **Fallados:** {resumen['fallados']} ❌
- **Porcentaje éxito:** {resumen['porcentaje']}%

---

## ⚠️ ESTADO: {"NECESITA CORRECCIONES" if resumen['fallados'] > 0 else "TODOS LOS TESTS PASAN"}

**{resumen['fallados']} tests fallan** - Requiere atención

---
"""
    
    # Bugs detectados
    if bugs_criticos:
        contenido += f"""
## 🐛 BUGS CRÍTICOS DETECTADOS ({len(bugs_criticos)})

"""
        for i, bug in enumerate(bugs_criticos, 1):
            contenido += f"{i}. {bug}\n"
        contenido += "\n---\n"
    
    # Desglose por panel
    if por_panel:
        contenido += """
## 📊 Desglose por Panel

| Panel | Total | Pasados | Fallados | % Éxito |
|-------|-------|---------|----------|---------|
"""
        for panel, stats in por_panel.items():
            pct = (stats['pasados'] / stats['total'] * 100) if stats['total'] > 0 else 0
            estado = "✅" if stats['fallados'] == 0 else "⚠️"
            contenido += f"| {estado} {panel} | {stats['total']} | {stats['pasados']} | {stats['fallados']} | {pct:.1f}% |\n"
        
        contenido += "\n---\n"
    
    # Tests que pasan
    contenido += f"""
## ✅ Tests que pasan ({resumen['pasados']}):

"""
    
    # Agrupar por panel
    tests_por_panel = {}
    for test in tests_pasados:
        panel = test.get('panel', 'Sin categoría')
        if panel not in tests_por_panel:
            tests_por_panel[panel] = []
        tests_por_panel[panel].append(test['nombre'])
    
    for panel, tests in sorted(tests_por_panel.items()):
        contenido += f"### {panel} ({len(tests)} tests)\n"
        for test in tests[:10]:  # Mostrar solo primeros 10
            contenido += f"- ✅ {test}\n"
        if len(tests) > 10:
            contenido += f"- ✅ ... y {len(tests) - 10} tests más\n"
        contenido += "\n"
    
    contenido += "---\n"
    
    # Tests que fallan
    if tests_fallados:
        contenido += f"""
## ❌ Tests que fallan ({resumen['fallados']}):

"""
        
        tests_fallados_por_panel = {}
        for test in tests_fallados:
            panel = test.get('panel', 'Sin categoría')
            if panel not in tests_fallados_por_panel:
                tests_fallados_por_panel[panel] = []
            tests_fallados_por_panel[panel].append(test)
        
        for panel, tests in sorted(tests_fallados_por_panel.items()):
            contenido += f"### {panel} ({len(tests)} tests fallando)\n\n"
            for test in tests:
                contenido += f"- ❌ **{test['nombre']}**\n"
                # Extraer archivo PHP si está en la acción o respuesta
                accion = test.get('accion', '')
                archivo = None
                if '.php' in accion:
                    # Buscar nombre de archivo PHP
                    palabras = accion.split()
                    for palabra in palabras:
                        if '.php' in palabra:
                            archivo = palabra.strip('`').strip(',').strip('.')
                            break
                
                if archivo:
                    contenido += f"  - Archivo: `{archivo}`\n"
                contenido += f"  - Esperado: {test.get('esperado', 'N/A')}\n"
                contenido += "\n"
        
        contenido += "---\n"
    
    # Bugs de seguridad
    if bugs_seguridad:
        contenido += f"""
## 🛡️ Bugs de Seguridad Detectados ({len(bugs_seguridad)})

"""
        for i, bug in enumerate(bugs_seguridad, 1):
            contenido += f"{i}. {bug}\n"
        contenido += "\n---\n"
    
    # Conclusión
    contenido += f"""
## 🎯 Conclusión

**{nombre_grupo} - Estado General:**

"""
    
    if resumen['porcentaje'] >= 95:
        contenido += f"""✅ **EXCELENTE** - {resumen['porcentaje']}% de tests pasando
- Sistema muy estable
- Pocos bugs pendientes
"""
    elif resumen['porcentaje'] >= 80:
        contenido += f"""⚠️ **BUENO** - {resumen['porcentaje']}% de tests pasando
- Funcionalidad principal operativa
- Requiere correcciones menores
"""
    elif resumen['porcentaje'] >= 60:
        contenido += f"""⚠️ **REGULAR** - {resumen['porcentaje']}% de tests pasando
- Funcionalidad básica operativa
- Múltiples bugs que corregir
"""
    else:
        contenido += f"""🚨 **CRÍTICO** - {resumen['porcentaje']}% de tests pasando
- Sistema requiere trabajo significativo
- Bugs graves pendientes
"""
    
    if bugs_criticos:
        contenido += f"""
**🐛 Bugs Críticos:** {len(bugs_criticos)} detectados - **ALTA PRIORIDAD**
"""
    
    if bugs_seguridad:
        contenido += f"""
**🛡️ Seguridad:** {len(bugs_seguridad)} vulnerabilidades detectadas
"""
    
    contenido += f"""
**Próximos pasos:**
1. Revisar tests fallados
2. Corregir bugs críticos
3. Validar seguridad
4. Ejecutar auditoría: `python3 auditoria_tests.py`

---

*Generado automáticamente por: `generar_reportes.py`*  
*Fecha: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}*
"""
    
    return contenido


def main():
    """Genera reportes para todos los grupos"""
    print("\n" + "="*80)
    print("📄 GENERADOR DE REPORTES AUTOMÁTICOS")
    print("="*80 + "\n")
    
    for grupo_id, config in GRUPOS.items():
        print(f"📊 Procesando: {config['nombre']}...")
        
        # Leer JSON
        data = leer_json_resultado(config['archivo_json'])
        if not data:
            print(f"   ⚠️  No se encontró: {config['archivo_json']}")
            continue
        
        # Generar documentación
        contenido = generar_doc_admin(
            data,
            config['nombre'],
            config['script']
        )
        
        # Guardar archivo
        archivo_doc = UNIT_REPORTES / config['archivo_doc']
        archivo_doc.parent.mkdir(parents=True, exist_ok=True)
        
        with open(archivo_doc, 'w', encoding='utf-8') as f:
            f.write(contenido)
        
        print(f"   ✅ Generado: {archivo_doc.relative_to(UNIT_REPORTES.parent)}")
    
    print("\n" + "="*80)
    print("✅ Reportes generados exitosamente")
    print("="*80 + "\n")


if __name__ == "__main__":
    main()
