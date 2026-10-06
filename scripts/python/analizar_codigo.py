#!/usr/bin/env python3
"""
Analizador de Código PHP - Métricas y Visualizaciones
Genera análisis de complejidad Big O y nomenclatura
"""

import re
import os
from pathlib import Path
from collections import defaultdict
import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
import numpy as np

# Configuración
PROYECTO_ROOT = Path(__file__).parent.parent.parent
OUTPUT_DIR = PROYECTO_ROOT / "docs" / "metricas"
OUTPUT_DIR.mkdir(exist_ok=True)

# Archivos a analizar
ARCHIVOS_CLAVE = [
    "config/database.php",
    "models/Reserva.php",
    "models/Cliente.php",
    "models/Mesa.php",
    "controllers/AuthController.php",
    "controllers/ReservaController.php",
    "app/validar_admin.php",
    "app/crear_reserva_admin.php",
    "validacion/ValidadorNombres.php",
]


class AnalizadorPHP:
    def __init__(self, archivo_path):
        self.path = Path(archivo_path)
        self.nombre = self.path.name
        with open(archivo_path, 'r', encoding='utf-8', errors='ignore') as f:
            self.contenido = f.read()
        
        self.funciones = self.extraer_funciones()
        self.variables = self.extraer_variables()
        self.clases = self.extraer_clases()
        self.seguridad = self.analizar_seguridad()
        self.patrones = self.analizar_patrones()
        self.metricas = self.metricas_codigo()
    
    def extraer_funciones(self):
        """Extrae funciones y calcula complejidad ciclomática"""
        patron = r'(?:public|private|protected)?\s*(?:static)?\s*function\s+(\w+)\s*\([^)]*\)\s*\{([^}]*(?:\{[^}]*\}[^}]*)*)\}'
        funciones = []
        
        for match in re.finditer(patron, self.contenido, re.MULTILINE | re.DOTALL):
            nombre = match.group(1)
            cuerpo = match.group(2)
            
            # Calcular anidaciones (loops dentro de loops)
            anidaciones = self.calcular_anidaciones(cuerpo)
            
            # Complejidad ciclomática
            complejidad = self.calcular_complejidad(cuerpo)
            
            funciones.append({
                'nombre': nombre,
                'complejidad': complejidad,
                'anidaciones': anidaciones,
                'lineas': cuerpo.count('\n') + 1,
                'big_o': self.estimar_big_o(anidaciones, cuerpo)
            })
        
        return funciones
    
    def calcular_anidaciones(self, codigo):
        """Cuenta niveles máximos de anidación (for, while dentro de for, etc.)"""
        max_anidacion = 0
        nivel_actual = 0
        
        # Buscar estructuras de control
        tokens = re.findall(r'(for|foreach|while|if)\s*\(|(\})', codigo)
        
        for token in tokens:
            if token[0]:  # Apertura de estructura
                nivel_actual += 1
                max_anidacion = max(max_anidacion, nivel_actual)
            elif token[1]:  # Cierre }
                nivel_actual = max(0, nivel_actual - 1)
        
        return max_anidacion
    
    def calcular_complejidad(self, codigo):
        """Complejidad ciclomática = 1 + número de puntos de decisión"""
        decisiones = len(re.findall(r'\b(if|else|elseif|for|foreach|while|case|catch|\?\?)\b', codigo))
        return 1 + decisiones
    
    def estimar_big_o(self, anidaciones, codigo):
        """Estima Big O basado en anidaciones"""
        if anidaciones == 0:
            return "O(1)"
        elif anidaciones == 1:
            # Verificar si hay queries dentro
            if re.search(r'(prepare|query|execute|fetch)', codigo):
                return "O(n)"
            return "O(n)"
        elif anidaciones == 2:
            return "O(n²)"
        elif anidaciones == 3:
            return "O(n³)"
        else:
            return f"O(n^{anidaciones})"
    
    def extraer_variables(self):
        """Analiza nomenclatura de variables"""
        # Variables PHP ($nombre)
        vars_php = re.findall(r'\$([a-zA-Z_]\w*)', self.contenido)
        
        nomenclatura = {
            'snake_case': [],
            'camelCase': [],
            'PascalCase': [],
            'otros': []
        }
        
        for var in set(vars_php):
            if re.match(r'^[a-z]+(_[a-z0-9]+)*$', var):
                nomenclatura['snake_case'].append(var)
            elif re.match(r'^[a-z]+([A-Z][a-z0-9]*)*$', var):
                nomenclatura['camelCase'].append(var)
            elif re.match(r'^[A-Z][a-zA-Z0-9]*$', var):
                nomenclatura['PascalCase'].append(var)
            else:
                nomenclatura['otros'].append(var)
        
        return nomenclatura
    
    def extraer_clases(self):
        """Extrae nombres de clases"""
        clases = re.findall(r'class\s+(\w+)', self.contenido)
        return clases
    
    def analizar_seguridad(self):
        """Analiza prácticas de seguridad"""
        seguridad = {
            'prepared_statements': len(re.findall(r'->prepare\(', self.contenido)),
            'password_hash': len(re.findall(r'password_hash\(', self.contenido)),
            'password_verify': len(re.findall(r'password_verify\(', self.contenido)),
            'htmlspecialchars': len(re.findall(r'htmlspecialchars\(', self.contenido)),
            'filter_var': len(re.findall(r'filter_var\(', self.contenido)),
            'trim': len(re.findall(r'trim\(', self.contenido)),
            'sql_injection_riesgo': len(re.findall(r'mysqli_query\s*\(\s*\$[^,]+,\s*["\'].*\$', self.contenido)),
            'session_regenerate': len(re.findall(r'session_regenerate_id\(', self.contenido)),
        }
        return seguridad
    
    def analizar_patrones(self):
        """Detecta patrones de diseño"""
        patrones = {
            'singleton': 1 if re.search(r'private\s+static\s+\$instance', self.contenido) else 0,
            'factory': 1 if re.search(r'function\s+create\w*\(', self.contenido) else 0,
            'active_record': 1 if re.search(r'function\s+(create|update|delete|getAll)\(', self.contenido) else 0,
            'dependency_injection': len(re.findall(r'function\s+__construct\([^)]+\$', self.contenido)),
        }
        return patrones
    
    def metricas_codigo(self):
        """Métricas generales del código"""
        lineas_totales = len(self.contenido.split('\n'))
        lineas_codigo = len([l for l in self.contenido.split('\n') if l.strip() and not l.strip().startswith('//')])
        lineas_comentarios = len(re.findall(r'^\s*//', self.contenido, re.MULTILINE))
        
        # Parámetros promedio por función
        params_totales = 0
        for func in self.funciones:
            patron_params = r'function\s+' + func['nombre'] + r'\(([^)]*)\)'
            match = re.search(patron_params, self.contenido)
            if match:
                params = match.group(1).split(',') if match.group(1).strip() else []
                params_totales += len(params)
        
        params_promedio = params_totales / len(self.funciones) if self.funciones else 0
        
        return {
            'lineas_totales': lineas_totales,
            'lineas_codigo': lineas_codigo,
            'lineas_comentarios': lineas_comentarios,
            'funciones_count': len(self.funciones),
            'params_promedio': params_promedio,
        }


