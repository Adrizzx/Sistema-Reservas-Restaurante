#!/usr/bin/env python3
"""
Tests para LOGIN CLIENTE (index.html)
- Validaciones de email y teléfono
- SQL Injection y XSS
"""
import os
import json
from typing import List, Dict

try:
    import requests
except Exception as e:
    print("❌ Falta 'requests'. Instala: python3 -m pip install -r testing/code/python/requirements.txt")
    raise

from common import get_base_url, make_session, safe_request, result, merge_results

BASE = get_base_url()
SESSION = make_session()
LOGIN_URL = f"{BASE}/app/login_cliente.php"

REPORT_DIR = os.path.join(os.path.dirname(__file__), "reportes")
OUTPUT_FILE = os.path.join(REPORT_DIR, "ultimo-resultado-login-cliente.json")
os.makedirs(REPORT_DIR, exist_ok=True)


def login_cliente(email: str, telefono: str, nombre_test: str, debe_pasar: bool) -> Dict:
    payload = {"email": email, "telefono": telefono}
    res = safe_request("POST", LOGIN_URL, SESSION, data=payload)
    data = res["data"]
    
    ok = isinstance(data, dict) and data.get("success") is True
    paso = ok if debe_pasar else (data.get("success") is False)
    
    return result(
        nombre=nombre_test,
        panel="Login Cliente",
        accion=f"POST email={email[:30]}... telefono={telefono[:15]}...",
        esperado="Debe aceptar solo credenciales válidas; rechazar inválidas/ataques",
        paso=paso,
        respuesta=data if isinstance(data, dict) else {"message": "Respuesta no JSON"}
    )


def suite_login_cliente() -> List[Dict]:
    resultados: List[Dict] = []
    
    # 1 - Sin credenciales
    resultados.append(login_cliente("", "", "Login sin email ni teléfono", False))
    
    # 2 - Solo email
    resultados.append(login_cliente("test@test.com", "", "Login solo email", False))
    
    # 3 - Solo teléfono
    resultados.append(login_cliente("", "123456789", "Login solo teléfono", False))
    
    # 4 - Email inválido
    resultados.append(login_cliente("no-es-email", "123456789", "Login email sin @", False))
    
    # 5 - SQL Injection email
    resultados.append(login_cliente("' OR '1'='1", "123", "Login SQL Injection (comilla simple)", False))
    
    # 6 - SQL Injection UNION
    resultados.append(login_cliente("admin' UNION SELECT NULL,NULL,NULL--", "123", "Login SQL Injection UNION", False))
    
    # 7 - XSS en email
    resultados.append(login_cliente("<script>alert(1)</script>", "123", "Login XSS en email", False))
    
    # 8 - XSS en teléfono
    resultados.append(login_cliente("test@test.com", "<img src=x onerror=alert(1)>", "Login XSS en teléfono", False))
    
    # 9 - Email muy largo
    email_largo = "a" * 300 + "@test.com"
    resultados.append(login_cliente(email_largo, "123456", "Login email muy largo", False))
    
    # 10 - Teléfono muy largo
    tel_largo = "9" * 100
    resultados.append(login_cliente("test@test.com", tel_largo, "Login teléfono muy largo", False))
    
    # 11 - Email DROP TABLE
    resultados.append(login_cliente("test@test.com'; DROP TABLE clientes;--", "123", "Login email DROP TABLE", False))
    
    # 12 - Teléfono con letras
    resultados.append(login_cliente("test@test.com", "ABC123XYZ", "Login teléfono con letras", False))
    
    # 13 - Solo espacios
    resultados.append(login_cliente("   ", "   ", "Login solo espacios", False))
    
    # 14 - NULL bytes
    resultados.append(login_cliente("test\x00@test.com", "123\x00456", "Login NULL bytes", False))
    
    # 15 - Emojis
    resultados.append(login_cliente("test@test.com", "📱📞☎️", "Login teléfono con emojis", False))
    
    return resultados


def main():
    tests = suite_login_cliente()
    
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
