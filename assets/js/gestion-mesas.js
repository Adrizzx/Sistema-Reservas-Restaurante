// Gestión de Mesas - CRUD Completo

class GestionMesas {
    constructor() {
        this.mesas = [];
        this.init();
    }

    init() {
        console.log('Inicializando Gestión de Mesas...');
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
}

// Inicializar globalmente
window.gestionMesas = new GestionMesas();