def generar_grafico_complejidad(datos_archivos):
    """Gráfico de complejidad ciclomática por archivo"""
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(14, 6))
    
    # Gráfico 1: Complejidad promedio por archivo
    archivos = []
    complejidades = []
    
    for archivo, analizador in datos_archivos.items():
        if analizador.funciones:
            archivos.append(archivo.split('/')[-1][:15])
            promedio = sum(f['complejidad'] for f in analizador.funciones) / len(analizador.funciones)
            complejidades.append(promedio)
    
    colores = ['green' if c <= 5 else 'orange' if c <= 10 else 'red' for c in complejidades]
    ax1.barh(archivos, complejidades, color=colores)
    ax1.set_xlabel('Complejidad Ciclomática Promedio')
    ax1.set_title('Complejidad por Archivo\n(Verde: Bueno ≤5, Naranja: Aceptable ≤10, Rojo: Alto >10)')
    ax1.axvline(x=5, color='green', linestyle='--', alpha=0.5, label='Límite óptimo')
    ax1.axvline(x=10, color='orange', linestyle='--', alpha=0.5, label='Límite aceptable')
    
    # Gráfico 2: Distribución Big O
    big_o_count = defaultdict(int)
    
    for analizador in datos_archivos.values():
        for func in analizador.funciones:
            big_o_count[func['big_o']] += 1
    
    labels = list(big_o_count.keys())
    sizes = list(big_o_count.values())
    colors_pie = ['#4CAF50', '#8BC34A', '#FFC107', '#FF5722', '#F44336']
    
    ax2.pie(sizes, labels=labels, autopct='%1.1f%%', colors=colors_pie[:len(labels)], startangle=90)
    ax2.set_title('Distribución de Complejidad Big O\nen Funciones del Sistema')
    
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / 'complejidad_ciclomatica.png', dpi=300, bbox_inches='tight')
    print(f"✅ Gráfico guardado: {OUTPUT_DIR / 'complejidad_ciclomatica.png'}")


