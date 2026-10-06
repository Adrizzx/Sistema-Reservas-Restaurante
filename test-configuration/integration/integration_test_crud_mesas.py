#!/usr/bin/env python3
"""
INTEGRATION TEST: CRUD Completo de Mesas
Prueba el flujo completo: API → Base de Datos → Verificación
- Crear mesa
- Leer mesa desde BD
- Actualizar mesa
- Verificar actualización en BD
- Eliminar mesa
- Verificar eliminación en BD
- Casos de error: duplicados, ID inexistente, reservas activas
"""

import requests
import json
import os
import subprocess
from datetime import datetime

BASE = "http://localhost/PRY_PROYECTO"
SESSION = requests.Session()
REPORT_DIR = os.path.join(os.path.dirname(__file__), "reportes")
OUTPUT_FILE = os.path.join(REPORT_DIR, "ultimo-resultado-integration-crud-mesas.json")
os.makedirs(REPORT_DIR, exist_ok=True)


def ejecutar_sql(query):
    """Ejecutar query SQL directamente y retornar resultado"""
    try:
        result = subprocess.run(
            ['/opt/lampp/bin/mysql', '-u', 'root', 'crud_proyecto', '-e', query],
            capture_output=True, text=True, timeout=5
        )
        return result.stdout
    except Exception as e:
        return f"ERROR: {str(e)}"


def safe_request(method, url, session, **kwargs):
    """Wrapper para requests con manejo de errores"""
    try:
        resp = session.request(method, url, timeout=10, **kwargs)
        return {
            'status': resp.status_code,
            'elapsed': resp.elapsed.total_seconds(),
            'data': resp.json() if resp.headers.get('Content-Type', '').startswith('application/json') else resp.text
        }
    except Exception as e:
        return {'status': 0, 'elapsed': 0, 'data': {'success': False, 'message': str(e)}}


def result(nombre, panel, accion, esperado, paso, respuesta):
    """Formato estándar de resultado"""
    return {
        "nombre": nombre,
        "panel": panel,
        "accion": accion,
        "esperado": esperado,
        "paso": paso,
        "respuesta": respuesta
    }


def verificar_mesa_en_bd(numero_mesa=None, mesa_id=None):
    """Verificar si una mesa existe en la BD y retornar sus datos"""
    if numero_mesa:
        query = f"SELECT * FROM mesas WHERE numero_mesa = '{numero_mesa}'"
    elif mesa_id:
        query = f"SELECT * FROM mesas WHERE id = {mesa_id}"
    else:
        return None
    
    output = ejecutar_sql(query)
    # Si hay datos (más de una línea), la mesa existe
    lines = output.strip().split('\n')
    return len(lines) > 1


def contar_mesas():
    """Contar total de mesas en BD"""
    output = ejecutar_sql("SELECT COUNT(*) as total FROM mesas")
    lines = output.strip().split('\n')
    if len(lines) > 1:
        return lines[1].strip()
    return "0"


def crear_mesa(numero, cap_min, cap_max, ubicacion, nombre_test):
    """Helper para crear una mesa"""
    payload = {
        "numero_mesa": numero,
        "capacidad_minima": cap_min,
        "capacidad_maxima": cap_max,
        "ubicacion": ubicacion,
        "estado": "disponible",
        "descripcion": f"Mesa de prueba {nombre_test}"
    }
    
    res = safe_request("POST", f"{BASE}/app/agregar_mesa.php", SESSION, json=payload)
    data = res.get("data", {})
    
    return data


def actualizar_mesa(mesa_id, numero, cap_min, cap_max, ubicacion):
    """Helper para actualizar una mesa"""
    payload = {
        "id": mesa_id,
        "numero_mesa": numero,
        "capacidad_minima": cap_min,
        "capacidad_maxima": cap_max,
        "ubicacion": ubicacion,
        "estado": "disponible",
        "descripcion": "Mesa actualizada"
    }
    
    res = safe_request("POST", f"{BASE}/app/editar_mesa.php", SESSION, json=payload)
    return res.get("data", {})


