#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Tests para Reservas de Mesas y Zonas (mesas.php)
Detecta bugs en validación de fechas, disponibilidad y horarios
"""

import sys
import os
import json
import subprocess
from datetime import datetime, timedelta
from typing import Dict

try:
    import requests
except Exception as e:
    print("❌ Falta 'requests'. Instala: python3 -m pip install -r testing/code/python/requirements.txt")
    raise

from common import get_base_url, make_session, safe_request, result, merge_results

BASE = get_base_url()
SESSION = make_session()
API_URL = f"{BASE}/app/api/crear_reserva_zona.php"

REPORT_DIR = os.path.join(os.path.dirname(__file__), "reportes")
OUTPUT_FILE = os.path.join(REPORT_DIR, "ultimo-resultado-reservas-mesas.json")
os.makedirs(REPORT_DIR, exist_ok=True)


def login_cliente():
    """Login con usuario real joffre"""
    login_response = safe_request("POST", f"{BASE}/app/validar_cliente.php", SESSION, data={
        'usuario': 'joffre',
        'password': '20082004'
    })
    
    print(f"🔐 Login joffre: {login_response.get('data', {}).get('success', False)}")
    return login_response


def reservar_zona(zonas, fecha, hora, personas, nombre_test, debe_pasar=False):
    """Helper para crear reserva de zona"""
    payload = {
        "zonas": zonas,
        "fecha_reserva": fecha,
        "hora_reserva": hora,
        "numero_personas": personas
    }
    
    res = safe_request("POST", API_URL, SESSION, json=payload)
    data = res.get("data", {})
    
    ok = data.get("success") is True
    paso = ok if debe_pasar else not ok
    
    return result(
        nombre=nombre_test,
        panel="Reservar Zona",
        accion=f"POST zonas={zonas} fecha={fecha} hora={hora} personas={personas}",
        esperado="Debe validar fechas (no pasadas, max 6 meses), horarios, disponibilidad y datos",
        paso=paso,
        respuesta=data
    )


def main():
    """Ejecuta todos los tests de reservas"""
    print("\n🧪 TESTS DE RESERVAS DE MESAS Y ZONAS")
    print("=" * 60)
    
    # Login primero y verificar
    login_result = login_cliente()
    if not login_result.get('data', {}).get('success'):
        print("❌ ERROR: No se pudo hacer login. Abortando tests.")
        print(f"Respuesta: {login_result}")
        return 1
    
    # Insertar mesas de prueba
    subprocess.run([
        '/opt/lampp/bin/mysql', '-u', 'root', 'crud_proyecto', '-e',
        """
        DELETE FROM mesas;
        INSERT INTO mesas (numero_mesa, capacidad_minima, capacidad_maxima, precio_reserva, ubicacion, estado) VALUES
        ('M01', 2, 4, 5.00, 'interior', 'disponible'),
        ('M02', 4, 6, 7.00, 'interior', 'disponible'),
        ('T01', 4, 8, 10.00, 'terraza', 'disponible'),
        ('V01', 2, 4, 15.00, 'vip', 'disponible'),
        ('B01', 2, 4, 6.00, 'bar', 'disponible');
        """
    ], capture_output=True)
    
    resultados = []
    
    # Fechas de prueba
    hoy = datetime.now().strftime('%Y-%m-%d')
    ayer = (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d')
    semana_pasada = (datetime.now() - timedelta(days=7)).strftime('%Y-%m-%d')
    mes_pasado = (datetime.now() - timedelta(days=30)).strftime('%Y-%m-%d')
    manana = (datetime.now() + timedelta(days=1)).strftime('%Y-%m-%d')
    siete_meses = (datetime.now() + timedelta(days=210)).strftime('%Y-%m-%d')
    
    # =============================================
    # GRUPO 1: VALIDACIÓN DE FECHAS (12 tests)
    # =============================================
    
    print("\n📅 GRUPO 1: Validación de Fechas")
    
    # 1 - Fecha vacía
    resultados.append(reservar_zona(["interior"], "", "19:00", 10, "Fecha vacía"))
    
    # 2 - Fecha ayer
    resultados.append(reservar_zona(["interior"], ayer, "19:00", 10, f"Fecha pasada (ayer: {ayer})"))
    
    # 3 - Fecha hace 1 semana
    resultados.append(reservar_zona(["terraza"], semana_pasada, "20:00", 15, f"Fecha hace 1 semana ({semana_pasada})"))
    
    # 4 - Fecha hace 1 mes
    resultados.append(reservar_zona(["vip"], mes_pasado, "21:00", 8, f"Fecha hace 1 mes ({mes_pasado})"))
    
    # 5 - Fecha año 3000
    resultados.append(reservar_zona(["bar"], "3000-12-31", "19:00", 5, "Fecha año 3000 (muy lejana)"))
    
    # 6 - Fecha año 2100
    resultados.append(reservar_zona(["interior"], "2100-01-01", "18:00", 12, "Fecha año 2100"))
    
    # 7 - Fecha 7 meses adelante
    resultados.append(reservar_zona(["terraza"], siete_meses, "19:30", 20, f"Fecha 7 meses ({siete_meses}) >6 meses"))
    
    # 8 - Formato inválido DD/MM/YYYY
    resultados.append(reservar_zona(["vip"], "31/12/2026", "20:00", 6, "Fecha formato DD/MM/YYYY"))
    
    # 9 - Fecha con texto
    resultados.append(reservar_zona(["bar"], "mañana", "19:00", 4, "Fecha texto 'mañana'"))
    
    # 10 - SQL injection en fecha
    resultados.append(reservar_zona(["interior"], "2026-01-01' OR '1'='1", "18:00", 10, "SQL injection en fecha"))
    
    # 11 - Fecha XSS
    resultados.append(reservar_zona(["terraza"], "<script>alert('xss')</script>", "19:00", 8, "XSS en fecha"))
    
    # 12 - Fecha null/None
    resultados.append(reservar_zona(["bar"], None, "19:00", 5, "Fecha None/null"))
    
    # =============================================
    # GRUPO 2: VALIDACIÓN DE HORARIOS (6 tests)
    # =============================================
    
    print("\n🕐 GRUPO 2: Validación de Horarios")
    
    # 13 - Hora vacía
    resultados.append(reservar_zona(["interior"], manana, "", 10, "Hora vacía"))
    
    # 14 - Hora antes de apertura (06:00)
    resultados.append(reservar_zona(["terraza"], manana, "06:00", 8, "Hora 06:00 (antes apertura)"))
    
    # 15 - Hora después de cierre (02:00)
    resultados.append(reservar_zona(["vip"], manana, "02:00", 6, "Hora 02:00 (después cierre)"))
    
    # 16 - Hora formato inválido '7pm'
    resultados.append(reservar_zona(["bar"], manana, "7pm", 5, "Hora formato '7pm'"))
    
    # 17 - Hora inválida 25:00
    resultados.append(reservar_zona(["interior"], manana, "25:00", 10, "Hora 25:00 (inválida)"))
    
    # 18 - Hora XSS
    resultados.append(reservar_zona(["terraza"], manana, "<script>alert('xss')</script>", 8, "XSS en hora"))
    
    # =============================================
    # GRUPO 3: VALIDACIÓN DE DISPONIBILIDAD (7 tests)
    # =============================================
    
    print("\n🪑 GRUPO 3: Validación de Disponibilidad")
    
    # 19 - Reservar sin mesas en BD
    subprocess.run(['/opt/lampp/bin/mysql', '-u', 'root', 'crud_proyecto', '-e', 'DELETE FROM mesas'], 
                   capture_output=True)
    resultados.append(reservar_zona(["interior"], manana, "19:00", 10, "Sin mesas en BD"))
    
    # Restaurar mesas pero OCUPADAS
    subprocess.run([
        '/opt/lampp/bin/mysql', '-u', 'root', 'crud_proyecto', '-e',
        """
        INSERT INTO mesas (numero_mesa, capacidad_minima, capacidad_maxima, precio_reserva, ubicacion, estado) VALUES
        ('M01', 2, 4, 5.00, 'interior', 'ocupada'),
        ('M02', 4, 6, 7.00, 'interior', 'ocupada');
        """
    ], capture_output=True)
    
    # 20 - Zona con solo mesas ocupadas
    resultados.append(reservar_zona(["interior"], manana, "20:00", 8, "Zona solo mesas ocupadas"))
    
    # Restaurar disponibles
    subprocess.run([
        '/opt/lampp/bin/mysql', '-u', 'root', 'crud_proyecto', '-e',
        """
        DELETE FROM mesas;
        INSERT INTO mesas (numero_mesa, capacidad_minima, capacidad_maxima, precio_reserva, ubicacion, estado) VALUES
        ('M01', 2, 4, 5.00, 'interior', 'disponible'),
        ('T01', 4, 8, 10.00, 'terraza', 'disponible');
        """
    ], capture_output=True)
    
    # 21 - Array de zonas vacío
    resultados.append(reservar_zona([], manana, "19:00", 10, "Array zonas vacío []"))
    
    # 22 - Zona inexistente
    resultados.append(reservar_zona(["jardin", "piscina"], manana, "19:00", 15, "Zonas inexistentes"))
    
    # 23 - SQL injection en zona
    resultados.append(reservar_zona(["interior' OR '1'='1"], manana, "19:00", 10, "SQL injection en zona"))
    
    # 24 - XSS en zona
    resultados.append(reservar_zona(["<script>alert('xss')</script>"], manana, "19:00", 8, "XSS en zona"))
    
    # 25 - Zona None/null
    resultados.append(reservar_zona(None, manana, "19:00", 10, "Zonas None/null"))
    
    # =============================================
    # GRUPO 4: VALIDACIÓN DE PERSONAS (6 tests)
    # =============================================
    
    print("\n👥 GRUPO 4: Validación de Número de Personas")
    
    # 26 - 0 personas
    resultados.append(reservar_zona(["interior"], manana, "19:00", 0, "0 personas"))
    
    # 27 - Número negativo
    resultados.append(reservar_zona(["terraza"], manana, "20:00", -5, "Personas negativas (-5)"))
    
    # 28 - Número excesivo (1000)
    resultados.append(reservar_zona(["interior"], manana, "19:00", 1000, "1000 personas (excesivo)"))
    
    # 29 - Personas como texto
    resultados.append(reservar_zona(["vip"], manana, "21:00", "diez", "Personas como texto 'diez'"))
    
    # 30 - Campo personas None
    resultados.append(reservar_zona(["bar"], manana, "19:00", None, "Personas None/null"))
    
    # 31 - XSS en personas
    resultados.append(reservar_zona(["interior"], manana, "19:00", "<script>alert('xss')</script>", 
                                    "XSS en número personas"))
    
    # =============================================
    # GRUPO 5: ESTRÉS ADICIONAL - CASOS EXTREMOS (19 tests)
    # =============================================
    
    print("\n🔥 GRUPO 5: Pruebas de Estrés Adicionales")
    
    # 32-35 - Múltiples zonas simultáneas
    resultados.append(reservar_zona(["interior", "terraza"], manana, "19:00", 20, 
                                    "Múltiples zonas válidas", debe_pasar=True))
    resultados.append(reservar_zona(["interior", "terraza", "vip", "bar"], manana, "20:00", 50, 
                                    "Todas las zonas simultáneas", debe_pasar=True))
    resultados.append(reservar_zona(["interior", "jardin"], manana, "19:00", 15, 
                                    "Zona válida + zona inexistente"))
    resultados.append(reservar_zona(["<script>", "interior"], manana, "19:00", 10, 
                                    "XSS + zona válida en array"))
    
    # 36-40 - Combinaciones de fechas/horas límite
    resultados.append(reservar_zona(["interior"], hoy, "00:00", 5, 
                                    "Hoy medianoche (hora límite)"))
    resultados.append(reservar_zona(["terraza"], hoy, "23:59", 8, 
                                    "Hoy 23:59 (hora límite)"))
    
    # Fecha exactamente 6 meses
    seis_meses = (datetime.now() + timedelta(days=180)).strftime('%Y-%m-%d')
    resultados.append(reservar_zona(["vip"], seis_meses, "19:00", 10, 
                                    f"Fecha exacta 6 meses ({seis_meses})", debe_pasar=True))
    
    # Fecha 6 meses + 1 día
    seis_meses_un_dia = (datetime.now() + timedelta(days=181)).strftime('%Y-%m-%d')
    resultados.append(reservar_zona(["bar"], seis_meses_un_dia, "19:00", 10, 
                                    f"Fecha 6 meses + 1 día ({seis_meses_un_dia})"))
    
    # Fecha límite año
    resultados.append(reservar_zona(["interior"], "2026-12-31", "23:59", 15, 
                                    "Fin de año 2026 23:59"))
    
    # 41-45 - Ataques SQL injection avanzados
    resultados.append(reservar_zona(["interior"], manana, "19:00' OR '1'='1", 10, 
                                    "SQL injection en hora (OR)"))
    resultados.append(reservar_zona(["interior"], manana, "19:00; DROP TABLE mesas; --", 10, 
                                    "SQL injection DROP TABLE en hora"))
    resultados.append(reservar_zona(["interior' UNION SELECT * FROM clientes --"], manana, "19:00", 10, 
                                    "SQL injection UNION en zona"))
    resultados.append(reservar_zona(["interior"], manana, "19:00", "10 OR 1=1", 
                                    "SQL injection en personas (texto)"))
    resultados.append(reservar_zona(["interior"], "2026-01-20' AND 1=0 UNION SELECT NULL,NULL,NULL --", 
                                    "19:00", 10, "SQL injection UNION en fecha"))
    
    # 46-50 - Payloads maliciosos completos
    resultados.append(reservar_zona(
        ["<img src=x onerror=alert(1)>"], 
        "<script>document.location='http://evil.com'</script>", 
        "<iframe src='javascript:alert(1)'>", 
        "<svg onload=alert(1)>", 
        "XSS en todos los campos"))
    
    resultados.append(reservar_zona(["interior"], manana, "19:00", -999999, 
                                    "Personas número muy negativo"))
    resultados.append(reservar_zona(["interior"], manana, "19:00", 2147483647, 
                                    "Personas MAX_INT (overflow)"))
    resultados.append(reservar_zona(["interior"], manana, "19:00", 0.5, 
                                    "Personas decimal (0.5)"))
    resultados.append(reservar_zona([""], manana, "19:00", 10, 
                                    "Zona string vacío en array"))
    
    # =============================================
    # GUARDAR RESULTADOS
    # =============================================
    
    # Guardar en formato compatible con generar-reporte.php (array directo)
    with open(OUTPUT_FILE, 'w', encoding='utf-8') as f:
        json.dump(resultados, f, indent=2, ensure_ascii=False)
    
    total = len(resultados)
    pasados = sum(1 for r in resultados if r.get("paso"))
    fallados = total - pasados
    resultado_final = {
        "total": total,
        "pasados": pasados,
        "fallados": fallados
    }
    
    total = resultado_final['total']
    pasados = resultado_final['pasados']
    porcentaje = (pasados/total*100) if total > 0 else 0
    
    print(f"\n{'='*60}")
    print(f"✅ Pasados: {pasados}/{total} ({porcentaje:.1f}%)")
    print(f"❌ Fallados: {resultado_final['fallados']}")
    print(f"📄 Guardado en: {OUTPUT_FILE}")
    print(f"{'='*60}\n")
    
    return 0 if pasados == total else 1


if __name__ == '__main__':
    sys.exit(main())