def generar_grafico_anidaciones(datos_archivos):
    """Gráfico de anidaciones (Big O visual)"""
    fig, ax = plt.subplots(figsize=(12, 6))
    
    funciones_complejas = []
    
    for archivo, analizador in datos_archivos.items():
        for func in analizador.funciones:
            if func['anidaciones'] > 0:
                funciones_complejas.append({
                    'nombre': f"{archivo.split('/')[-1][:10]}::{func['nombre'][:15]}",
                    'anidaciones': func['anidaciones'],
                    'big_o': func['big_o']
                })
    
    # Ordenar por anidaciones
    funciones_complejas.sort(key=lambda x: x['anidaciones'], reverse=True)
    funciones_complejas = funciones_complejas[:15]  # Top 15
    
    nombres = [f['nombre'] for f in funciones_complejas]
    anidaciones = [f['anidaciones'] for f in funciones_complejas]
    big_os = [f['big_o'] for f in funciones_complejas]
    
    # Colores según Big O
    color_map = {
        'O(1)': '#4CAF50',
        'O(n)': '#8BC34A',
        'O(n²)': '#FFC107',
        'O(n³)': '#FF5722'
    }
    colores = [color_map.get(bo, '#F44336') for bo in big_os]
    
    bars = ax.barh(nombres, anidaciones, color=colores)
    ax.set_xlabel('Niveles de Anidación (loops dentro de loops)')
    ax.set_title('Top 15 Funciones con Mayor Anidación\n(Indicador de Complejidad Algorítmica)')
    ax.set_xlim(0, max(anidaciones) + 1 if anidaciones else 3)
    
    # Añadir etiquetas Big O
    for i, (bar, big_o) in enumerate(zip(bars, big_os)):
        width = bar.get_width()
        ax.text(width + 0.1, bar.get_y() + bar.get_height()/2, 
                big_o, va='center', fontsize=9, fontweight='bold')
    
    # Leyenda
    legend_elements = [
        mpatches.Patch(color='#4CAF50', label='O(1) - Constante'),
        mpatches.Patch(color='#8BC34A', label='O(n) - Lineal'),
        mpatches.Patch(color='#FFC107', label='O(n²) - Cuadrática'),
        mpatches.Patch(color='#FF5722', label='O(n³) - Cúbica')
    ]
    ax.legend(handles=legend_elements, loc='lower right')
    
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / 'anidaciones_big_o.png', dpi=300, bbox_inches='tight')
    print(f"✅ Gráfico guardado: {OUTPUT_DIR / 'anidaciones_big_o.png'}")


def generar_grafico_nomenclatura(datos_archivos):
    """Gráfico de convenciones de nomenclatura"""
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(14, 6))
    
    # Consolidar variables de todos los archivos
    total_nomenclatura = defaultdict(int)
    
    for analizador in datos_archivos.values():
        for tipo, vars in analizador.variables.items():
            total_nomenclatura[tipo] += len(vars)
    
    # Gráfico 1: Variables
    tipos = list(total_nomenclatura.keys())
    cantidades = list(total_nomenclatura.values())
    
    colores_var = {
        'snake_case': '#4CAF50',
        'camelCase': '#2196F3',
        'PascalCase': '#FF9800',
        'otros': '#F44336'
    }
    colores = [colores_var.get(t, '#999') for t in tipos]
    
    ax1.bar(tipos, cantidades, color=colores)
    ax1.set_ylabel('Cantidad de Variables')
    ax1.set_title('Convención de Nomenclatura en Variables\n(Proyecto completo)')
    ax1.set_ylim(0, max(cantidades) * 1.2 if cantidades else 10)
    
    for i, (tipo, cant) in enumerate(zip(tipos, cantidades)):
        ax1.text(i, cant + 5, str(cant), ha='center', fontweight='bold')
    
    # Gráfico 2: Clases (siempre PascalCase en PHP)
    total_clases = sum(len(a.clases) for a in datos_archivos.values())
    
    ax2.bar(['PascalCase\n(Clases)'], [total_clases], color='#FF9800', width=0.5)
    ax2.set_ylabel('Cantidad de Clases')
    ax2.set_title('Nomenclatura de Clases\n(Estándar PSR-1)')
    ax2.text(0, total_clases + 0.5, str(total_clases), ha='center', fontsize=16, fontweight='bold')
    
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / 'nomenclatura.png', dpi=300, bbox_inches='tight')
    print(f"✅ Gráfico guardado: {OUTPUT_DIR / 'nomenclatura.png'}")


