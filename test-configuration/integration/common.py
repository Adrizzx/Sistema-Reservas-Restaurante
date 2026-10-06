import os
import time
import json
from typing import Any, Dict, Optional

try:
    import requests
except Exception:
    requests = None

DEFAULT_BASE_URL = "http://localhost/PRY_PROYECTO"


def get_base_url() -> str:
    return os.getenv("BASE_URL", DEFAULT_BASE_URL).rstrip("/")


def make_session() -> Optional["requests.Session"]:
    if requests is None:
        return None
    s = requests.Session()
    s.headers.update({
        "User-Agent": "AdminPanelTester/1.0",
        "Accept": "application/json, */*"
    })
    return s


def result(nombre: str, panel: str, accion: str, esperado: str, paso: bool,
           respuesta: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
    return {
        "nombre": nombre,
        "panel": panel,
        "accion": accion,
        "esperado": esperado,
        "paso": bool(paso),
        "respuesta": respuesta or {}
    }


def safe_request(method: str, url: str, session: Optional["requests.Session"],
                 **kwargs) -> Dict[str, Any]:
    started = time.time()
    try:
        s = session if session is not None else requests
        resp = s.request(method, url, timeout=8, **kwargs)
        elapsed = round(time.time() - started, 3)
        content_type = resp.headers.get("Content-Type", "")
        data: Any
        if "application/json" in content_type:
            try:
                data = resp.json()
            except Exception:
                data = {"raw": resp.text}
        else:
            data = {"raw": resp.text[:500]}
        return {
            "status": resp.status_code,
            "elapsed": elapsed,
            "data": data,
        }
    except Exception as e:
        elapsed = round(time.time() - started, 3)
        return {
            "status": None,
            "elapsed": elapsed,
            "data": {"message": f"Error de conexión: {e}"}
        }


def merge_results(existing: Optional[list], new_items: list) -> list:
    if not existing:
        return new_items
    by_name = {r.get("nombre"): r for r in existing}
    for item in new_items:
        by_name[item.get("nombre")] = item
    return list(by_name.values())
