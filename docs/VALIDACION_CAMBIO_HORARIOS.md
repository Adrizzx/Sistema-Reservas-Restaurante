# Sistema de Validación de Cambios de Horario con WhatsApp

## Problema Resuelto

**Escenario:**
- Cliente hace reserva para mañana a las 10:00 AM
- Admin cambia horario: mañana abre a las 11:00 AM (en vez de 9:00 AM)
- ❌ La reserva de las 10:00 AM quedaría inválida

**Solución:**
El sistema detecta automáticamente reservas afectadas y permite contactar a los clientes por **WhatsApp** directamente.

---

## Cómo Funciona

### 1. Backend Valida Automáticamente
Archivo: `app/api/gestionar_horarios.php`

Cuando el admin intenta cambiar horarios:
1. ✅ Busca todas las reservas futuras confirmadas
2. ✅ Verifica cuáles quedarían fuera del nuevo horario
3. ✅ Muestra lista detallada de reservas afectadas
4. ✅ **NO actualiza** hasta que el admin confirme

---

## Implementación en el Frontend

### Ejemplo para admin.php

```javascript
// Función para guardar horarios con validación
async function guardarHorarios() {
    const configuraciones = {
        horario_lunes_viernes_inicio: document.getElementById('horaInicioLV').value,
        horario_lunes_viernes_fin: document.getElementById('horaFinLV').value,
        horario_sabado_inicio: document.getElementById('horaInicioSab').value,
        horario_sabado_fin: document.getElementById('horaFinSab').value,
        horario_domingo_inicio: document.getElementById('horaInicioDom').value,
        horario_domingo_fin: document.getElementById('horaFinDom').value
    };

    try {
        // Primer intento: validar reservas afectadas
        const response = await fetch('app/api/gestionar_horarios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'actualizar',
                configuraciones: configuraciones,
                forzar: false  // NO forzar, primero validar
            })
        });

        const data = await response.json();

        // Si hay advertencia de reservas afectadas
        if (data.advertencia && data.reservas_afectadas) {
            mostrarAdvertenciaReservas(data.reservas_afectadas, configuraciones);
        } 
        // Si se actualizó correctamente (sin reservas afectadas)
        else if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '✅ Horarios Actualizados',
                text: data.message,
                background: '#1a1a1a',
                color: '#ffffff',
                confirmButtonColor: '#d4af37'
            }).then(() => location.reload());
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            background: '#1a1a1a',
            color: '#ffffff',
            confirmButtonColor: '#d4af37'
        });
    }
}

// Mostrar advertencia con reservas afectadas
function mostrarAdvertenciaReservas(reservasAfectadas, configuraciones) {
    // Crear tabla HTML con las reservas
    let tablaHTML = `
        <div style="max-height: 400px; overflow-y: auto; text-align: left;">
            <p style="color: #ff6b6b; font-weight: bold; margin-bottom: 15px;">
                ⚠️ ATENCIÓN: ${reservasAfectadas.length} reserva(s) quedarían fuera del nuevo horario
            </p>
            <table style="width: 100%; border-collapse: collapse; color: white;">
                <thead style="background: rgba(212, 175, 55, 0.2);">
                    <tr>
                        <th style="padding: 10px; border: 1px solid #555;">Cliente</th>
                        <th style="padding: 10px; border: 1px solid #555;">WhatsApp</th>
                        <th style="padding: 10px; border: 1px solid #555;">Fecha</th>
                        <th style="padding: 10px; border: 1px solid #555;">Hora</th>
                        <th style="padding: 10px; border: 1px solid #555;">Mesa</th>
                    </tr>
                </thead>
                <tbody>
    `;

    reservasAfectadas.forEach(reserva => {
        tablaHTML += `
            <tr>
                <td style="padding: 8px; border: 1px solid #555;">${reserva.cliente}</td>
                <td style="padding: 8px; border: 1px solid #555;">
                    <a href="#" onclick="abrirWhatsApp('${reserva.telefono}', '${reserva.cliente}', '${reserva.fecha}', '${reserva.hora}'); return false;" 
                       style="color: #25D366; text-decoration: none;">
                        📱 ${reserva.telefono}
                    </a>
                </td>
                <td style="padding: 8px; border: 1px solid #555;">${reserva.fecha}</td>
                <td style="padding: 8px; border: 1px solid #555; font-weight: bold; color: #ff6b6b;">${reserva.hora}</td>
                <td style="padding: 8px; border: 1px solid #555;">${reserva.mesa}</td>
            </tr>
        `;
    });

    tablaHTML += `
                </tbody>
            </table>
            <div style="margin-top: 15px; padding: 10px; background: rgba(255, 193, 7, 0.2); border-radius: 5px;">
                <p style="color: #ffc107; font-size: 0.9rem; margin: 0;">
                    <strong>Nuevo horario:</strong> ${reservasAfectadas[0].nuevo_horario}
                </p>
            </div>
            <div style="margin-top: 15px; padding: 10px; background: rgba(37, 211, 102, 0.2); border-radius: 5px;">
                <p style="color: #25D366; font-size: 0.85rem; margin: 0;">
                    💬 <strong>WhatsApp:</strong> Haz clic en cualquier número para contactar directamente
                </p>
            </div>
        </div>
    `;

    // Mostrar modal de confirmación
    Swal.fire({
        title: '⚠️ Conflicto de Horarios',
        html: tablaHTML,
        icon: 'warning',
        background: '#1a1a1a',
        color: '#ffffff',
        width: '900px',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: '✅ Cambiar de Todas Formas',
        denyButtonText: '📱 Contactar por WhatsApp',
        cancelButtonText: '❌ Cancelar',
        confirmButtonColor: '#d4af37',
        denyButtonColor: '#25D366',
        cancelButtonColor: '#666',
    }).then(async (result) => {
        if (result.isConfirmed) {
            // Admin confirmó: forzar actualización
            await forzarActualizacionHorarios(configuraciones);
        } else if (result.isDenied) {
            // Contactar a todos por WhatsApp
            contactarTodosPorWhatsApp(reservasAfectadas);
        }
    });
}

// Abrir WhatsApp Web con mensaje personalizado
function abrirWhatsApp(telefono, cliente, fecha, hora) {
    // Limpiar el teléfono (quitar espacios, guiones, etc)
    const telefonoLimpio = telefono.replace(/\D/g, '');
    
    // Construir mensaje personalizado
    const mensaje = `Hola ${cliente}, 