def generar_grafico_seguridad(datos_archivos):
    """Gráfico de prácticas de seguridad"""
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(16, 6))
    
    # Consolidar métricas de seguridad
    total_seguridad = defaultdict(int)
    for analizador in datos_archivos.values():
        for metrica, valor in analizador.seguridad.items():
            total_seguridad[metrica] += valor
    
    # Gráfico 1: Buenas prácticas de seguridad
    buenas_practicas = {
        'Prepared\nStatements': total_seguridad['prepared_statements'],
        'Password\nHash': total_seguridad['password_hash'],
        'Password\nVerify': total_seguridad['password_verify'],
        'HTML\nEscape': total_seguridad['htmlspecialchars'],
        'Filter\nVar': total_seguridad['filter_var'],
        'Trim\nInputs': total_seguridad['trim'],
        'Session\nRegenerate': total_seguridad['session_regenerate'],
    }
    
    metricas = list(buenas_practicas.keys())
    valores = list(buenas_practicas.values())
    colores = ['#4CAF50' if v > 0 else '#E0E0E0' for v in valores]
    
    bars = ax1.bar(metricas, valores, color=colores, edgecolor='black', linewidth=1.5)
    ax1.set_ylabel('Cantidad de Usos', fontsize=12, fontweight='bold')
    ax1.set_title('✅ BUENAS PRÁCTICAS DE SEGURIDAD IMPLEMENTADAS\n(Mayor es Mejor)', 
                  fontsize=14, fontweight='bold')
    ax1.set_ylim(0, max(valores) * 1.3 if valores else 10)
    ax1.grid(axis='y', alpha=0.3, linestyle='--')
    
    # Añadir valores en las barras
    for bar in bars:
        height = bar.get_height()
        if height > 0:
            ax1.text(bar.get_x() + bar.get_width()/2., height + 0.5,
                    f'{int(height)}', ha='center', va='bottom', fontweight='bold', fontsize=11)
    
    # Gráfico 2: Riesgos de seguridad
    riesgos = {
        'SQL Injection\nRiesgo': total_seguridad['sql_injection_riesgo'],
    }
    
    # Calcular puntaje de seguridad (0-100)
    puntaje_positivo = sum(buenas_practicas.values())
    puntaje_negativo = total_seguridad['sql_injection_riesgo'] * 10
    puntaje_total = max(0, min(100, (puntaje_positivo - puntaje_negativo) * 2))
    
    # Gauge chart simulado
    categorias = ['CRÍTICO\n(0-30)', 'BAJO\n(31-60)', 'MEDIO\n(61-80)', 'ALTO\n(81-90)', 'EXCELENTE\n(91-100)']
    rangos = [30, 30, 20, 10, 10]
    colores_gauge = ['#F44336', '#FF9800', '#FFC107', '#8BC34A', '#4CAF50']
    
    # Crear gráfico de pastel semi-circular
    wedges, texts = ax2.pie(rangos, colors=colores_gauge, startangle=180, counterclock=False,
                             wedgeprops={'edgecolor': 'white', 'linewidth': 2})
    
    # Añadir aguja indicadora
    angulo = 180 - (puntaje_total * 1.8)  # 180° a 0° = 100 a 0
    ax2.annotate('', xy=(0.7 * np.cos(np.radians(angulo)), 0.7 * np.sin(np.radians(angulo))),
                 xytext=(0, 0), arrowprops=dict(arrowstyle='->', lw=3, color='black'))
    
    # Texto del puntaje
    ax2.text(0, -0.3, f'{int(puntaje_total)}', ha='center', va='center', 
             fontsize=48, fontweight='bold', color='#1E3A8A')
    ax2.text(0, -0.5, 'PUNTAJE DE SEGURIDAD', ha='center', va='center',
             fontsize=12, fontweight='bold')
    
    # Añadir leyenda
    ax2.legend(categorias, loc='upper center', bbox_to_anchor=(0.5, -0.05), ncol=5, fontsize=9)
    ax2.set_title('🛡️ ÍNDICE DE SEGURIDAD GLOBAL\n(0 = Vulnerable, 100 = Muy Seguro)',
                  fontsize=14, fontweight='bold')
    
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / 'seguridad.png', dpi=300, bbox_inches='tight')
    print(f"✅ Gráfico guardado: {OUTPUT_DIR / 'seguridad.png'}")


