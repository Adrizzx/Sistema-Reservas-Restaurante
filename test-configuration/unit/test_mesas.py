#!/usr/bin/env python3
"""
Tests para GESTIÓN DE MESAS (admin.php)
- CRUD: agregar, editar, eliminar, listar
- Validaciones de capacidad, ubicación, estado, duplicados
"""
import os
import json
import random
import string
from typing import List, Dict

try:
    import requests
except Exception as e:
    print("❌ Falta 'requests'. Instala: python3 -m pip install -r testing/code/python/requirements.txt")
    raise

from common import get_base_url, make_session, safe_request, result, merge_results

BASE = get_base_url()
SESSION = make_session()

AGREGAR_URL = f"{BASE}/app/agregar_mesa.php"
EDITAR_URL = f"{BASE}/app/editar_mesa.php"
ELIMINAR_URL = f"{BASE}/app/eliminar_mesa.php"
LISTAR_URL = f"{BASE}/app/obtener_mesas.php"

REPORT_DIR = os.path.join(os.path.dirname(__file__), "reportes")
OUTPUT_FILE = os.path.join(REPORT_DIR, "ultimo-resultado-mesas.json")
os.makedirs(REPORT_DIR, exist_ok=True)


def _rand_num(prefix: str = "T") -> str:
    return prefix + ''.join(random.choice(string.digits) for _ in range(3))


def build_payload(numero: str, cap_min: int, cap_max: int, ubicacion: str, estado: str, desc: str = None) -> Dict:
    return {"numero_mesa": numero, "capacidad_minima": cap_min, "capacidad_maxima": cap_max, "ubicacion": ubicacion, "estado": estado, "descripcion": desc}


def agregar(payload: Dict, nombre: str) -> Dict:
    res = safe_request("POST", AGREGAR_URL, SESSION, json=payload)
    data = res["data"]
    ok = isinstance(data, dict) and data.get("success") is True and data.get("id")
    return result(nombre=nombre, panel="Gestión de Mesas", accion=f"Agregar: {payload}", esperado="Respetar reglas (unicidad, capacidad<=15, estado/ubicación válidos)", paso=ok, respuesta=data if isinstance(data, dict) else {})


def editar(payload: Dict, nombre: str) -> Dict:
    res = safe_request("POST", EDITAR_URL, SESSION, json=payload)
    data = res["data"]
    ok = isinstance(data, dict) and data.get("success") is True
    return result(nombre=nombre, panel="Gestión de Mesas", accion=f"Editar: {payload}", esperado="Actualizar sólo si datos válidos", paso=ok, respuesta=data if isinstance(data, dict) else {})


def eliminar(mesa_id: int, nombre: str) -> Dict:
    res = safe_request("POST", ELIMINAR_URL, SESSION, json={"id": mesa_id})
    data = res["data"]
    ok = isinstance(data, dict) and data.get("success") is True
    return result(nombre=nombre, panel="Gestión de Mesas", accion=f"Eliminar id={mesa_id}", esperado="Eliminar si no tiene reservas activas", paso=ok, respuesta=data if isinstance(data, dict) else {})


def listar(nombre: str) -> Dict:
    res = safe_request("GET", LISTAR_URL, SESSION)
    data = res["data"]
    ok = isinstance(data, dict) and data.get("success") is True and isinstance(data.get("mesas"), list)
    return result(nombre=nombre, panel="Gestión de Mesas", accion="Listar mesas", esperado="Devolver listado de mesas", paso=ok, respuesta=data if isinstance(data, dict) else {})


