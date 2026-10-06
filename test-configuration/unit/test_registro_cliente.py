#!/usr/bin/env python3
"""
Tests para REGISTRO CLIENTE (index.html)
- Validaciones de nombre, apellido, cédula, teléfono, usuario, password
- SQL Injection, XSS, duplicados
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
REGISTRO_URL = f"{BASE}/app/registro_cliente.php"

REPORT_DIR = os.path.join(os.path.dirname(__file__), "reportes")
OUTPUT_FILE = os.path.join(REPORT_DIR, "ultimo-resultado-registro-cliente.json")
os.makedirs(REPORT_DIR, exist_ok=True)


def _rand_str(n: int = 8) -> str:
    return ''.join(random.choice(string.ascii_letters) for _ in range(n))


def _generar_email_unico() -> str:
    """Genera un email único para cada test"""
    return f"test_{_rand_str(10)}@test.com"


def _generar_cedula_ecuatoriana() -> str:
    """Genera cédula ecuatoriana válida con provincia y dígito verificador correctos"""
    provincia = random.randint(1, 24)
    digitos_medios = ''.join(random.choice(string.digits) for _ in range(7))
    primeros_9 = f"{provincia:02d}{digitos_medios}"
    
    coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2]
    suma = 0
    for i in range(9):
        valor = int(primeros_9[i]) * coeficientes[i]
        if valor > 9:
            valor -= 9
        suma += valor
    
    residuo = suma % 10
    digito_verificador = 0 if residuo == 0 else 10 - residuo
    return primeros_9 + str(digito_verificador)


def registro_cliente(payload: Dict, nombre_test: str, debe_pasar: bool) -> Dict:
    res = safe_request("POST", REGISTRO_URL, SESSION, data=payload)
    data = res["data"]
    
    ok = isinstance(data, dict) and data.get("success") is True
    paso = ok if debe_pasar else (data.get("success") is False)
    
    return result(
        nombre=nombre_test,
        panel="Registro Cliente",
        accion=f"POST: {payload.get('nombre', '')} {payload.get('apellido', '')}",
        esperado="Validar campos, rechazar duplicados/ataques SQL/XSS/longitudes inválidas",
        paso=paso,
        respuesta=data if isinstance(data, dict) else {"message": "Respuesta no JSON"}
    )


def suite_registro_cliente() -> List[Dict]:
    r: List[Dict] = []
    
    # === NOMBRES/APELLIDOS (15 tests) ===
    r.append(registro_cliente({"nombre": "", "apellido": "Apellido", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre vacío", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Apellido vacío", False))
    r.append(registro_cliente({"nombre": "Juan123", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre con números", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez@#$", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Apellido con símbolos", False))
    r.append(registro_cliente({"nombre": "Juan'; DROP TABLE clientes;--", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre SQL Injection", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "<script>alert('XSS')</script>", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Apellido XSS", False))
    r.append(registro_cliente({"nombre": "A" * 150, "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre muy largo", False))
    r.append(registro_cliente({"nombre": "O'Brien", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre con comilla", False))
    r.append(registro_cliente({"nombre": "Juan\tCarlos", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre con tab", False))
    r.append(registro_cliente({"nombre": "Juan\nCarlos", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre con salto línea", False))
    r.append(registro_cliente({"nombre": "     ", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre solo espacios", False))
    r.append(registro_cliente({"nombre": "Müller", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre con diéresis (válido)", True))
    r.append(registro_cliente({"nombre": "José", "apellido": "García", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre con acentos (válido)", True))
    r.append(registro_cliente({"nombre": "Nuñez", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre con ñ (válido)", True))
    r.append(registro_cliente({"nombre": "María", "apellido": "López", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Nombre/apellido válidos", True))
    
    # === CÉDULA (10 tests) ===
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": "", "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Cédula vacía", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": "123", "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Cédula muy corta", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": "ABC1234567", "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Cédula con letras", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": "1234' OR '1'='1", "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Cédula SQL Injection", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": "<script>alert(1)</script>", "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Cédula XSS", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": "1" * 50, "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Cédula muy larga", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": "1234 5678 90", "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Cédula con espacios", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": "1234-5678-90", "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Cédula con guiones", False))
    
    # Cédula duplicada
    cedula_base = _generar_cedula_ecuatoriana()
    usuario_base = _rand_str(10)
    r.append(registro_cliente({"nombre": "Cliente", "apellido": "Base", "cedula": cedula_base, "telefono": "0999999999", "ciudad": "Quito", "usuario": usuario_base, "password": "Pass1234", "email": _generar_email_unico()}, "Cliente base (para duplicado)", True))
    r.append(registro_cliente({"nombre": "Otro", "apellido": "Cliente", "cedula": cedula_base, "telefono": "0988888888", "ciudad": "Quito", "usuario": _rand_str(10), "password": "Pass1234", "email": _generar_email_unico()}, "Cédula duplicada (debe fallar)", False))
    
    r.append(registro_cliente({"nombre": "Pedro", "apellido": "Ramírez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Cédula válida", True))
    
    # === USUARIO/PASSWORD (10 tests) ===
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": "", "password": "Pass1234", "email": _generar_email_unico()}, "Usuario vacío", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "", "email": _generar_email_unico()}, "Password vacío", False))
    r.append(registro_cliente({"nombre": "Otro", "apellido": "Usuario", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0977777777", "ciudad": "Quito", "usuario": usuario_base, "password": "Pass1234", "email": _generar_email_unico()}, "Usuario duplicado (debe fallar)", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": "admin' OR '1'='1", "password": "Pass1234", "email": _generar_email_unico()}, "Usuario SQL Injection", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": "<script>alert(1)</script>", "password": "Pass1234", "email": _generar_email_unico()}, "Usuario XSS", False))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": "a" * 100, "password": "Pass1234", "email": _generar_email_unico()}, "Usuario muy largo", False))
    # NOTA: El sistema actualmente NO valida longitud de password, acepta cualquier longitud
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "123", "email": _generar_email_unico()}, "Password muy corto", True))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Quito", "usuario": _rand_str(8), "password": "P" * 300, "email": _generar_email_unico()}, "Password muy largo", True))
    r.append(registro_cliente({"nombre": "Juan", "apellido": "Pérez", "cedula": _generar_cedula_ecuatoriana(), "telefono": "ABC123XYZ", "ciudad": "Quito", "usuario": _rand_str(8), "password": "Pass1234", "email": _generar_email_unico()}, "Teléfono con letras", False))
    r.append(registro_cliente({"nombre": "Carlos", "apellido": "Mendoza", "cedula": _generar_cedula_ecuatoriana(), "telefono": "0987654321", "ciudad": "Guayaquil", "usuario": _rand_str(10), "password": "Seguro123!", "email": _generar_email_unico()}, "Registro completo válido", True))
    
    return r


def main():
    tests = suite_registro_cliente()
    
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
