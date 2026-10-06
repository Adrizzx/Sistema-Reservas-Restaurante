// Gestión de Reservas - CRUD Completo

class GestionReservas {
    constructor() {
        this.reservas = [];
        this.mesas = [];
        this.clientes = [];
        this.configuracionHorarios = null;
        this.init();
    }

    init() {
        console.log('Inicializando Gestión de Reservas...');
        this.cargarConfiguracionHorarios();
    }

    async cargarConfiguracionHorarios() {
        try {
            const response = await fetch('app/api/gestionar_horarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ action: 'obtener' })
            });

            const data = await response.json();

            if (data.success && data.configuracion) {
                this.configuracionHorarios = {
                    hora_apertura: data.configuracion.hora_apertura?.valor || '11:00',
                    hora_cierre: data.configuracion.hora_cierre?.valor || '23:00',
                    dias_cerrados: data.configuracion.dias_cerrados?.valor || ''
                };
                console.log('Configuración de horarios cargada:', this.configuracionHorarios);
            }
        } catch (error) {
            console.error('Error cargando configuración de horarios:', error);
        }
    }

    validarHorarioReserva(fecha, hora) {
        if (!this.configuracionHorarios) {
            return { valido: true }; // Si no hay configuración, permitir
        }

        const { hora_apertura, hora_cierre, dias_cerrados } = this.configuracionHorarios;

        // Validar hora dentro del rango de apertura-cierre
        if (hora < hora_apertura || hora > hora_cierre) {
            return {
                valido: false,
                mensaje: `El horario de reserva debe estar entre ${hora_apertura} y ${hora_cierre}`
            };
        }

        // Validar día de la semana no esté cerrado
        if (dias_cerrados) {
            const fechaObj = new Date(fecha + 'T00:00:00');
            const diaSemana = fechaObj.getDay(); // 0=Domingo, 1=Lunes, ..., 6=Sábado
            const diasCerradosArray = dias_cerrados.split(',').map(d => parseInt(d.trim()));

            if (diasCerradosArray.includes(diaSemana)) {
                const nombresEspañol = {
                    0: 'Domingo', 1: 'Lunes', 2: 'Martes', 3: 'Miércoles',
                    4: 'Jueves', 5: 'Viernes', 6: 'Sábado'
                };
                return {
                    valido: false,
                    mensaje: `No se pueden hacer reservas los días ${nombresEspañol[diaSemana]}. El restaurante está cerrado.`
                };
            }
        }

        return { valido: true };
    }

    async cargarReservas() {
        try {
            const filtroEstado = document.getElementById('filtroEstadoReserva')?.value || '';
            const url = filtroEstado ? `app/obtener_reservas.php?estado=${filtroEstado}` : 'app/obtener_reservas.php';

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.reservas = data.reservas;
                this.renderTabla();
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error cargando reservas:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar las reservas'
            });
        }
    }

    async cargarMesas() {
        try {
            const response = await fetch('app/obtener_mesas.php');
            const data = await response.json();
            if (data.success) {
                this.mesas = data.mesas;
            }
        } catch (error) {
            console.error('Error cargando mesas:', error);
        }
    }

    async cargarClientes() {
        try {
            const response = await fetch('app/obtener_clientes.php');
            const data = await response.json();
            if (data.success) {
                this.clientes = data.clientes;
            }
        } catch (error) {
            console.error('Error cargando clientes:', error);
        }
    }

    renderTabla() {
        const tbody = document.getElementById('tablaReservas');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (this.reservas.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        No hay reservas registradas
                    </td>
                </tr>
            `;
            return;
        }

        this.reservas.forEach(reserva => {
            const tr = document.createElement('tr');

            const estadoBadge = this.getEstadoBadge(reserva.estado);

            tr.innerHTML = `
                <td><strong>#${reserva.id}</strong></td>
                <td>
                    <i class="bi bi-person-circle me-1"></i>
                    ${reserva.cliente_nombre}
                    ${reserva.cliente_telefono ? `<br><small class="text-muted">${reserva.cliente_telefono}</small>` : ''}
                </td>
                <td><span class="badge bg-secondary">${reserva.mesa_numero}</span></td>
                <td>${this.formatearFecha(reserva.fecha_reserva)}</td>
                <td><i class="bi bi-clock me-1"></i>${reserva.hora_reserva}</td>
                <td><i class="bi bi-people me-1"></i>${reserva.numero_personas}</td>
                <td>${estadoBadge}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-primary" onclick="gestionReservas.editarReserva(${reserva.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${reserva.estado !== 'cancelada' && reserva.estado !== 'finalizada' ? `
                            <button class="btn btn-danger" onclick="gestionReservas.confirmarEliminar(${reserva.id}, '${reserva.cliente_nombre}')" title="Cancelar reserva">
                                <i class="fas fa-ban"></i>
                            </button>
                        ` : ''}
                        ${reserva.estado === 'pendiente' ? `
                            <button class="btn btn-success" onclick="gestionReservas.cambiarEstado(${reserva.id}, 'confirmada')" title="Confirmar">
                                <i class="fas fa-check"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    getEstadoBadge(estado) {
        const badges = {
            'pendiente': '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pendiente</span>',
            'confirmada': '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Confirmada</span>',
            'en_curso': '<span class="badge bg-info"><i class="bi bi-arrow-repeat me-1"></i>En Curso</span>',
            'finalizada': '<span class="badge bg-secondary"><i class="bi bi-check-all me-1"></i>Finalizada</span>',
            'cancelada': '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Cancelada</span>'
        };
        return badges[estado] || `<span class="badge bg-secondary">${estado}</span>`;
    }

    formatearFecha(fecha) {
        const date = new Date(fecha);
        return date.toLocaleDateString('es-EC', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    async mostrarModalReserva(modo = 'crear', reservaData = null) {
        // Cargar mesas y clientes primero
        await this.cargarMesas();
        await this.cargarClientes();

        const modalHtml = `
            <div class="modal fade" id="modalReserva" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                ${modo === 'crear' ? '➕ Crear Nueva Reserva' : '✏️ Editar Reserva'}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="formReserva">
                                <input type="hidden" id="reservaId" value="${reservaData?.id || ''}">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Cliente *</label>
                                        <select class="form-select" id="clienteId" required>
                                            <option value="">Seleccione un cliente</option>
                                            ${this.clientes.map(c => `
                                                <option value="${c.id}" ${reservaData?.cliente_id == c.id ? 'selected' : ''}>
                                                    ${c.nombre} ${c.apellido} - ${c.cedula}
                                                </option>
                                            `).join('')}
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Mesa *</label>
                                        <select class="form-select" id="mesaId" required>
                                            <option value="">Seleccione una mesa</option>
                                            ${this.mesas.map(m => `
                                                <option value="${m.id}" ${reservaData?.mesa_id == m.id ? 'selected' : ''}>
                                                    ${m.numero_mesa} - ${m.capacidad_minima}-${m.capacidad_maxima} personas (${m.ubicacion})
                                                </option>
                                            `).join('')}
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Fecha de Reserva *</label>
                                        <input type="date" class="form-control" id="fechaReserva" 
                                               value="${reservaData?.fecha_reserva || ''}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Hora *</label>
                                        <input type="time" class="form-control" id="horaReserva" 
                                               value="${reservaData?.hora_reserva || ''}" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Número de Personas *</label>
                                        <input type="number" class="form-control" id="numeroPersonas" 
                                               value="${reservaData?.numero_personas || 2}" min="1" max="50" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Estado *</label>
                                        <select class="form-select" id="estadoReserva" required>
                                            <option value="pendiente" ${reservaData?.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                                            <option value="confirmada" ${reservaData?.estado === 'confirmada' ? 'selected' : ''}>Confirmada</option>
                                            <option value="en_curso" ${reservaData?.estado === 'en_curso' ? 'selected' : ''}>En Curso</option>
                                            <option value="finalizada" ${reservaData?.estado === 'finalizada' ? 'selected' : ''}>Finalizada</option>
                                            <option value="cancelada" ${reservaData?.estado === 'cancelada' ? 'selected' : ''}>Cancelada</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Observaciones</label>
                                    <textarea class="form-control" id="observaciones" rows="3" 
                                              placeholder="Notas especiales sobre la reserva">${reservaData?.observaciones || ''}</textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="gestionReservas.guardarReserva('${modo}')">
                                ${modo === 'crear' ? '➕ Crear Reserva' : '💾 Guardar Cambios'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Eliminar modal anterior si existe
        const modalAnterior = document.getElementById('modalReserva');
        if (modalAnterior) {
            modalAnterior.remove();
        }

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('modalReserva'));
        modal.show();
    }

    async editarReserva(id) {
        const reserva = this.reservas.find(r => r.id == id);
        if (reserva) {
            this.mostrarModalReserva('editar', reserva);
        }
    }

    async guardarReserva(modo) {
        const id = document.getElementById('reservaId')?.value;
        const cliente_id = document.getElementById('clienteId').value;
        const mesa_id = document.getElementById('mesaId').value;
        const fecha_reserva = document.getElementById('fechaReserva').value;
        const hora_reserva = document.getElementById('horaReserva').value;
        const numero_personas = parseInt(document.getElementById('numeroPersonas').value);
        const estado = document.getElementById('estadoReserva').value;
        const observaciones = document.getElementById('observaciones').value.trim();

        if (!cliente_id || !mesa_id || !fecha_reserva || !hora_reserva || !numero_personas) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                text: 'Por favor complete todos los campos obligatorios'
            });
            return;
        }

        // VALIDAR HORARIO DE RESERVA
        const validacion = this.validarHorarioReserva(fecha_reserva, hora_reserva);
        if (!validacion.valido) {
            Swal.fire({
                icon: 'error',
                title: 'Horario no permitido',
                text: validacion.mensaje
            });
            return;
        }

        try {
            const endpoint = modo === 'crear' ? 'app/crear_reserva_admin.php' : 'app/editar_reserva.php';
            const data = {
                cliente_id,
                mesa_id,
                fecha_reserva,
                hora_reserva,
                numero_personas,
                estado,
                observaciones
            };

            if (modo === 'editar') {
                data.id = id;
            }

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: result.message,
                    timer: 2000
                });

                const modal = bootstrap.Modal.getInstance(document.getElementById('modalReserva'));
                modal.hide();

                await this.cargarReservas();

                if (window.restaurantLayout) {
                    window.restaurantLayout.refresh();
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error guardando reserva:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo guardar la reserva'
            });
        }
    }

    confirmarEliminar(id, clienteNombre) {
        Swal.fire({
            title: '¿Cancelar reserva con notificación?',
            html: `
                <p>Reserva de: <strong>${clienteNombre}</strong></p>
                <hr>
                <div class="text-start">
                    <label class="form-label">Motivo de cancelación:</label>
                    <textarea id="motivoCancelacion" class="form-control" rows="3" placeholder="Ej: Problema con el horario, cambio de planes, etc."></textarea>
                    <small class="text-muted">El cliente recibirá un WhatsApp con este motivo</small>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cancelar y notificar',
            cancelButtonText: 'No cancelar',
            preConfirm: () => {
                const motivo = document.getElementById('motivoCancelacion').value.trim();
                if (!motivo) {
                    Swal.showValidationMessage('Debes ingresar un motivo de cancelación');
                    return false;
                }
                return motivo;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.cancelarReservaConNotificacion(id, result.value);
            }
        });
    }

    // NUEVO: Cancelar reserva con notificación de WhatsApp
    async cancelarReservaConNotificacion(id, motivo) {
        try {
            const response = await fetch('app/api/cancelar_reserva_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    reserva_id: id,
                    motivo: motivo
                })
            });

            const result = await response.json();

            if (result.success) {
                // Actualizar el estado local
                const reserva = this.reservas.find(r => r.id === id);
                if (reserva) {
                    reserva.estado = 'cancelada';
                }
                this.renderTabla();

                // Mensaje de éxito con información de WhatsApp
                const whatsappMsg = result.whatsapp_enviado
                    ? '<br><small class="text-success">✅ WhatsApp enviado correctamente</small>'
                    : '<br><small class="text-warning">⚠️ Reserva cancelada pero WhatsApp no pudo enviarse</small>';

                Swal.fire({
                    icon: 'success',
                    title: '¡Reserva Cancelada!',
                    html: result.message + whatsappMsg,
                    timer: 3000,
                    showConfirmButton: true
                });

                // Actualizar layout si existe
                if (window.restaurantLayout) {
                    window.restaurantLayout.refresh();
                }

                // Actualizar estadísticas si existe la función
                if (typeof actualizarEstadisticas === 'function') {
                    actualizarEstadisticas();
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error cancelando reserva:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo cancelar la reserva'
            });
        }
    }

    // Mantener método antiguo para compatibilidad (sin notificación)
    async eliminarReserva(id) {
        const confirmacion = await Swal.fire({
            title: '¿Cancelar reserva sin notificación?',
            text: 'La reserva se marcará como cancelada (sin WhatsApp)',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No'
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const response = await fetch('app/eliminar_reserva.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const result = await response.json();

            if (result.success) {
                // Actualizar el estado local inmediatamente
                const reserva = this.reservas.find(r => r.id === id);
                if (reserva) {
                    reserva.estado = 'cancelada';
                }
                this.renderTabla();

                Swal.fire({
                    icon: 'success',
                    title: '¡Cancelada!',
                    text: result.message,
                    timer: 1500,
                    showConfirmButton: false
                });

                if (window.restaurantLayout) {
                    window.restaurantLayout.refresh();
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error cancelando reserva:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo cancelar la reserva'
            });
        }
    }

    async cambiarEstado(id, nuevoEstado) {
        try {
            const response = await fetch('app/editar_reserva.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, estado: nuevoEstado })
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Actualizado!',
                    text: `Reserva marcada como ${nuevoEstado}`,
                    timer: 2000
                });

                await this.cargarReservas();

                if (window.restaurantLayout) {
                    window.restaurantLayout.refresh();
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error cambiando estado:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo actualizar el estado'
            });
        }
    }

    async abrir() {
        // Actualizar estados antes de cargar reservas
        try {
            await fetch('app/api/actualizar_estados_reservas.php', {
                credentials: 'same-origin'
            });
        } catch (error) {
            console.error('Error actualizando estados:', error);
        }

        this.cargarReservas();
    }
}

// Inicializar globalmente
window.gestionReservas = new GestionReservas();