def generar_grafico_patrones(datos_archivos):
    """Gráfico de patrones de diseño detectados"""
    import numpy as np
    
    fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(16, 6))
    
    # Consolidar patrones
    total_patrones = defaultdict(int)
    for analizador in datos_archivos.values():
        for patron, valor in analizador.patrones.items():
            total_patrones[patron] += valor
    
    # Gráfico 1: Patrones detectados
    patrones_nombres = {
        'singleton': 'Singleton',
        'factory': 'Factory',
        'active_record': 'Active Record',
        'dependency_injection': 'Dependency\nInjection'
    }
    
    nombres = [patrones_nombres[k] for k in total_patrones.keys()]
    valores = list(total_patrones.values())
    colores_patrones = ['#9C27B0', '#3F51B5', '#00BCD4', '#4CAF50']
    
    bars = ax1.bar(nombres, valores, color=colores_patrones, edgecolor='black', linewidth=2)
    ax1.set_ylabel('Ocurrencias', fontsize=12, fontweight='bold')
    ax1.set_title('🎨 PATRONES DE DISEÑO DETECTADOS\n(Gang of Four + MVC)', 
                  fontsize=14, fontweight='bold')
    ax1.set_ylim(0, max(valores) * 1.3 if valores else 10)
    ax1.grid(axis='y', alpha=0.3, linestyle='--')
    
    for bar in bars:
        height = bar.get_height()
        ax1.text(bar.get_x() + bar.get_width()/2., height + 0.3,
                f'{int(height)}', ha='center', va='bottom', fontweight='bold', fontsize=12)
    
    # Gráfico 2: Distribución de principios SOLID (simulado)
    # Basado en métricas del código
    solid_scores = []
    solid_labels = []
    
    # S - Single Responsibility (menos líneas por función = mejor)
    lineas_promedio = np.mean([f['lineas'] for a in datos_archivos.values() for f in a.funciones])
    solid_s = max(0, 100 - (lineas_promedio * 2))
    solid_scores.append(min(100, solid_s))
    solid_labels.append('Single\nResponsibility')
    
    # O - Open/Closed (uso de clases = mejor)
    total_clases = sum(len(a.clases) for a in datos_archivos.values())
    solid_o = min(100, total_clases * 15)
    solid_scores.append(solid_o)
    solid_labels.append('Open/Closed')
    
    # L - Liskov Substitution (difícil de medir, usar promedio)
    solid_l = 75
    solid_scores.append(solid_l)
    solid_labels.append('Liskov\nSubstitution')
    
    # D - Dependency Inversion (inyección de dependencias)
    total_di = sum(a.patrones['dependency_injection'] for a in datos_archivos.values())
    solid_d = min(100, total_di * 20)
    solid_scores.append(solid_d)
    solid_labels.append('Dependency\nInversion')
    
    # Gráfico de radar
    angles = np.linspace(0, 2 * np.pi, len(solid_labels), endpoint=False).tolist()
    solid_scores += solid_scores[:1]  # Cerrar el polígono
    angles += angles[:1]
    
    ax2 = plt.subplot(122, projection='polar')
    ax2.plot(angles, solid_scores, 'o-', linewidth=2, color='#1E3A8A', label='Proyecto')
    ax2.fill(angles, solid_scores, alpha=0.25, color='#3B82F6')
    ax2.set_xticks(angles[:-1])
    ax2.set_xticklabels(solid_labels, fontsize=10, fontweight='bold')
    ax2.set_ylim(0, 100)
    ax2.set_yticks([25, 50, 75, 100])
    ax2.set_yticklabels(['25', '50', '75', '100'], fontsize=8)
    ax2.set_title('⚙️ ADHERENCIA A PRINCIPIOS SOLID\n(0 = No cumple, 100 = Excelente)', 
                  fontsize=14, fontweight='bold', pad=20)
    ax2.grid(True, alpha=0.3)
    
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / 'patrones_solid.png', dpi=300, bbox_inches='tight')
    print(f"✅ Gráfico guardado: {OUTPUT_DIR / 'patrones_solid.png'}")


def generar_grafico_metricas(datos_archivos):
    """Gráfico de métricas de código"""
    fig = plt.figure(figsize=(16, 10))
    gs = fig.add_gridspec(2, 2, hspace=0.3, wspace=0.3)
    ax1 = fig.add_subplot(gs[0, 0])
    ax2 = fig.add_subplot(gs[0, 1])
    ax3 = fig.add_subplot(gs[1, :])
    
    # Gráfico 1: Líneas de código por archivo
    archivos = []
    lineas = []
    
    for archivo, analizador in datos_archivos.items():
        archivos.append(archivo.split('/')[-1][:20])
        lineas.append(analizador.metricas['lineas_codigo'])
    
    colores_lineas = ['#4CAF50' if l < 200 else '#FFC107' if l < 400 else '#FF5722' for l in lineas]
    ax1.barh(archivos, lineas, color=colores_lineas, edgecolor='black', linewidth=1)
    ax1.set_xlabel('Líneas de Código', fontweight='bold')
    ax1.set_title('📏 LÍNEAS DE CÓDIGO POR ARCHIVO\n(Verde < 200, Amarillo < 400, Rojo >= 400)',
                  fontweight='bold', fontsize=12)
    ax1.axvline(x=200, color='green', linestyle='--', alpha=0.5, linewidth=2)
    ax1.axvline(x=400, color='orange', linestyle='--', alpha=0.5, linewidth=2)
    
    # Gráfico 2: Parámetros promedio por función
    params = [a.metricas['params_promedio'] for a in datos_archivos.values()]
    promedio_global = np.mean(params)
    
    ax2.hist(params, bins=5, color='#3F51B5', edgecolor='black', linewidth=1.5, alpha=0.7)
    ax2.axvline(x=promedio_global, color='red', linestyle='--', linewidth=3, 
                label=f'Promedio: {promedio_global:.1f}')
    ax2.axvline(x=3, color='green', linestyle='--', linewidth=2, alpha=0.5,
                label='Límite recomendado: 3')
    ax2.set_xlabel('Parámetros por Función', fontweight='bold')
    ax2.set_ylabel('Frecuencia', fontweight='bold')
    ax2.set_title('📊 DISTRIBUCIÓN DE PARÁMETROS POR FUNCIÓN\n(Menos es Mejor)',
                  fontweight='bold', fontsize=12)
    ax2.legend(fontsize=10)
    ax2.grid(axis='y', alpha=0.3)
    
    # Gráfico 3: Comparativa multi-métrica por archivo
    archivos_cortos = [a.split('/')[-1][:15] for a in datos_archivos.keys()]
    
    metricas_comparar = {
        'Complejidad Promedio': [],
        'Líneas/Función': [],
        'Funciones': [],
    }
    
    for analizador in datos_archivos.values():
        if analizador.funciones:
            complejidad_prom = np.mean([f['complejidad'] for f in analizador.funciones])
            lineas_prom = np.mean([f['lineas'] for f in analizador.funciones])
        else:
            complejidad_prom = 0
            lineas_prom = 0
        
        metricas_comparar['Complejidad Promedio'].append(complejidad_prom)
        metricas_comparar['Líneas/Función'].append(lineas_prom / 10)  # Escala /10 para visualizar
        metricas_comparar['Funciones'].append(len(analizador.funciones))
    
    x = np.arange(len(archivos_cortos))
    width = 0.25
    
    bars1 = ax3.bar(x - width, metricas_comparar['Complejidad Promedio'], width, 
                    label='Complejidad Promedio', color='#FF5722', edgecolor='black')
    bars2 = ax3.bar(x, metricas_comparar['Líneas/Función'], width,
                    label='Líneas/Función (÷10)', color='#2196F3', edgecolor='black')
    bars3 = ax3.bar(x + width, metricas_comparar['Funciones'], width,
                    label='# Funciones', color='#4CAF50', edgecolor='black')
    
    ax3.set_xlabel('Archivos', fontweight='bold', fontsize=12)
    ax3.set_ylabel('Valores', fontweight='bold', fontsize=12)
    ax3.set_title('📈 COMPARATIVA MULTI-MÉTRICA POR ARCHIVO\n(Vista Consolidada de Calidad)',
                  fontweight='bold', fontsize=14)
    ax3.set_xticks(x)
    ax3.set_xticklabels(archivos_cortos, rotation=45, ha='right')
    ax3.legend(loc='upper left', fontsize=10)
    ax3.grid(axis='y', alpha=0.3, linestyle='--')
    
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / 'metricas_codigo.png', dpi=300, bbox_inches='tight')
    print(f"✅ Gráfico guardado: {OUTPUT_DIR / 'metricas_codigo.png'}")


