<div align="center">

# Le Salon de Lumière · Sistema de Reservas

### Plataforma web para la reserva de mesas y zonas de un restaurante, con panel administrativo, notificaciones por WhatsApp, auditoría y pruebas automatizadas

[![PHP](https://img.shields.io/badge/PHP-8-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/docs/Web/JavaScript)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Twilio](https://img.shields.io/badge/Twilio-WhatsApp%20API-F22F46?style=for-the-badge&logo=twilio&logoColor=white)](https://www.twilio.com/)
[![Python](https://img.shields.io/badge/Python-Testing%20%26%20ETL-3776AB?style=for-the-badge&logo=python&logoColor=white)](https://www.python.org/)

<img src="media/inicio.jpg" alt="Página de inicio de Le Salon de Lumière" width="90%">

</div>

---

## Descripción

**Le Salon de Lumière** es un sistema completo de reservas para un restaurante. Los clientes consultan el menú, eligen mesa o zona según disponibilidad en tiempo real y gestionan sus reservas desde su perfil. El personal administrativo controla mesas, horarios, menú, clientes y configuración desde un panel centralizado, mientras el sistema notifica automáticamente a los clientes por **WhatsApp** y registra cada operación en tablas de **auditoría**.

El proyecto se desarrolló aplicando un proceso de software formal: arquitectura **MVC**, base de datos con **triggers** y procedimientos almacenados, **pruebas unitarias, de integración y de estrés**, y análisis de **métricas de calidad de código**.

## Capturas

<table>
  <tr>
    <td width="50%"><img src="media/menu.jpg" alt="Menú del restaurante"></td>
    <td width="50%"><img src="media/mesas.jpg" alt="Disponibilidad de mesas"></td>
  </tr>
  <tr>
    <td align="center"><sub>Menú del restaurante</sub></td>
    <td align="center"><sub>Disponibilidad de mesas</sub></td>
  </tr>
  <tr>
    <td width="50%"><img src="media/reservas.jpg" alt="Formulario de reservas"></td>
    <td width="50%"><img src="media/seleccion_perfil.jpg" alt="Selección de tipo de usuario"></td>
  </tr>
  <tr>
    <td align="center"><sub>Reserva en línea</sub></td>
    <td align="center"><sub>Acceso por tipo de usuario</sub></td>
  </tr>
  <tr>
    <td width="50%"><img src="media/admin_dashboard.jpg" alt="Panel administrativo"></td>
    <td width="50%"><img src="media/admin_reservas.jpg" alt="Gestión de reservas"></td>
  </tr>
  <tr>
    <td align="center"><sub>Panel administrativo</sub></td>
    <td align="center"><sub>Gestión de reservas</sub></td>
  </tr>
  <tr>
    <td width="50%"><img src="media/gestion_mesas.jpg" alt="Gestión de mesas"></td>
    <td width="50%"><img src="media/subir_excel.jpg" alt="Carga masiva del menú desde Excel"></td>
  </tr>
  <tr>
    <td align="center"><sub>Gestión de mesas</sub></td>
    <td align="center"><sub>Carga masiva del menú desde Excel</sub></td>
  </tr>
</table>

## Funcionalidades

**Clientes**
- Registro e inicio de sesión con validación de datos (nombres, teléfono, correo).
- Consulta del menú por categorías y de los espacios del restaurante.
- Reserva de **mesas individuales** o de **zonas completas** con verificación de disponibilidad.
- Perfil con historial y estado de sus reservas.

**Administración**
- Dashboard con indicadores de ocupación y reservas.
- Gestión de mesas (capacidad, precio, estado) y de reservas: crear, editar, confirmar o cancelar con motivo.
- Configuración de **horarios de atención** con validación de conflictos frente a reservas existentes.
- Gestión del menú con **carga masiva desde Excel** (procesada con Python y Pandas).
- Gestión de clientes y configuración general del restaurante.

**Automatización y trazabilidad**
- **Notificaciones por WhatsApp** (API de Twilio) al confirmar, modificar o cancelar reservas, y cuando cambian los horarios.
- Actualización automática del estado de las reservas mediante procedimiento almacenado y tarea programada.
- **Triggers** que calculan el precio de las mesas y registran cada cambio de estado.
- **Auditoría** del sistema, de reservas y de horarios en tablas dedicadas.

## Arquitectura

```mermaid
flowchart LR
    U([Cliente / Administrador]) --> V[Vistas<br/>HTML · Bootstrap · JS]
    V -->|fetch| API[Endpoints PHP<br/>app/api]
    API --> C[Controladores<br/>Auth · Mesa · Reserva · Menu<br/>Notificacion · Auditoria]
    C --> M[Modelos<br/>Mesa · Cliente · Reserva<br/>Plato · Categoria]
    M --> DB[(MySQL<br/>triggers y procedimientos)]
    C --> W[Twilio<br/>WhatsApp]
    X[Excel del menú] --> PY[Script Python<br/>Pandas] --> DB
```

```
├── controllers/        Lógica de negocio (MVC)
├── models/             Acceso a datos con PDO
├── app/                Endpoints y APIs consumidas por el frontend
├── config/             Configuración, conexión Singleton y carga de variables de entorno
├── validacion/         Validadores del lado del servidor
├── sql/                Instalación completa, triggers, procedimientos y datos de prueba
├── scripts/            Tareas programadas y utilidades en Python
├── test-configuration/ Pruebas unitarias, de integración, de estrés y auditoría de tests
└── docs/               Documentación técnica y métricas de código
```

## Modelo de datos

15 tablas, entre ellas tres de auditoría.

```mermaid
erDiagram
    clientes ||--o{ reservas : "cliente_id"
    mesas ||--o{ reservas : "mesa_id"
    categorias_platos ||--o{ platos : "categoria_id"
    reservas ||--o{ pre_pedidos : "reserva_id"
    platos ||--o{ pre_pedidos : "plato_id"
    reservas ||--o{ notas_consumo : "reserva_id"
    administradores ||--o{ historial_estados : "usuario_id"
    reservas ||--o{ notificaciones_whatsapp : "reserva_id"
    clientes ||--o{ reservas_zonas : "cliente_id"
    administradores ||--o{ auditoria_horarios : "admin_id"
    reservas ||--o{ auditoria_reservas : "reserva_id"
    administradores ||--o{ auditoria_reservas : "admin_id"
    administradores {
        int id PK
        varchar usuario
        varchar password
    }
    clientes {
        int id PK
        varchar nombre
        varchar apellido
    }
    categorias_platos {
        int id PK
        varchar nombre
        text descripcion
    }
    mesas {
        int id PK
        varchar numero_mesa
        int capacidad_minima
    }
    reservas {
        int id PK
        int cliente_id FK
        int mesa_id FK
    }
    platos {
        int id PK
        int categoria_id FK
        varchar nombre
    }
    pre_pedidos {
        int id PK
        int reserva_id FK
        int plato_id FK
    }
    notas_consumo {
        int id PK
        int reserva_id FK
        varchar numero_nota
    }
    historial_estados {
        int id PK
        int usuario_id FK
        enum tabla_referencia
    }
    configuracion_restaurante {
        int id PK
        varchar clave
        text valor
    }
    notificaciones_whatsapp {
        int id PK
        int reserva_id FK
        varchar telefono
    }
    reservas_zonas {
        int id PK
        int cliente_id FK
        text zonas
    }
    auditoria_horarios {
        int id PK
        int admin_id FK
        varchar admin_nombre
    }
    auditoria_reservas {
        int id PK
        int reserva_id FK
        int admin_id FK
    }
    auditoria_sistema {
        int id PK
        int usuario_id
        enum usuario_tipo
    }
```

## Calidad y pruebas

- **Pruebas unitarias** por módulo (administración, clientes, login, registro, mesas, reservas y validadores).
- **Pruebas de integración** del CRUD de mesas y **pruebas de estrés** del panel administrativo.
- Reportes automáticos de ejecución y pistas de auditoría de cada corrida.
- Configuración de **PHPUnit** para pruebas de modelos, controladores y validadores.
- **Análisis estático propio** en Python: complejidad ciclomática, niveles de anidación, nomenclatura y adherencia a principios **SOLID** (`docs/metricas/`).

<div align="center">
  <img src="docs/metricas/dashboard_resumen.png" alt="Resumen de métricas de código" width="80%">
  <br><sub>Resumen del análisis de métricas de código</sub>
</div>

## Instalación

**Requisitos:** PHP 8+, MySQL 8 / MariaDB, Apache (XAMPP o LAMPP) y Python 3.10+ para los scripts y pruebas.

```bash
# 1. Clonar en htdocs
git clone https://github.com/Adrizzx/Sistema-Reservas-Restaurante.git

# 2. Crear la base de datos
mysql -u root -p < sql/INSTALACION_COMPLETA_BASE_DATOS.sql

# 3. Variables de entorno
cp .env.example .env              # credenciales de MySQL y Twilio (nunca se versiona)

# 4. Dependencias de Python (scripts y pruebas)
pip install -r scripts/python/requirements.txt
```

Guía detallada: [`docs/INSTRUCCIONES_INSTALACION.txt`](docs/INSTRUCCIONES_INSTALACION.txt) · Despliegue: [`DEPLOY_INSTRUCTIONS.md`](DEPLOY_INSTRUCTIONS.md)

## Equipo

Proyecto grupal de la asignatura **Modelos de Procesos de Software**, Universidad de las Fuerzas Armadas ESPE.

| Integrantes |
|---|
| **Marco Adrian Padilla Triviño** ([@Adrizzx](https://github.com/Adrizzx)) |
| Joffre Esteban Gómez Quinaluisa |
| Rubén Alejandro Bustos Viteri |
| Adonny Mateo Calero Argüello |
| Luciana Francheska Bolaños Toapanta |
| Daniela Flores |
| Evelyn Villarreal |
| Víctor Guano |
| Steven |
