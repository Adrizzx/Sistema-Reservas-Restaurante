# Guía: Editar Reservas con Validación de Horario

## Descripción
El sistema ahora valida automáticamente cuando se intenta cambiar el horario de una reserva, verificando:
- ✅ Que el nuevo horario esté dentro del horario de atención
- ✅ Que no haya conflictos con otras reservas en la misma mesa
- ✅ Que las reservas estén activas

## Ejemplo de Uso en el Frontend

### Opción 1: Editar con Advertencia al Usuario

```javascript
async function editarReservaConValidacion(reservaId) {
    // 1. Mostrar formulario para editar
    const { value: formData } = await Swal.fire({
        title: 'Editar Reserva',
        html: `
            <div style="text-align: left;">
                <label style="color: #d4af37;">Nueva Fecha:</label>
                <input type="date" id="nueva_fecha" class="swal2-input">
                
                <label style="color: #d4af37;">Nueva Hora:</label>
                <input type="time" id="nueva_hora" class="swal2-input">
                
                <label style="color: #d4af37;">Número de Personas:</label>
                <input type="number" id="nueva_personas" class="swal2-input" min="1" max="10">
            </div>
        `,
        background: '#1a1a1a',
        color: '#ffffff',
        confirmButtonColor: '#d4af37',
        showCancelButton: true,
        confirmButtonText: 'Guardar Cambios',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            return {
                fecha: document.getElementById('nueva_fecha').value,
                hora: document.getElementById('nueva_hora').value,
                personas: document.getElementById('nueva_personas').value
            }
        }
    });

    if (!formData) return;

    // 2. Detectar si hay cambio de horario y mostrar advertencia
    const fechaCambio = formData.fecha !== fechaOriginal || formData.hora !== horaOriginal;
    
    if (fechaCambio) {
        const confirmacion = await Swal.fire({
            icon: 'warning',
            title: '⚠️ Cambio de Horario',
            html: `
                <p>Estás cambiando el horario de la reserva.</p>
                <p><strong>Fecha anterior:</strong> ${fechaOriginal} ${horaOriginal}</p>
                <p><strong>Nueva fecha:</strong> ${formData.fecha} ${formData.hora}</p>
                <p style="color: #d4af37;">¿Estás seguro de continuar?</p>
            `,
            background: '#1a1a1a',
            color: '#ffffff',
            showCancelButton: true,
            confirmButtonColor: '#d4af37',
            cancelButtonColor: '#666',
            confirmButtonText: 'Sí, cambiar horario',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmacion.isConfirmed) return;
    }

    // 3. Enviar la actualización
    try {
        const response = await fetch('app/editar_reserva.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: reservaId,
                fecha_reserva: formData.fecha,
                hora_reserva: formData.hora,
                numero_personas: parseInt(formData.personas)
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '✅ Reserva Actualizada',
                text: data.message,
                background: '#1a1a1a',
                color: '#ffffff',
                confirmButtonColor: '#d4af37'
            }).then(() => {
                location.reload();
            });
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `
                <p>${error.message}</p>
                <small style="color: #999;">
                    Si el horario está ocupado, intenta con otra hora o mesa.
                </small>
            `,
            background: '#1a1a1a',
            color: '#ffffff',
            confirmButtonColor: '#d4af37'
        });
    }
}
```

### Opción 2: Bloquear Cambio de Horario (Solo Cancelar)

```javascript
async function editarReservaSinHorario(reservaId) {
    // Solo permitir cambiar el número de personas, NO la fecha ni hora
    const { value: personas } = await Swal.fire({
        title: 'Editar Reserva',
        html: `
            <p style="color: #999; margin-bottom: 20px;">
                <i class="bi bi-info-circle"></i> 
                No se puede cambiar la fecha u hora de una reserva existente.
                Para cambiar el horario, debes cancelar y crear una nueva reserva.
            </p>
            <label style="color: #d4af37;">Número de Personas:</label>
            <input type="number" id="nueva_personas" class="swal2-input" min="1" max="10">
        `,
        background: '#1a1a1a',
        color: '#ffffff',
        confirmButtonColor: '#d4af37',
        showCancelButton: true,
        confirmButtonText: 'Actualizar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            return document.getElementById('nueva_personas').value
        }
    });

    if (!personas) return;

    try {
        const response = await fetch('app/editar_reserva.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: reservaId,
                numero_personas: parseInt(personas)
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Reserva Actualizada',
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
```

## Validaciones del Backend

El archivo `editar_reserva.php` ahora valida automáticamente:

### 1. Horario de Atención
- Verifica que la nueva hora esté dentro del horario del restaurante
- Respeta horarios diferentes para Lun-Vie, Sábado y Domingo

### 2. Conflictos de Reservas
- Verifica que no haya otra reserva en la misma mesa, fecha y hora
- Solo verifica reservas activas (pendiente/confirmada)

### 3. Estado del Sistema
- Verifica que las reservas estén habilitadas
- Respeta la configuración del administrador

## Respuestas del API

### ✅ Éxito
```json
{
    "success": true,
    "message": "Reserva actualizada exitosamente",
    "cambio_horario": true
}
```

### ❌ Error - Conflicto de Horario
```json
{
    "success": false,
    "message": "Ya existe otra reserva para la mesa M01 en ese horario. Por favor, elige otra hora o mesa."
}
```

### ❌ Error - Fuera de Horario
```json
{
    "success": false,
    "message": "El nuevo horario debe estar entre 10:00 y 22:00"
}
```

## Recomendación

**Para mejor experiencia de usuario:**
- Usa la **Opción 1** si quieres dar flexibilidad al cliente
- Usa la **Opción 2** si quieres ser más estricto y evitar confusiones
- En ambos casos, el backend valida y protege contra conflictos

## Integración con el Admin Panel

Para el panel de administración, puedes permitir edición completa con advertencias:

```javascript
// En admin.php, agregar función de edición
async function editarReservaAdmin(reserva) {
    // Mostrar todos los campos editables
    // Incluir advertencia si cambia horario
    // Permitir override si es admin (con confirmación adicional)
}
```