def generar_dashboard_resumen(datos_archivos):
    """Dashboard resumen estilo infografía"""
    import numpy as np
    
    fig = plt.figure(figsize=(18, 10))
    fig.suptitle('🎯 DASHBOARD DE CALIDAD DE CÓDIGO - Le Salon de Lumière', 
                 fontsize=20, fontweight='bold', y=0.98)
    
    # Calcular métricas globales
    total_archivos = len(datos_archivos)
    total_funciones = sum(len(a.funciones) for a in datos_archivos.values())
    total_clases = sum(len(a.clases) for a in datos_archivos.values())
    total_lineas = sum(a.metricas['lineas_codigo'] for a in datos_archivos.values())
    
    complejidad_promedio = np.mean([f['complejidad'] for a in datos_archivos.values() for f in a.funciones])
    
    # Seguridad
    prepared_statements = sum(a.seguridad['prepared_statements'] for a in datos_archivos.values())
    sql_injection_riesgo = sum(a.seguridad['sql_injection_riesgo'] for a in datos_archivos.values())
    
    # Nomenclatura
    total_snake = sum(len(a.variables['snake_case']) for a in datos_archivos.values())
    total_vars = sum(sum(len(v) for v in a.variables.values()) for a in datos_archivos.values())
    consistencia_nomenclatura = (total_snake / total_vars * 100) if total_vars > 0 else 0
    
    # Crear grid para paneles
    ax1 = plt.subplot(3, 4, 1)
    ax2 = plt.subplot(3, 4, 2)
    ax3 = plt.subplot(3, 4, 3)
    ax4 = plt.subplot(3, 4, 4)
    ax5 = plt.subplot(3, 4, 5)
    ax6 = plt.subplot(3, 4, 6)
    ax7 = plt.subplot(3, 4, 7)
    ax8 = plt.subplot(3, 4, 8)
    ax9 = plt.subplot(3, 1, 3)
    
    # Panel 1: Archivos
    ax1.text(0.5, 0.6, str(total_archivos), ha='center', va='center', fontsize=60, fontweight='bold', color='#1E3A8A')
    ax1.text(0.5, 0.2, 'ARCHIVOS\nANALIZADOS', ha='center', va='center', fontsize=12, fontweight='bold')
    ax1.set_xlim(0, 1)
    ax1.set_ylim(0, 1)
    ax1.axis('off')
    ax1.add_patch(plt.Rectangle((0.05, 0.05), 0.9, 0.9, fill=False, edgecolor='#1E3A8A', linewidth=3))
    
    # Panel 2: Funciones
    ax2.text(0.5, 0.6, str(total_funciones), ha='center', va='center', fontsize=60, fontweight='bold', color='#10B981')
    ax2.text(0.5, 0.2, 'FUNCIONES\nTOTALES', ha='center', va='center', fontsize=12, fontweight='bold')
    ax2.set_xlim(0, 1)
    ax2.set_ylim(0, 1)
    ax2.axis('off')
    ax2.add_patch(plt.Rectangle((0.05, 0.05), 0.9, 0.9, fill=False, edgecolor='#10B981', linewidth=3))
    
    # Panel 3: Clases
    ax3.text(0.5, 0.6, str(total_clases), ha='center', va='center', fontsize=60, fontweight='bold', color='#F59E0B')
    ax3.text(0.5, 0.2, 'CLASES\n(OOP)', ha='center', va='center', fontsize=12, fontweight='bold')
    ax3.set_xlim(0, 1)
    ax3.set_ylim(0, 1)
    ax3.axis('off')
    ax3.add_patch(plt.Rectangle((0.05, 0.05), 0.9, 0.9, fill=False, edgecolor='#F59E0B', linewidth=3))
    
    # Panel 4: Líneas de código
    ax4.text(0.5, 0.6, f'{total_lineas:,}', ha='center', va='center', fontsize=50, fontweight='bold', color='#8B5CF6')
    ax4.text(0.5, 0.2, 'LÍNEAS DE\nCÓDIGO', ha='center', va='center', fontsize=12, fontweight='bold')
    ax4.set_xlim(0, 1)
    ax4.set_ylim(0, 1)
    ax4.axis('off')
    ax4.add_patch(plt.Rectangle((0.05, 0.05), 0.9, 0.9, fill=False, edgecolor='#8B5CF6', linewidth=3))
    
    # Panel 5: Complejidad
    color_complejidad = '#10B981' if complejidad_promedio <= 5 else '#F59E0B' if complejidad_promedio <= 10 else '#EF4444'
    ax5.text(0.5, 0.6, f'{complejidad_promedio:.1f}', ha='center', va='center', fontsize=60, fontweight='bold', color=color_complejidad)
    ax5.text(0.5, 0.2, 'COMPLEJIDAD\nPROMEDIO', ha='center', va='center', fontsize=12, fontweight='bold')
    ax5.text(0.5, 0.05, '(≤5 = Excelente)', ha='center', va='center', fontsize=8, style='italic')
    ax5.set_xlim(0, 1)
    ax5.set_ylim(0, 1)
    ax5.axis('off')
    ax5.add_patch(plt.Rectangle((0.05, 0.05), 0.9, 0.9, fill=False, edgecolor=color_complejidad, linewidth=3))
    
    # Panel 6: Prepared Statements
    ax6.text(0.5, 0.6, str(prepared_statements), ha='center', va='center', fontsize=60, fontweight='bold', color='#10B981')
    ax6.text(0.5, 0.2, 'PREPARED\nSTATEMENTS', ha='center', va='center', fontsize=12, fontweight='bold')
    ax6.text(0.5, 0.05, '(Anti SQL Injection)', ha='center', va='center', fontsize=8, style='italic')
    ax6.set_xlim(0, 1)
    ax6.set_ylim(0, 1)
    ax6.axis('off')
    ax6.add_patch(plt.Rectangle((0.05, 0.05), 0.9, 0.9, fill=False, edgecolor='#10B981', linewidth=3))
    
    # Panel 7: Riesgos SQL
    color_riesgo = '#10B981' if sql_injection_riesgo == 0 else '#EF4444'
    ax7.text(0.5, 0.6, str(sql_injection_riesgo), ha='center', va='center', fontsize=60, fontweight='bold', color=color_riesgo)
    ax7.text(0.5, 0.2, 'SQL INJECTION\nRIESGO', ha='center', va='center', fontsize=12, fontweight='bold')
    ax7.text(0.5, 0.05, '(0 = Seguro)', ha='center', va='center', fontsize=8, style='italic')
    ax7.set_xlim(0, 1)
    ax7.set_ylim(0, 1)
    ax7.axis('off')
    ax7.add_patch(plt.Rectangle((0.05, 0.05), 0.9, 0.9, fill=False, edgecolor=color_riesgo, linewidth=3))
    
    # Panel 8: Consistencia nomenclatura
    color_nomenclatura = '#10B981' if consistencia_nomenclatura >= 80 else '#F59E0B'
    ax8.text(0.5, 0.6, f'{consistencia_nomenclatura:.0f}%', ha='center', va='center', fontsize=50, fontweight='bold', color=color_nomenclatura)
    ax8.text(0.5, 0.2, 'SNAKE_CASE\nCONSISTENCIA', ha='center', va='center', fontsize=12, fontweight='bold')
    ax8.text(0.5, 0.05, '(≥80% = Bueno)', ha='center', va='center', fontsize=8, style='italic')
    ax8.set_xlim(0, 1)
    ax8.set_ylim(0, 1)
    ax8.axis('off')
    ax8.add_patch(plt.Rectangle((0.05, 0.05), 0.9, 0.9, fill=False, edgecolor=color_nomenclatura, linewidth=3))
    
    # Panel 9: Gráfico de evolución Big O
    big_o_dist = defaultdict(int)
    for analizador in datos_archivos.values():
        for func in analizador.funciones:
            big_o_dist[func['big_o']] += 1
    
    labels_big_o = list(big_o_dist.keys())
    valores_big_o = list(big_o_dist.values())
    colores_big_o = ['#10B981', '#34D399', '#FCD34D', '#FB923C', '#EF4444']
    
    wedges, texts, autotexts = ax9.pie(valores_big_o, labels=labels_big_o, autopct='%1.1f%%',
                                        colors=colores_big_o[:len(labels_big_o)], startangle=90,
                                        textprops={'fontsize': 14, 'fontweight': 'bold'})
    ax9.set_title('🚀 DISTRIBUCIÓN DE COMPLEJIDAD ALGORÍTMICA (Big O)', fontsize=14, fontweight='bold', pad=20)
    
    for autotext in autotexts:
        autotext.set_color('white')
        autotext.set_fontsize(12)
        autotext.set_fontweight('bold')
    
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / 'dashboard_resumen.png', dpi=300, bbox_inches='tight')
    print(f"✅ Gráfico guardado: {OUTPUT_DIR / 'dashboard_resumen.png'}")


