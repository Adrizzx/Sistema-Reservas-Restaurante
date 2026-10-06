#!/usr/bin/env python3
"""
update_from_excel.py
Lee un Excel con dos hojas ("categorias" y "platos") y actualiza MySQL:
 - Tabla categorias: id, nombre (UNICO), descripcion, orden_menu, activo
 - Tabla platos: id, categoria_id (FK), nombre, descripcion, precio, stock_disponible,
                 tiempo_preparacion, imagen_url, ingredientes, es_especial, activo
Requisitos:
  pip install pandas openpyxl pymysql python-slugify
Uso:
  python update_from_excel.py --input menu.xlsx \
      --mysql-host localhost --mysql-db crud_proyecto \
      --mysql-user crud_proyecto --mysql-pass 12345
"""
import argparse
import sys
import pymysql
import pandas as pd
from slugify import slugify

CAT_SHEET = "categorias"
PLA_SHEET = "platos"

def connect_mysql(host, db, user, pwd, charset="utf8mb4"):
    return pymysql.connect(
        host=host, user=user, password=pwd, database=db,
        charset=charset, cursorclass=pymysql.cursors.DictCursor, autocommit=False
    )

def table_exists(conn, table_name):
    with conn.cursor() as c:
        c.execute("SELECT COUNT(1) AS cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s", (table_name,))
        return c.fetchone()["cnt"] > 0

def get_columns(conn, table_name):
    with conn.cursor() as c:
        c.execute("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s", (table_name,))
        return [r['COLUMN_NAME'] for r in c.fetchall()]

def ensure_unique_indexes(conn, categorias_table='categorias', platos_table='platos'):
    with conn.cursor() as c:
        # Índice único en categorias.nombre
        c.execute("""
            SELECT COUNT(1) AS cnt FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s AND INDEX_NAME='uq_categorias_nombre';
        """, (categorias_table,))
        if c.fetchone()["cnt"] == 0:
            c.execute(f"CREATE UNIQUE INDEX uq_categorias_nombre ON {categorias_table}(nombre);")
        # Índice compuesto en platos(nombre, categoria_id)
        c.execute("""
            SELECT COUNT(1) AS cnt FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s AND INDEX_NAME='uq_platos_nombre_categoria';
        """, (platos_table,))
        if c.fetchone()["cnt"] == 0:
            c.execute(f"CREATE UNIQUE INDEX uq_platos_nombre_categoria ON {platos_table}(nombre, categoria_id);")

def upsert_categoria(conn, row, categorias_table='categorias'):
    with conn.cursor() as c:
        # Buscar por nombre
        c.execute(f"SELECT id FROM {categorias_table} WHERE nombre=%s", (row["nombre"],))
        r = c.fetchone()
        if r:
            # Update
            c.execute(f"""
                UPDATE {categorias_table} SET descripcion=%s, orden_menu=%s, activo=%s
                WHERE id=%s
            """, (row["descripcion"], int(row["orden_menu"]) if pd.notna(row["orden_menu"]) else None,
                  int(row["activo"]) if pd.notna(row["activo"]) else 1, r["id"]))
            return r["id"], "updated"
        else:
            # Insert
            c.execute(f"""
                INSERT INTO {categorias_table} (nombre, descripcion, orden_menu, activo)
                VALUES (%s,%s,%s,%s)
            """, (row["nombre"], row["descripcion"],
                  int(row["orden_menu"]) if pd.notna(row["orden_menu"]) else None,
                  int(row["activo"]) if pd.notna(row["activo"]) else 1))
            return c.lastrowid, "inserted"

def upsert_plato(conn, row, categoria_id, platos_table='platos', available_columns=None):
    # available_columns: list of column names present in platos_table
    if available_columns is None:
        available_columns = []
    with conn.cursor() as c:
        c.execute(f"SELECT id FROM {platos_table} WHERE nombre=%s AND categoria_id=%s", (row["nombre"], categoria_id))
        r = c.fetchone()

        expected_fields = [
            "descripcion","precio","stock_disponible","tiempo_preparacion",
            "imagen_url","ingredientes","es_especial","activo"
        ]
        # Use only the fields that exist in the table
        fields = [f for f in expected_fields if f in available_columns]

        # Build values aligned with fields
        val_map = {
            "descripcion": row.get("descripcion", ""),
            "precio": (float(row["precio"]) if pd.notna(row.get("precio")) else None),
            "stock_disponible": (int(row["stock_disponible"]) if pd.notna(row.get("stock_disponible")) else 0),
            "tiempo_preparacion": (int(row["tiempo_preparacion"]) if pd.notna(row.get("tiempo_preparacion")) else None),
            "imagen_url": row.get("imagen_url", ""),
            "ingredientes": row.get("ingredientes", ""),
            "es_especial": (int(row.get("es_especial", 0)) if pd.notna(row.get("es_especial")) else 0),
            "activo": (int(row.get("activo", 1)) if pd.notna(row.get("activo")) else 1),
        }

        values = tuple(val_map[f] for f in fields)

        if r:
            if fields:
                c.execute(f"""
                    UPDATE {platos_table} SET {', '.join(f + '=%s' for f in fields)}
                    WHERE id=%s
                """, (*values, r["id"]))
            return r["id"], "updated"
        else:
            if fields:
                placeholders = ','.join(['%s']*len(fields))
                c.execute(f"""
                    INSERT INTO {platos_table} (categoria_id, nombre, {', '.join(fields)})
                    VALUES (%s,%s,{placeholders})
                """, (categoria_id, row["nombre"], *values))
            else:
                # No extra fields to insert, just categoria_id and nombre
                c.execute(f"INSERT INTO {platos_table} (categoria_id, nombre) VALUES (%s,%s)", (categoria_id, row["nombre"]))
            return c.lastrowid, "inserted"

