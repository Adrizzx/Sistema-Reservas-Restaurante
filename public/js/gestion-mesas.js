// Gestión de Mesas - CRUD Completo

class GestionMesas {
    constructor() {
        this.mesas = [];
        this.init();
    }

    init() {
        // Inicialización silenciosa
    }

    async cargarMesas() {
        try {
            const response = await fetch('app/obtener_mesas.php');
            const data = await response.json();

            if (data.success) {
                this.mesas = data.mesas;
                this.renderTabla();
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error cargando mesas:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar las mesas'
            });
        }
    }

    renderTabla() {
        const tbody = document.getElementById('tablaMesas');
        if (!tbody) return;

        tbody.innerHTML = '';

        this.mesas.forEach(mesa => {
            const tr = document.createElement('tr');

            const estadoBadge = this.getEstadoBadge(mesa.estado);
            const ubicacionIcon = this.getUbicacionIcon(mesa.ubicacion);

            tr.innerHTML = `
                <td><strong>${mesa.numero_mesa}</strong></td>
                <td>${ubicacionIcon} ${this.getUbicacionTexto(mesa.ubicacion)}</td>
                <td>
                    ${mesa.capacidad_minima === mesa.capacidad_maxima
                    ? mesa.capacidad_maxima
                    : `${mesa.capacidad_minima}-${mesa.capacidad_maxima}`}
                </td>
                <td>${estadoBadge}</td>
                <td>${mesa.descripcion || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="gestionMesas.editarMesa(${mesa.id})">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="gestionMesas.confirmarEliminar(${mesa.id}, '${mesa.numero_mesa}')">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    getEstadoBadge(estado) {
        const badges = {
            'disponible': '<span class="badge bg-success">Disponible</span>',
            'ocupada': '<span class="badge bg-danger">Ocupada</span>',
            'reservada': '<span class="badge bg-warning text-dark">Reservada</span>',
            'mantenimiento': '<span class="badge bg-secondary">Mantenimiento</span>'
        };
        return badges[estado] || estado;
    }

    getUbicacionIcon(ubicacion) {
        const iconos = {
            'interior': '🍽️',
            'terraza': '🌿',
            'vip': '👑',
            'bar': '🍸'
        };
        return iconos[ubicacion] || '🪑';
    }

    getUbicacionTexto(ubicacion) {
        const textos = {
            'interior': 'Interior',
            'terraza': 'Terraza',
            'vip': 'VIP',
            'bar': 'Bar'
        };
        return textos[ubicacion] || ubicacion;
    }

    mostrarModal(modo = 'agregar', mesaData = null) {
        const modalHtml = `
            <div class="modal fade" id="modalMesa" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                ${modo === 'agregar' ? '➕ Agregar Nueva Mesa' : '✏️ Editar Mesa'}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="formMesa">
                                <input type="hidden" id="mesaId" value="${mesaData?.id || ''}">
                                
                                <div class="mb-3">
                                    <label class="form-label">Número de Mesa *</label>
                                    <input type="text" class="form-control" id="numeroMesa" 
                                           value="${mesaData?.numero_mesa || ''}" 
                                           placeholder="Ej: M01, T01, V01" required>
                                    <small class="text-muted">Formato: M=Interior, T=Terraza, V=VIP, B=Bar + número</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Capacidad Mínima</label>
                                        <input type="number" class="form-control" id="capacidadMinima" 
                                               value="${mesaData?.capacidad_minima || 1}" min="1" max="50">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Capacidad Máxima *</label>
                                        <input type="number" class="form-control" id="capacidadMaxima" 
                                               value="${mesaData?.capacidad_maxima || ''}" 
                                               min="1" max="50" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Ubicación *</label>
                                    <select class="form-select" id="ubicacion" required>
                                        <option value="interior" ${mesaData?.ubicacion === 'interior' ? 'selected' : ''}>🍽️ Interior</option>
                                        <option value="terraza" ${mesaData?.ubicacion === 'terraza' ? 'selected' : ''}>🌿 Terraza</option>
                                        <option value="vip" ${mesaData?.ubicacion === 'vip' ? 'selected' : ''}>👑 VIP</option>
                                        <option value="bar" ${mesaData?.ubicacion === 'bar' ? 'selected' : ''}>🍸 Bar</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Estado *</label>
                                    <select class="form-select" id="estadoMesa" required>
                                        <option value="disponible" ${mesaData?.estado === 'disponible' ? 'selected' : ''}>Disponible</option>
                                        <option value="ocupada" ${mesaData?.estado === 'ocupada' ? 'selected' : ''}>Ocupada</option>
                                        <option value="reservada" ${mesaData?.estado === 'reservada' ? 'selected' : ''}>Reservada</option>
                                        <option value="mantenimiento" ${mesaData?.estado === 'mantenimiento' ? 'selected' : ''}>Mantenimiento</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" rows="2" 
                                              placeholder="Ej: Mesa junto a la ventana">${mesaData?.descripcion || ''}</textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" onclick="gestionMesas.guardarMesa('${modo}')">
                                ${modo === 'agregar' ? '➕ Agregar' : '💾 Guardar Cambios'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Eliminar modal anterior si existe
        const modalAnterior = document.getElementById('modalMesa');
        if (modalAnterior) {
            modalAnterior.remove();
        }

        // Agregar nuevo modal
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalMesa'));
        modal.show();
    }

    async editarMesa(id) {
        const mesa = this.mesas.find(m => m.id == id);
        if (mesa) {
            this.mostrarModal('editar', mesa);
        }
    }

    async guardarMesa(modo) {
        const id = document.getElementById('mesaId')?.value;
        const numero_mesa = document.getElementById('numeroMesa').value.trim();
        const capacidad_minima = parseInt(document.getElementById('capacidadMinima').value);
        const capacidad_maxima = parseInt(document.getElementById('capacidadMaxima').value);
        const ubicacion = document.getElementById('ubicacion').value;
        const estado = document.getElementById('estadoMesa').value;
        const descripcion = document.getElementById('descripcion').value.trim();

        if (!numero_mesa || !capacidad_maxima) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                text: 'Por favor complete todos los campos obligatorios'
            });
            return;
        }

        if (capacidad_minima > capacidad_maxima) {
            Swal.fire({
                icon: 'warning',
                title: 'Capacidad inválida',
                text: 'La capacidad mínima no puede ser mayor que la máxima'
            });
            return;
        }

        try {
            const endpoint = modo === 'agregar' ? 'app/agregar_mesa.php' : 'app/editar_mesa.php';
            const data = {
                numero_mesa,
                capacidad_minima,
                capacidad_maxima,
                ubicacion,
                estado,
                descripcion
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

                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalMesa'));
                modal.hide();

                // Recargar tabla
                await this.cargarMesas();

                // Recargar el layout visual
                if (window.restaurantLayout) {
                    window.restaurantLayout.refresh();
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error guardando mesa:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo guardar la mesa'
            });
        }
    }

    confirmarEliminar(id, numero) {
        Swal.fire({
            title: '¿Eliminar mesa?',
            html: `¿Estás seguro de eliminar la mesa <strong>${numero}</strong>?<br><small>Esta acción no se puede deshacer.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.eliminarMesa(id);
            }
        });
    }

    async eliminarMesa(id) {
        try {
            const response = await fetch('app/eliminar_mesa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Eliminada!',
                    text: result.message,
                    timer: 2000
                });

                await this.cargarMesas();

                if (window.restaurantLayout) {
                    window.restaurantLayout.refresh();
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error eliminando mesa:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo eliminar la mesa'
            });
        }
    }

    abrir() {
        this.cargarMesas();
    }

    // NUEVO: Cambiar estado de mesas masivamente
    async cambiarEstadoMasivo(mesas, nuevoEstado) {
        try {
            const response = await fetch('app/api/cambiar_estado_mesa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    mesas: mesas,
                    estado: nuevoEstado
                })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Actualizado!',
                    text: data.message,
                    timer: 2500,
                    showConfirmButton: false
                });

                // Recargar mesas en la tabla de gestión
                await this.cargarMesas();

                // Actualizar el layout visual del restaurante si existe
                if (window.restaurantLayout && typeof window.restaurantLayout.refresh === 'function') {
                    window.restaurantLayout.refresh();
                }

                // Actualizar dashboard si existe
                if (window.dashboard && typeof window.dashboard.actualizarMesas === 'function') {
                    await window.dashboard.actualizarMesas();
                }

                // Actualizar estadísticas globales si la función existe
                if (typeof actualizarEstadisticas === 'function') {
                    actualizarEstadisticas();
                }
            } else {
                throw new Error(data.message || 'Error al cambiar estado');
            }
        } catch (error) {
            console.error('Error cambiando estado masivo:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo cambiar el estado de las mesas'
            });
        }
    }

    // NUEVO: Mostrar menú de acciones masivas
    async mostrarAccionesMasivas() {
        const { value: accion } = await Swal.fire({
            title: '🔧 Acciones Masivas de Mesas',
            html: `
                <div class="text-start">
                    <p class="mb-3">Selecciona una acción para gestionar múltiples mesas:</p>
                    <div class="alert alert-info">
                        <small><i class="fas fa-info-circle"></i> Estas acciones actualizan el estado de las mesas instantáneamente</small>
                    </div>
                </div>
            `,
            input: 'select',
            inputOptions: {
                'marcar_todas_ocupadas': '🔴 Marcar TODAS como Ocupadas (Restaurante lleno)',
                'marcar_todas_disponibles': '🟢 Marcar TODAS como Disponibles (Reset)',
                'marcar_seleccionadas': '✅ Cambiar Mesas Específicas (Selección manual)',
                'liberar_ocupadas': '🔓 Liberar Solo las Ocupadas (Limpieza rápida)'
            },
            inputPlaceholder: 'Selecciona una acción...',
            showCancelButton: true,
            confirmButtonText: 'Continuar →',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            width: '600px'
        });

        if (accion) {
            switch (accion) {
                case 'marcar_todas_ocupadas':
                    await this.confirmarCambioMasivo('todas', 'ocupada');
                    break;
                case 'marcar_todas_disponibles':
                    await this.confirmarCambioMasivo('todas', 'disponible');
                    break;
                case 'marcar_seleccionadas':
                    await this.seleccionarMesasEspecificas();
                    break;
                case 'liberar_ocupadas':
                    await this.liberarMesasOcupadas();
                    break;
            }
        }
    }

    // NUEVO: Confirmar cambio masivo
    async confirmarCambioMasivo(mesas, estado) {
        const estadoTexto = {
            'disponible': '🟢 Disponibles',
            'ocupada': '🔴 Ocupadas',
            'reservada': '🟡 Reservadas',
            'mantenimiento': '⚫ En Mantenimiento'
        };

        const estadoDescripcion = {
            'disponible': 'Libera todas las mesas para nuevas reservas',
            'ocupada': 'Marca todas como ocupadas (sin reserva previa)',
            'reservada': 'Marca todas como reservadas',
            'mantenimiento': 'Deshabilita todas las mesas temporalmente'
        };

        const totalMesas = this.mesas.length;

        const resultado = await Swal.fire({
            title: '⚠️ ¿Confirmar cambio masivo?',
            html: `
                <div class="text-start">
                    <p>Se cambiarán <strong class="text-danger">TODAS LAS MESAS (${totalMesas})</strong> a:</p>
                    <div class="alert alert-warning mb-3">
                        <h5>${estadoTexto[estado]}</h5>
                        <small>${estadoDescripcion[estado]}</small>
                    </div>
                    <p class="text-muted"><i class="fas fa-exclamation-triangle"></i> Esta acción se aplicará de inmediato y afectará al sistema completo.</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: estado === 'disponible' ? '#10b981' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cambiar todas',
            cancelButtonText: 'Cancelar',
            width: '550px'
        });

        if (resultado.isConfirmed) {
            await this.cambiarEstadoMasivo(mesas, estado);
        }
    }

    // NUEVO: Seleccionar mesas específicas
    async seleccionarMesasEspecificas() {
        if (this.mesas.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Sin mesas',
                text: 'No hay mesas disponibles en el sistema'
            });
            return;
        }

        const { value: mesasSeleccionadas } = await Swal.fire({
            title: 'Seleccionar Mesas',
            html: `
                <div class="text-start mb-3">
                    <p>Selecciona las mesas que deseas cambiar de estado:</p>
                </div>
                <div id="mesas-checkbox-container" class="border rounded p-3" style="max-height: 350px; overflow-y: auto; text-align: left; background-color: #f8f9fa;">
                    ${this.mesas.map(mesa => {
                const estadoEmoji = {
                    'disponible': '🟢',
                    'ocupada': '🔴',
                    'reservada': '🟡',
                    'mantenimiento': '⚫'
                }[mesa.estado] || '⚪';

                const ubicacionEmoji = {
                    'interior': '🍽️',
                    'terraza': '🌿',
                    'vip': '👑',
                    'bar': '🍸'
                }[mesa.ubicacion] || '🪑';

                return `
                            <div class="form-check mb-2 p-2 rounded" style="background: white; border: 1px solid #dee2e6;">
                                <input class="form-check-input mesa-check" type="checkbox" value="${mesa.id}" id="mesa-${mesa.id}">
                                <label class="form-check-label w-100" for="mesa-${mesa.id}" style="cursor: pointer;">
                                    ${estadoEmoji} <strong>${mesa.numero_mesa}</strong> ${ubicacionEmoji} ${mesa.ubicacion} 
                                    <span class="badge bg-${mesa.estado === 'disponible' ? 'success' : mesa.estado === 'ocupada' ? 'danger' : mesa.estado === 'reservada' ? 'warning' : 'secondary'}">${mesa.estado}</span>
                                </label>
                            </div>
                        `;
            }).join('')}
                </div>
                <div class="mt-3 d-flex gap-2 justify-content-center">
                    <button class="btn btn-sm btn-outline-primary" id="btn-select-all">
                        ✅ Seleccionar Todas
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="btn-deselect-all">
                        ❌ Deseleccionar Todas
                    </button>
                </div>
            `,
            width: '650px',
            showCancelButton: true,
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                // Agregar eventos a los botones
                document.getElementById('btn-select-all').onclick = () => {
                    document.querySelectorAll('.mesa-check').forEach(cb => cb.checked = true);
                };
                document.getElementById('btn-deselect-all').onclick = () => {
                    document.querySelectorAll('.mesa-check').forEach(cb => cb.checked = false);
                };
            },
            preConfirm: () => {
                const checked = Array.from(document.querySelectorAll('.mesa-check:checked')).map(cb => parseInt(cb.value));
                if (checked.length === 0) {
                    Swal.showValidationMessage('⚠️ Debes seleccionar al menos una mesa');
                    return false;
                }
                return checked;
            }
        });

        if (mesasSeleccionadas && mesasSeleccionadas.length > 0) {
            // Preguntar a qué estado cambiar
            const { value: nuevoEstado } = await Swal.fire({
                title: 'Cambiar Estado',
                html: `
                    <p>Has seleccionado <strong>${mesasSeleccionadas.length}</strong> mesa(s)</p>
                    <p class="text-muted">Selecciona el nuevo estado:</p>
                `,
                input: 'select',
                inputOptions: {
                    'disponible': '🟢 Disponible',
                    'ocupada': '🔴 Ocupada',
                    'reservada': '🟡 Reservada',
                    'mantenimiento': '⚫ Mantenimiento'
                },
                inputPlaceholder: 'Selecciona el nuevo estado',
                showCancelButton: true,
                confirmButtonText: 'Cambiar Estado',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) {
                        return '⚠️ Debes seleccionar un estado';
                    }
                }
            });

            if (nuevoEstado) {
                await this.cambiarEstadoMasivo(mesasSeleccionadas, nuevoEstado);
            }
        }
    }

    // NUEVO: Liberar solo mesas ocupadas
    async liberarMesasOcupadas() {
        // Obtener IDs de mesas ocupadas
        const mesasOcupadas = this.mesas.filter(m => m.estado === 'ocupada');

        if (mesasOcupadas.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Sin mesas ocupadas',
                text: 'No hay mesas en estado "Ocupada" para liberar',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        const resultado = await Swal.fire({
            title: '¿Liberar mesas ocupadas?',
            html: `
                <p>Se liberarán <strong>${mesasOcupadas.length}</strong> mesa(s) ocupada(s):</p>
                <div class="text-start mt-3" style="max-height: 200px; overflow-y: auto;">
                    ${mesasOcupadas.map(m => `<div>🔴 ${m.numero_mesa} - ${m.ubicacion}</div>`).join('')}
                </div>
                <p class="mt-3 text-muted">Todas cambiarán a estado "Disponible"</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, liberar todas',
            cancelButtonText: 'Cancelar'
        });

        if (resultado.isConfirmed) {
            const mesasIds = mesasOcupadas.map(m => m.id);
            await this.cambiarEstadoMasivo(mesasIds, 'disponible');
        }
    }
}

// Inicializar globalmente
window.gestionMesas = new GestionMesas();