def generar_reporte_texto(datos_archivos):
    """Genera reporte en texto plano"""
    reporte = []
    reporte.append("=" * 80)
    reporte.append("REPORTE DE ANÁLISIS DE CÓDIGO - Le Salon de Lumière")
    reporte.append("=" * 80)
    reporte.append("")
    
    # Resumen general
    total_funciones = sum(len(a.funciones) for a in datos_archivos.values())
    total_clases = sum(len(a.clases) for a in datos_archivos.values())
    
    reporte.append("📊 RESUMEN GENERAL")
    reporte.append("-" * 80)
    reporte.append(f"Archivos analizados: {len(datos_archivos)}")
    reporte.append(f"Total de clases: {total_clases}")
    reporte.append(f"Total de funciones: {total_funciones}")
    reporte.append("")
    
    # Top funciones complejas
    todas_funciones = []
    for archivo, analizador in datos_archivos.items():
        for func in analizador.funciones:
            todas_funciones.append({
                'archivo': archivo.split('/')[-1],
                'nombre': func['nombre'],
                'complejidad': func['complejidad'],
                'anidaciones': func['anidaciones'],
                'big_o': func['big_o']
            })
    
    todas_funciones.sort(key=lambda x: x['complejidad'], reverse=True)
    
    reporte.append("🔴 TOP 10 FUNCIONES MÁS COMPLEJAS (Complejidad Ciclomática)")
    reporte.append("-" * 80)
    for i, func in enumerate(todas_funciones[:10], 1):
        reporte.append(f"{i}. {func['archivo']}::{func['nombre']}")
        reporte.append(f"   Complejidad: {func['complejidad']}, Anidaciones: {func['anidaciones']}, Big O: {func['big_o']}")
    reporte.append("")
    
    # Nomenclatura
    total_nomenclatura = defaultdict(int)
    for analizador in datos_archivos.values():
        for tipo, vars in analizador.variables.items():
            total_nomenclatura[tipo] += len(vars)
    
    reporte.append("📝 ANÁLISIS DE NOMENCLATURA")
    reporte.append("-" * 80)
    total_vars = sum(total_nomenclatura.values())
    for tipo, cant in total_nomenclatura.items():
        porcentaje = (cant / total_vars * 100) if total_vars > 0 else 0
        reporte.append(f"{tipo:15} : {cant:4} variables ({porcentaje:5.1f}%)")
    reporte.append("")
    
    # Guardar reporte
    reporte_path = OUTPUT_DIR / 'reporte_analisis.txt'
    with open(reporte_path, 'w', encoding='utf-8') as f:
        f.write('\n'.join(reporte))
    
    print(f"✅ Reporte guardado: {reporte_path}")
    
    # Imprimir en consola también
    print("\n" + '\n'.join(reporte))