def load_frames(path):
    xls = pd.ExcelFile(path)
    sheet_names = [s.strip() for s in xls.sheet_names]

    def choose_sheet(wanted, candidates):
        w = wanted.lower()
        # Exact match
        for s in candidates:
            if s.lower() == w:
                return s
        # Match by contains
        for s in candidates:
            if w in s.lower() or s.lower() in w:
                return s
        # Fallback: look for keywords
        keywords = wanted.lower().split('_')
        for s in candidates:
            low = s.lower()
            if any(kw for kw in keywords if kw and kw in low):
                return s
        # last resort: first sheet
        return candidates[0] if candidates else wanted

    cat_sheet_name = choose_sheet(CAT_SHEET, sheet_names)
    pla_sheet_name = choose_sheet(PLA_SHEET, sheet_names)

    cats = pd.read_excel(xls, cat_sheet_name)
    platos = pd.read_excel(xls, pla_sheet_name)
    
    # Llenar valores nulos con defaults - Compatible con pandas 2.x
    cats = cats.fillna(value={"descripcion":"", "activo":1})
    cats["orden_menu"] = cats["orden_menu"].fillna(0)
    
    platos = platos.fillna(value={
        "descripcion":"", "precio":0, "stock_disponible":0, 
        "tiempo_preparacion":0, "imagen_url":"", "ingredientes":"", 
        "es_especial":0, "activo":1
    })
    
    # Normalizaciones
    cats["nombre"] = cats["nombre"].astype(str).str.strip()
    platos["nombre"] = platos["nombre"].astype(str).str.strip()
    platos["categoria"] = platos["categoria"].astype(str).str.strip()
    return cats, platos

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--input", required=True)
    ap.add_argument("--mysql-host", default="localhost")
    ap.add_argument("--mysql-db", required=True)
    ap.add_argument("--mysql-user", required=True)
    ap.add_argument("--mysql-pass", required=True)
    ap.add_argument("--clear-before", action="store_true", help="Eliminar todos los platos y categorías antes de cargar")
    args = ap.parse_args()

    cats_df, platos_df = load_frames(args.input)
    conn = connect_mysql(args.mysql_host, args.mysql_db, args.mysql_user, args.mysql_pass)
    try:
        # Detectar nombres de tablas y columnas reales para ser tolerante a cambios de esquema
        # Priorizar `categorias_platos` (usado en este proyecto) si existe
        categorias_table = 'categorias_platos' if table_exists(conn, 'categorias_platos') else ('categorias' if table_exists(conn, 'categorias') else 'categorias')
        platos_table = 'platos' if table_exists(conn, 'platos') else 'platos'

        platos_columns = get_columns(conn, platos_table)

        # NUEVO: Limpiar tablas si se solicitó
        if args.clear_before:
            with conn.cursor() as c:
                print("🗑️  Limpiando tablas existentes...")
                c.execute(f"DELETE FROM {platos_table}")
                c.execute(f"DELETE FROM {categorias_table}")
                print(f"✅ Tablas limpiadas: {platos_table}, {categorias_table}")

        ensure_unique_indexes(conn, categorias_table=categorias_table, platos_table=platos_table)

        # Upsert categorías
        cat_map = {}
        for _, row in cats_df.iterrows():
            if not row["nombre"]:
                continue
            cat_id, action = upsert_categoria(conn, row, categorias_table=categorias_table)
            cat_map[row["nombre"]] = cat_id

        # Upsert platos
        for _, row in platos_df.iterrows():
            if not row["nombre"] or not row["categoria"]:
                continue
            categoria_name = row["categoria"]
            if categoria_name not in cat_map:
                # Crear categoría al vuelo si no está
                cat_id, _ = upsert_categoria(conn, {
                    "nombre": categoria_name,
                    "descripcion": "",
                    "orden_menu": None,
                    "activo": 1
                }, categorias_table=categorias_table)
                cat_map[categoria_name] = cat_id
            upsert_plato(conn, row, cat_map[categoria_name], platos_table=platos_table, available_columns=platos_columns)

        conn.commit()
        print("Actualización exitosa ✅")
    except Exception as e:
        conn.rollback()
        print("Error ❌:", e, file=sys.stderr)
        sys.exit(1)
    finally:
        conn.close()

if __name__ == "__main__":
    main()