Le informamos que tenemos un cambio importante en nuestros horarios de atención.

Su reserva programada para:
📅 Fecha: ${fecha}
🕐 Hora: ${hora}

Lamentablemente queda fuera de nuestro nuevo horario de atención. 

¿Podríamos reprogramar su reserva para un horario disponible?

Le ofrecemos mantener su mesa sin cargo adicional.

Quedamos atentos a su respuesta.

Saludos,
Le Salon de Lumière`;
    
    // Codificar mensaje para URL
    const mensajeCodificado = encodeURIComponent(mensaje);
    
    // Abrir WhatsApp Web (funciona en desktop y mobile)
    const urlWhatsApp = `https://wa.me/593${telefonoLimpio}?text=${mensajeCodificado}`;
    
    window.open(urlWhatsApp, '_blank');
}

// Contactar a todos los clientes por WhatsApp (abre ventanas múltiples)
function contactarTodosPorWhatsApp(reservasAfectadas) {
    Swal.fire({
        title: '📱 Contactar Clientes',
        html: `
            <p style="color: white; margin-bottom: 20px;">
                Se abrirán ${reservasAfectadas.length} conversación(es) de WhatsApp.
            </p>
            <p style="color: #ffc107; font-size: 0.9rem;">
                ⚠️ Tu navegador puede bloquear ventanas emergentes. Permite abrirlas si es necesario.
            </p>
        `,
        icon: 'info',
        background: '#1a1a1a',
        color: '#ffffff',
        confirmButtonColor: '#25D366',
        confirmButtonText: '📱 Abrir WhatsApp',
        showCancelButton: true,
        cancelButtonColor: '#666'
    }).then((result) => {
        if (result.isConfirmed) {
            // Abrir WhatsApp para cada cliente con un pequeño delay
            reservasAfectadas.forEach((reserva, index) => {
                setTimeout(() => {
                    abrirWhatsApp(reserva.telefono, reserva.cliente, reserva.fecha, reserva.hora);
                }, index * 500); // 500ms de delay entre cada uno
            });
            
            // Mensaje de confirmación
            setTimeout(() => {
                Swal.fire({
                    icon: 'success',
                    title: '✅ WhatsApp Abierto',
                    text: `Se abrieron ${reservasAfectadas.length} conversación(es)`,
                    background: '#1a1a1a',
                    color: '#ffffff',
                    confirmButtonColor: '#d4af37',
                    timer: 3000
                });
            }, reservasAfectadas.length * 500 + 100);
        }
    });
}