def main():
    print("🔍 Iniciando análisis de código PHP...\n")
    
    datos_archivos = {}
    
    for archivo_rel in ARCHIVOS_CLAVE:
        archivo_path = PROYECTO_ROOT / archivo_rel
        if archivo_path.exists():
            print(f"📄 Analizando: {archivo_rel}")
            analizador = AnalizadorPHP(archivo_path)
            datos_archivos[archivo_rel] = analizador
        else:
            print(f"⚠️  No encontrado: {archivo_rel}")
    
    if not datos_archivos:
        print("❌ No se encontraron archivos para analizar")
        return
    
    print(f"\n✅ {len(datos_archivos)} archivos analizados\n")
    
    # Generar visualizaciones
    print("📊 Generando gráficos...\n")
    generar_grafico_complejidad(datos_archivos)
    generar_grafico_anidaciones(datos_archivos)
    generar_grafico_nomenclatura(datos_archivos)
    generar_grafico_seguridad(datos_archivos)
    generar_grafico_patrones(datos_archivos)
    generar_grafico_metricas(datos_archivos)
    generar_dashboard_resumen(datos_archivos)
    
    # Generar reporte
    print("\n📝 Generando reporte...\n")
    generar_reporte_texto(datos_archivos)
    
    print(f"\n🎉 Análisis completado. Resultados en: {OUTPUT_DIR}/")


if __name__ == "__main__":
    main()