def eliminar_mesa(mesa_id):
    """Helper para eliminar una mesa"""
    payload = {"id": mesa_id}
    res = safe_request("POST", f"{BASE}/app/eliminar_mesa.php", SESSION, json=payload)
    return res.get("data", {})


def main():
    """Ejecuta todos los Integration Tests de CRUD Mesas"""
    print("\n🔄 INTEGRATION TESTS: CRUD COMPLETO DE MESAS")
    print("=" * 70)
    
    resultados = []
    
    # Limpiar mesas de prueba anteriores
    print("\n🧹 Limpiando mesas de prueba...")
    ejecutar_sql("DELETE FROM mesas WHERE numero_mesa LIKE 'IT_%'")
    
    # ========================================
    # FLUJO 1: CREAR → VERIFICAR EN BD
    # ========================================
    print("\n📝 FLUJO 1: Crear Mesa → Verificar en BD")
    
    # 1.1 - Crear mesa válida
    datos_mesa = crear_mesa("IT_001", 2, 4, "interior", "válida")
    exito_crear = datos_mesa.get("success") is True
    mesa_id = datos_mesa.get("id")
    
    # Verificar que se creó en BD
    existe_en_bd = verificar_mesa_en_bd("IT_001")
    
    resultados.append(result(
        nombre="CREAR mesa válida + verificar BD",
        panel="Integration: Agregar Mesa → BD",
        accion=f"POST agregar_mesa.php → SELECT FROM mesas WHERE numero_mesa='IT_001'",
        esperado="Mesa creada en API Y existe en BD",
        paso=exito_crear and existe_en_bd,
        respuesta={
            "api_response": datos_mesa,
            "id_generado": mesa_id,
            "existe_en_bd": existe_en_bd
        }
    ))
    
    # 1.2 - Intentar crear mesa duplicada
    datos_duplicado = crear_mesa("IT_001", 2, 4, "terraza", "duplicada")
    debe_fallar = datos_duplicado.get("success") is False
    mensaje_correcto = "ya existe" in datos_duplicado.get("message", "").lower()
    
    resultados.append(result(
        nombre="CREAR mesa duplicada debe fallar",
        panel="Integration: Agregar Mesa (validación)",
        accion="POST agregar_mesa.php con numero_mesa existente",
        esperado="API rechaza duplicado Y no crea nueva fila en BD",
        paso=debe_fallar and mensaje_correcto,
        respuesta=datos_duplicado
    ))
    
    # 1.3 - Crear segunda mesa para pruebas posteriores
    datos_mesa2 = crear_mesa("IT_002", 4, 6, "vip", "segunda")
    mesa_id2 = datos_mesa2.get("id")
    
    # ========================================
    # FLUJO 2: ACTUALIZAR → VERIFICAR EN BD
    # ========================================
    print("\n✏️ FLUJO 2: Actualizar Mesa → Verificar cambios en BD")
    
    if mesa_id:
        # 2.1 - Actualizar mesa existente
        datos_update = actualizar_mesa(mesa_id, "IT_001_EDIT", 2, 8, "bar")
        exito_update = datos_update.get("success") is True
        
        # Verificar cambios en BD
        output_bd = ejecutar_sql(f"SELECT numero_mesa, capacidad_maxima, ubicacion FROM mesas WHERE id = {mesa_id}")
        cambios_aplicados = "IT_001_EDIT" in output_bd and "8" in output_bd and "bar" in output_bd
        
        resultados.append(result(
            nombre="ACTUALIZAR mesa + verificar cambios en BD",
            panel="Integration: Editar Mesa → BD",
            accion=f"POST editar_mesa.php id={mesa_id} → SELECT FROM mesas WHERE id={mesa_id}",
            esperado="API actualiza Y cambios reflejados en BD (numero_mesa, capacidad, ubicacion)",
            paso=exito_update and cambios_aplicados,
            respuesta={
                "api_response": datos_update,
                "bd_output": output_bd,
                "cambios_verificados": cambios_aplicados
            }
        ))
        
        # 2.2 - Intentar actualizar con número de mesa duplicado (de IT_002)
        datos_duplicado_update = actualizar_mesa(mesa_id, "IT_002", 2, 8, "bar")
        debe_fallar_dup = datos_duplicado_update.get("success") is False
        mensaje_duplicado = "ya existe" in datos_duplicado_update.get("message", "").lower()
        
        # Verificar que NO cambió en BD
        output_bd_sin_cambio = ejecutar_sql(f"SELECT numero_mesa FROM mesas WHERE id = {mesa_id}")
        no_cambio = "IT_001_EDIT" in output_bd_sin_cambio  # Debe mantener el nombre anterior
        
        resultados.append(result(
            nombre="ACTUALIZAR con número duplicado debe fallar",
            panel="Integration: Editar Mesa (validación)",
            accion=f"POST editar_mesa.php intentar cambiar a numero_mesa existente",
            esperado="API rechaza Y BD no cambia numero_mesa",
            paso=debe_fallar_dup and mensaje_duplicado and no_cambio,
            respuesta={
                "api_response": datos_duplicado_update,
                "bd_mantiene_original": no_cambio
            }
        ))
        
        # 2.3 - Actualizar mesa inexistente
        datos_update_fake = actualizar_mesa(99999, "IT_FAKE", 2, 4, "interior")
        # Esto NO debe fallar necesariamente (UPDATE con 0 rows affected es válido)
        # Verificar que no se creó nueva mesa
        output_fake = ejecutar_sql("SELECT COUNT(*) FROM mesas WHERE id = 99999")
        no_existe = "0" in output_fake
        
        resultados.append(result(
            nombre="ACTUALIZAR mesa inexistente (id=99999)",
            panel="Integration: Editar Mesa",
            accion="POST editar_mesa.php con id inexistente → verificar BD",
            esperado="API no crea nueva fila (UPDATE afecta 0 rows)",
            paso=no_existe,
            respuesta={
                "api_response": datos_update_fake,
                "bd_no_crea_mesa": no_existe
            }
        ))
    
    # ========================================
    # FLUJO 3: ELIMINAR CON RESERVAS ACTIVAS
    # ========================================
    print("\n🗑️ FLUJO 3: Eliminar Mesa con Reservas Activas")
    
    if mesa_id2:
        # 3.1 - Crear reserva activa para IT_002
        ejecutar_sql(f"""
            INSERT INTO reservas (cliente_id, mesa_id, fecha_reserva, hora_reserva, numero_personas, estado)
            VALUES (1, {mesa_id2}, '2026-01-15', '19:00', 4, 'confirmada')
        """)
        
        # Intentar eliminar mesa con reserva activa
        datos_delete_con_reserva = eliminar_mesa(mesa_id2)
        debe_fallar_delete = datos_delete_con_reserva.get("success") is False
        mensaje_reservas = "reservas activas" in datos_delete_con_reserva.get("message", "").lower()
        
        # Verificar que la mesa AÚN existe en BD
        mesa_sigue_existiendo = verificar_mesa_en_bd(mesa_id=mesa_id2)
        
        resultados.append(result(
            nombre="ELIMINAR mesa con reservas activas debe fallar",
            panel="Integration: Eliminar Mesa (validación)",
            accion=f"POST eliminar_mesa.php con reserva confirmada → verificar mesa en BD",
            esperado="API rechaza eliminación Y mesa permanece en BD",
            paso=debe_fallar_delete and mensaje_reservas and mesa_sigue_existiendo,
            respuesta={
                "api_response": datos_delete_con_reserva,
                "mesa_existe_en_bd": mesa_sigue_existiendo
            }
        ))
        
        # Limpiar reserva de prueba
        ejecutar_sql(f"DELETE FROM reservas WHERE mesa_id = {mesa_id2}")
    
    # ========================================
    # FLUJO 4: ELIMINAR → VERIFICAR EN BD
    # ========================================
    print("\n🗑️ FLUJO 4: Eliminar Mesa → Verificar eliminación en BD")
    
    if mesa_id:
        # Contar mesas antes
        total_antes = contar_mesas()
        
        # 4.1 - Eliminar mesa sin reservas
        datos_delete = eliminar_mesa(mesa_id)
        exito_delete = datos_delete.get("success") is True
        
        # Verificar que NO existe en BD
        no_existe_bd = not verificar_mesa_en_bd(mesa_id=mesa_id)
        
        # Contar mesas después
        total_despues = contar_mesas()
        
        resultados.append(result(
            nombre="ELIMINAR mesa + verificar desaparición en BD",
            panel="Integration: Eliminar Mesa → BD",
            accion=f"POST eliminar_mesa.php id={mesa_id} → SELECT FROM mesas WHERE id={mesa_id}",
            esperado="API elimina Y mesa no existe en BD",
            paso=exito_delete and no_existe_bd,
            respuesta={
                "api_response": datos_delete,
                "mesa_eliminada_de_bd": no_existe_bd,
                "total_antes": total_antes,
                "total_despues": total_despues
            }
        ))
        
        # 4.2 - Intentar eliminar mesa ya eliminada
        datos_delete2 = eliminar_mesa(mesa_id)
        # Esto puede no fallar (DELETE con 0 rows affected es válido SQL)
        # Verificar que sigue sin existir
        sigue_sin_existir = not verificar_mesa_en_bd(mesa_id=mesa_id)
        
        resultados.append(result(
            nombre="ELIMINAR mesa ya eliminada (idempotencia)",
            panel="Integration: Eliminar Mesa",
            accion=f"POST eliminar_mesa.php con id ya eliminado",
            esperado="Mesa sigue sin existir en BD (operación idempotente)",
            paso=sigue_sin_existir,
            respuesta={
                "api_response": datos_delete2,
                "no_existe_en_bd": sigue_sin_existir
            }
        ))
    
    # ========================================
    # FLUJO 5: CICLO COMPLETO CRUD
    # ========================================
    print("\n🔄 FLUJO 5: Ciclo Completo CREATE → READ → UPDATE → DELETE")
    
    # Crear
    datos_ciclo = crear_mesa("IT_CICLO", 2, 6, "terraza", "ciclo completo")
    id_ciclo = datos_ciclo.get("id")
    paso1_crear = datos_ciclo.get("success") is True and verificar_mesa_en_bd("IT_CICLO")
    
    # Leer desde BD
    output_read = ejecutar_sql(f"SELECT * FROM mesas WHERE id = {id_ciclo}")
    paso2_leer = "IT_CICLO" in output_read and "terraza" in output_read
    
    # Actualizar
    datos_ciclo_update = actualizar_mesa(id_ciclo, "IT_CICLO_MOD", 2, 10, "vip")
    output_update = ejecutar_sql(f"SELECT numero_mesa, capacidad_maxima, ubicacion FROM mesas WHERE id = {id_ciclo}")
    paso3_update = datos_ciclo_update.get("success") is True and "IT_CICLO_MOD" in output_update and "10" in output_update
    
    # Eliminar
    datos_ciclo_delete = eliminar_mesa(id_ciclo)
    paso4_delete = datos_ciclo_delete.get("success") is True and not verificar_mesa_en_bd(mesa_id=id_ciclo)
    
    ciclo_completo_ok = paso1_crear and paso2_leer and paso3_update and paso4_delete
    
    resultados.append(result(
        nombre="CICLO COMPLETO: CREATE → READ → UPDATE → DELETE",
        panel="Integration: CRUD Completo",
        accion="Crear mesa → Leer BD → Actualizar → Verificar BD → Eliminar → Verificar BD",
        esperado="Todo el ciclo funciona correctamente sin errores",
        paso=ciclo_completo_ok,
        respuesta={
            "paso_1_crear": paso1_crear,
            "paso_2_leer": paso2_leer,
            "paso_3_update": paso3_update,
            "paso_4_delete": paso4_delete,
            "ciclo_completo": ciclo_completo_ok
        }
    ))
    
    # ========================================
    # FLUJO 6: VALIDACIONES DE DATOS
    # ========================================
    print("\n✅ FLUJO 6: Validaciones de Datos Inválidos")
    
    # 6.1 - Crear mesa sin número
    datos_sin_numero = crear_mesa("", 2, 4, "interior", "sin numero")
    debe_fallar = datos_sin_numero.get("success") is False
    
    resultados.append(result(
        nombre="VALIDACIÓN: Crear mesa sin numero_mesa",
        panel="Integration: Validaciones",
        accion="POST agregar_mesa.php con numero_mesa vacío",
        esperado="API rechaza y no crea en BD",
        paso=debe_fallar and not verificar_mesa_en_bd(""),
        respuesta=datos_sin_numero
    ))
    
    # 6.2 - Crear mesa sin capacidad_maxima
    payload_sin_cap = {
        "numero_mesa": "IT_NOCAP",
        "capacidad_minima": 2,
        "ubicacion": "interior"
    }
    res_sin_cap = safe_request("POST", f"{BASE}/app/agregar_mesa.php", SESSION, json=payload_sin_cap)
    datos_sin_cap = res_sin_cap.get("data", {})
    debe_fallar_cap = datos_sin_cap.get("success") is False
    
    resultados.append(result(
        nombre="VALIDACIÓN: Crear mesa sin capacidad_maxima",
        panel="Integration: Validaciones",
        accion="POST agregar_mesa.php sin campo capacidad_maxima",
        esperado="API rechaza y no crea en BD",
        paso=debe_fallar_cap and not verificar_mesa_en_bd("IT_NOCAP"),
        respuesta=datos_sin_cap
    ))
    
    # 6.3 - Actualizar sin ID
    payload_sin_id = {
        "numero_mesa": "IT_NOID",
        "capacidad_minima": 2,
        "capacidad_maxima": 4,
        "ubicacion": "interior"
    }
    res_sin_id = safe_request("POST", f"{BASE}/app/editar_mesa.php", SESSION, json=payload_sin_id)
    datos_sin_id = res_sin_id.get("data", {})
    debe_fallar_id = datos_sin_id.get("success") is False
    
    resultados.append(result(
        nombre="VALIDACIÓN: Actualizar mesa sin ID",
        panel="Integration: Validaciones",
        accion="POST editar_mesa.php sin campo id",
        esperado="API rechaza la actualización",
        paso=debe_fallar_id,
        respuesta=datos_sin_id
    ))
    
    # ========================================
    # RESUMEN
    # ========================================
    print("\n" + "=" * 70)
    
    total = len(resultados)
    pasados = sum(1 for r in resultados if r["paso"])
    fallados = total - pasados
    porcentaje = (pasados / total * 100) if total > 0 else 0
    
    print(f"✅ Pasados: {pasados}/{total} ({porcentaje:.1f}%)")
    print(f"❌ Fallados: {fallados}")
    
    # Guardar resultados
    with open(OUTPUT_FILE, 'w', encoding='utf-8') as f:
        json.dump(resultados, f, indent=2, ensure_ascii=False)
    
    print(f"📄 Guardado en: {OUTPUT_FILE}")
    print("=" * 70)
    
    # Mostrar tests fallados
    if fallados > 0:
        print("\n❌ TESTS FALLADOS:")
        for r in resultados:
            if not r["paso"]:
                print(f"  - {r['nombre']}")
    
    return 0 if fallados == 0 else 1


if __name__ == "__main__":
    exit(main())