def suite_mesas() -> List[Dict]:
    r: List[Dict] = []
    
    r.append(listar("Listar inicial"))
    
    # Crear base válida
    num1 = _rand_num("A")
    p_ok = build_payload(num1, 1, 4, "interior", "disponible", "Mesa test OK")
    r1 = agregar(p_ok, f"Agregar mesa válida {num1}")
    r.append(r1)
    mesa1_id = r1["respuesta"].get("id") if isinstance(r1.get("respuesta"), dict) else None
    
    # Duplicado número (debe fallar)
    p_dup = build_payload(num1, 1, 4, "interior", "disponible")
    r_dup = agregar(p_dup, "Duplicado (debe fallar)")
    msg = r_dup["respuesta"].get("message") if isinstance(r_dup["respuesta"], dict) else ""
    r_dup["paso"] = isinstance(r_dup["respuesta"], dict) and (r_dup["respuesta"].get("success") is False) and ("ya existe" in (msg or ""))
    r.append(r_dup)
    
    # Capacidad 0 (debe fallar)
    num2 = _rand_num("B")
    r_cap0 = agregar(build_payload(num2, 0, 0, "interior", "disponible"), "Capacidad 0 (debe fallar)")
    r_cap0["paso"] = isinstance(r_cap0["respuesta"], dict) and (r_cap0["respuesta"].get("success") is False)
    r.append(r_cap0)
    
    # Capacidad >15 (debe fallar)
    num3 = _rand_num("C")
    r_cap100 = agregar(build_payload(num3, 1, 100, "interior", "disponible"), "Capacidad 100 (debe fallar)")
    r_cap100["paso"] = isinstance(r_cap100["respuesta"], dict) and (r_cap100["respuesta"].get("success") is False)
    r.append(r_cap100)
    
    # Ubicación inválida (debe fallar)
    num4 = _rand_num("D")
    r_bad_loc = agregar(build_payload(num4, 1, 4, "patio", "disponible"), "Ubicación inválida (debe fallar)")
    r_bad_loc["paso"] = isinstance(r_bad_loc["respuesta"], dict) and (r_bad_loc["respuesta"].get("success") is False)
    r.append(r_bad_loc)
    
    # Estado inválido (debe fallar)
    num5 = _rand_num("E")
    r_bad_state = agregar(build_payload(num5, 1, 4, "interior", "invalido"), "Estado inválido (debe fallar)")
    r_bad_state["paso"] = isinstance(r_bad_state["respuesta"], dict) and (r_bad_state["respuesta"].get("success") is False)
    r.append(r_bad_state)
    
    # Descripción larga (debe fallar)
    num6 = _rand_num("F")
    r_desc = agregar(build_payload(num6, 1, 4, "interior", "disponible", "x" * 300), "Descripción larga (debe fallar)")
    r_desc["paso"] = isinstance(r_desc["respuesta"], dict) and (r_desc["respuesta"].get("success") is False)
    r.append(r_desc)
    
    # XSS en descripción (debe fallar)
    num7 = _rand_num("G")
    r_xss = agregar(build_payload(num7, 1, 4, "interior", "disponible", "<script>alert(1)</script>"), "Descripción XSS (debe fallar)")
    r_xss["paso"] = isinstance(r_xss["respuesta"], dict) and (r_xss["respuesta"].get("success") is False)
    r.append(r_xss)
    
    # Editar válida
    if mesa1_id:
        r.append(editar({"id": mesa1_id, "numero_mesa": num1, "capacidad_minima": 1, "capacidad_maxima": 6, "ubicacion": "interior", "estado": "disponible", "descripcion": "Actualizada"}, "Editar mesa válida"))
    
    # Crear otra y editar con duplicado
    num8 = _rand_num("H")
    r_create8 = agregar(build_payload(num8, 1, 4, "interior", "disponible"), f"Crear base {num8}")
    r.append(r_create8)
    mesa8_id = r_create8["respuesta"].get("id") if isinstance(r_create8.get("respuesta"), dict) else None
    
    if mesa1_id and mesa8_id:
        r_edit_dup = editar({"id": mesa8_id, "numero_mesa": num1, "capacidad_minima": 1, "capacidad_maxima": 4, "ubicacion": "interior", "estado": "disponible", "descripcion": "dup"}, "Editar con duplicado (debe fallar)")
        r_edit_dup["paso"] = isinstance(r_edit_dup["respuesta"], dict) and (r_edit_dup["respuesta"].get("success") is False)
        r.append(r_edit_dup)
    
    # Eliminar mesas creadas
    for rid, label in [(mesa1_id, "Eliminar mesa1"), (mesa8_id, "Eliminar mesa8")]:
        if rid:
            r.append(eliminar(rid, label))
    
    r.append(listar("Listar final"))
    
    return r


def main():
    tests = suite_mesas()
    
    existing = None
    if os.path.exists(OUTPUT_FILE):
        try:
            with open(OUTPUT_FILE, "r", encoding="utf-8") as fh:
                existing = json.load(fh)
        except Exception:
            pass
    
    merged = merge_results(existing, tests)
    
    with open(OUTPUT_FILE, "w", encoding="utf-8") as fh:
        json.dump(merged, fh, ensure_ascii=False, indent=2)
    
    print(f"✅ Resultados guardados: {OUTPUT_FILE}")
    print(f"Total tests: {len(merged)}")
    pasados = sum(1 for t in merged if t["paso"])
    print(f"Pasados: {pasados}/{len(merged)} ({round(pasados/len(merged)*100, 1)}%)")


if __name__ == "__main__":
    main()