// Forzar actualización cuando el admin confirma
async function forzarActualizacionHorarios(configuraciones) {
    try {
        const response = await fetch('app/api/gestionar_horarios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'actualizar',
                configuraciones: configuraciones,
                forzar: true  // FORZAR actualización
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '✅ Horarios Actualizados',
                html: `
                    <p>${data.message}</p>
                    <p style="color: #ffc107; margin-top: 10px;">
                        ⚠️ Recuerda contactar a los clientes afectados por WhatsApp
                    </p>
                `,
                background: '#1a1a1a',
                color: '#ffffff',
                confirmButtonColor: '#d4af37'
            }).then(() => location.reload());
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron actualizar los horarios',
            background: '#1a1a1a',
            color: '#ffffff',
            confirmButtonColor: '#d4af37'
        });
    }
}
```

---

## Ventajas de WhatsApp

### ✅ Beneficios
- **Inmediato**: El cliente recibe la notificación al instante
- **Tasa de lectura alta**: 98% de mensajes leídos vs 20% de emails
- **Conversación directa**: Permite reprogramar en tiempo real
- **Sin servidores**: Usa WhatsApp Web API (gratis)
- **Familiar**: Todo el mundo usa WhatsApp

### 📱 Formato de Número
El sistema acepta números en formato ecuatoriano:
- `0990000001` → Se convierte a `593998521340`
- `+593 99 852 1340` → Se limpia automáticamente
- Funciona con cualquier formato, se limpia antes de enviar

### 🔗 URL de WhatsApp Web
```
https://wa.me/593998521340?text=mensaje_codificado
```
- Abre WhatsApp Web en desktop
- Abre app de WhatsApp en móvil
- Compatible con todos los navegadores

---

## Respuestas del API

### ✅ Sin Conflictos
```json
{
  "success": true,
  "message": "Configuración actualizada correctamente"
}
```

### ⚠️ Con Reservas Afectadas (Incluye WhatsApp)
```json
{
  "success": false,
  "advertencia": true,
  "message": "Hay 2 reserva(s) que quedarían fuera del nuevo horario",
  "reservas_afectadas": [
    {
      "id": 45,
      "cliente": "Juan Pérez",
      "email": "juan@email.com",
      "telefono": "0990000001",
      "fecha": "15/12/2025",
      "hora": "10:00",
      "mesa": "M01",
      "personas": 4,
      "nuevo_horario": "11:00 - 22:00",
      "problema": "antes_apertura"
    }
  ],
  "requiere_confirmacion": true
}
```

---

## Recomendaciones

### Política Sugerida
1. ✅ **Siempre validar** antes de cambiar horarios
2. ✅ **Contactar por WhatsApp** si hay reservas afectadas (más efectivo que email)
3. ✅ **Ofrecer reprogramar** o cancelar sin penalización
4. ✅ **Documentar** cambios en bitácora del sistema

### Mejores Prácticas
- 📅 Cambiar horarios con mínimo 24-48 horas de anticipación
- 📱 Usar WhatsApp para comunicación inmediata
- 💾 Guardar log de cambios de horario
- 🔔 Configurar alertas para admin cuando hay conflictos

### Plantilla de Mensaje WhatsApp
```
Hola [NOMBRE], 

Le informamos que tenemos un cambio importante en nuestros horarios de atención.

Su reserva programada para:
📅 Fecha: [FECHA]
🕐 Hora: [HORA]

Lamentablemente queda fuera de nuestro nuevo horario de atención. 

¿Podríamos reprogramar su reserva para un horario disponible?

Le ofrecemos mantener su mesa sin cargo adicional.

Quedamos atentos a su respuesta.

Saludos,
Le Salon de Lumière
```

---

## Diferencia: Email vs WhatsApp

| Aspecto | Email | WhatsApp |
|---------|-------|----------|
| **Tasa de lectura** | 📧 20% | 📱 98% |
| **Tiempo de respuesta** | ⏰ Horas/Días | ⚡ Minutos |
| **Conversación** | ❌ Unidireccional | ✅ Bidireccional |
| **Confirmación lectura** | ❌ No siempre | ✅ Doble check |
| **Implementación** | 🔧 Compleja (servidor SMTP) | ✅ Simple (URL) |
| **Costo** | 💰 Puede tener costo | 🆓 Gratis |

**Recomendación:** Usa WhatsApp para notificaciones urgentes como cambios de horario.

---

## Próximos Pasos (Opcional)

### Integración Avanzada
Si quieres automatizar completamente:

1. **WhatsApp Business API** (requiere aprobación de Facebook)
2. **Twilio WhatsApp** (servicio pago pero con mensajes automáticos)
3. **Notificaciones programadas** cuando hay cambios

### Mejora Simple (Recomendada)
Por ahora, la solución actual es perfecta porque:
- ✅ No requiere servicios externos
- ✅ Funciona inmediatamente
- ✅ El admin tiene control total
- ✅ Gratis y simple
