class RestaurantLayout {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            return;
        }
        this.mesas = [];
        this.updateInterval = null;
        this.init();
    }

    async init() {
        this.createLayout();
        await this.loadMesas();
        this.startAutoUpdate();
    }

    createLayout() {
        this.container.innerHTML = `
            <div class="restaurant-layout">
                <div class="restaurant-title">
                    🍽️ Layout del Restaurante Elegante
                </div>
                
                <div class="restaurant-stats" id="layoutStats">
                    <div class="stat-row">
                        <span>Disponibles:</span>
                        <span id="statDisponibles">-</span>
                    </div>
                    <div class="stat-row">
                        <span>Ocupadas:</span>
                        <span id="statOcupadas">-</span>
                    </div>
                    <div class="stat-row">
                        <span>Reservadas:</span>
                        <span id="statReservadas">-</span>
                    </div>
                    <div class="stat-row">
                        <span>Ocupación:</span>
                        <span id="statOcupacion">-%</span>
                    </div>
                </div>

                <div class="restaurant-zones" id="restaurantZones">
                    <div class="zone zone-interior">
                        <div class="zone-label">🏛️ Salón Principal</div>
                        <div class="mesas-container" data-zona="interior"></div>
                    </div>
                    
                    <div class="zone zone-terraza">
                        <div class="zone-label">🌿 Terraza</div>
                        <div class="mesas-container" data-zona="terraza"></div>
                    </div>
                    
                    <div class="zone zone-vip">
                        <div class="zone-label">👑 Área VIP</div>
                        <div class="mesas-container" data-zona="vip"></div>
                    </div>
                    
                    <div class="zone zone-bar">
                        <div class="zone-label">🍸 Bar & Lounge</div>
                        <div class="mesas-container" data-zona="bar"></div>
                    </div>
                </div>

                <div class="restaurant-legend">
                    <div class="legend-item">
                        <div class="legend-color mesa-disponible"></div>
                        <span>Disponible</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color mesa-ocupada"></div>
                        <span>Ocupada</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color mesa-reservada"></div>
                        <span>Reservada</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color mesa-mantenimiento"></div>
                        <span>Mantenimiento</span>
                    </div>
                </div>
            </div>
        `;
    }

    showLoading() {
        const zones = document.getElementById('restaurantZones');
        zones.innerHTML = `
            <div class="loading-restaurant">
                <div class="spinner-border" role="status"></div>
                <div>Cargando distribución del restaurante...</div>
            </div>
        `;
    }

    async loadMesas() {
        try {
            this.showLoading();

            // Agregar timestamp para evitar caché del navegador
            const timestamp = new Date().getTime();
            const response = await fetch(`app/api/mesas_estado.php?_=${timestamp}`, {
                cache: 'no-store',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });

            if (!response.ok) {
                throw new Error('Error en la respuesta de la API');
            }

            const data = await response.json();

            if (data.success) {
                this.mesas = data.mesas;
                this.renderMesas();
                this.updateStats(data.resumen);
            } else {
                throw new Error(data.message || 'Error al cargar mesas');
            }
        } catch (error) {
            console.error('Error cargando mesas:', error);
            this.showError('Error al cargar las mesas del restaurante');
        }
    }

    renderMesas() {
        // Recrear el layout si fue eliminado por el loading
        if (!document.getElementById('restaurantZones').querySelector('.zone')) {
            this.createLayout();
        }

        // Limpiar contenedores
        const containers = document.querySelectorAll('.mesas-container');
        containers.forEach(container => container.innerHTML = '');

        // Agrupar mesas por zona
        const mesasPorZona = this.groupMesasByZona(this.mesas);

        // Renderizar mesas en cada zona
        Object.entries(mesasPorZona).forEach(([zona, mesas]) => {
            const container = document.querySelector(`[data-zona="${zona}"]`);

            if (container) {
                mesas.forEach(mesa => {
                    const mesaElement = this.createMesaElement(mesa);
                    container.appendChild(mesaElement);
                });
            }
        });
    }

    groupMesasByZona(mesas) {
        return mesas.reduce((grupos, mesa) => {
            const zona = mesa.ubicacion || 'interior';
            if (!grupos[zona]) {
                grupos[zona] = [];
            }
            grupos[zona].push(mesa);
            return grupos;
        }, {});
    }

    createMesaElement(mesa) {
        const mesaEl = document.createElement('div');

        // Determinar tamaño según capacidad máxima
        const sizeClass = this.getMesaSizeClass(mesa.capacidad_maxima || mesa.capacidad);
        mesaEl.className = `mesa mesa-${mesa.estado} ${sizeClass}`;

        // Determinar icono según zona y capacidad
        const icono = this.getMesaIcon(mesa);
        const capacidadText = mesa.capacidad_minima && mesa.capacidad_maxima
            ? (mesa.capacidad_minima === mesa.capacidad_maxima
                ? mesa.capacidad_maxima
                : `${mesa.capacidad_minima}-${mesa.capacidad_maxima}`)
            : mesa.capacidad;

        mesaEl.innerHTML = `
            <div class="mesa-icon">${icono}</div>
            <div class="mesa-numero">${mesa.numero}</div>
            <div class="mesa-capacidad">👥 ${capacidadText}</div>
            <div class="mesa-tooltip">
                ${this.getMesaTooltipContent(mesa)}
            </div>
        `;

        mesaEl.addEventListener('click', () => this.onMesaClick(mesa));

        return mesaEl;
    }

    getMesaSizeClass(capacidad) {
        // Determinar tamaño según capacidad
        if (capacidad <= 4) {
            return 'mesa-pequena'; // 70x70px
        } else if (capacidad <= 8) {
            return 'mesa-mediana'; // 85x85px
        } else if (capacidad <= 10) {
            return 'mesa-grande'; // 100x100px
        } else {
            return 'mesa-extra-grande'; // 120x120px
        }
    }

    getMesaIcon(mesa) {
        // Iconos según zona
        const iconosPorZona = {
            'interior': '🍽️',
            'terraza': '🌿',
            'vip': '👑',
            'bar': '🍸'
        };

        return iconosPorZona[mesa.ubicacion] || '🪑';
    }

    getMesaTooltipContent(mesa) {
        let content = `<strong>🪑 Mesa ${mesa.numero}</strong><br>`;

        // Mostrar rango de capacidad
        if (mesa.capacidad_minima && mesa.capacidad_maxima) {
            if (mesa.capacidad_minima === mesa.capacidad_maxima) {
                content += `👥 ${mesa.capacidad_maxima} personas<br>`;
            } else {
                content += `👥 ${mesa.capacidad_minima}-${mesa.capacidad_maxima} personas<br>`;
            }
        } else {
            content += `👥 ${mesa.capacidad} personas<br>`;
        }

        content += `${this.getEstadoIcon(mesa.estado)} ${this.getEstadoText(mesa.estado)}<br>`;
        content += `📍 ${this.getZonaText(mesa.ubicacion)}`;

        if (mesa.descripcion) {
            content += `<br>💬 ${mesa.descripcion}`;
        }

        if (mesa.reserva) {
            content += `<br><br><strong>📋 Reserva Activa:</strong><br>`;
            content += `👤 ${mesa.reserva.cliente}<br>`;
            content += `🕐 ${mesa.reserva.hora}<br>`;
            content += `👥 ${mesa.reserva.personas} personas`;
            if (mesa.reserva.notas) {
                content += `<br>📝 ${mesa.reserva.notas}`;
            }
        }

        return content;
    }

    getEstadoIcon(estado) {
        const iconos = {
            'disponible': '🟢',
            'ocupada': '🔴',
            'reservada': '🟡',
            'mantenimiento': '⚫'
        };
        return iconos[estado] || '⚪';
    }

    getEstadoText(estado) {
        const textos = {
            'disponible': 'Disponible',
            'ocupada': 'Ocupada',
            'reservada': 'Reservada',
            'mantenimiento': 'Mantenimiento'
        };
        return textos[estado] || estado;
    }

    getZonaText(zona) {
        const zonas = {
            'interior': '🏛️ Salón Principal',
            'terraza': '🌿 Terraza',
            'vip': '👑 Área VIP',
            'bar': '🍸 Bar & Lounge'
        };
        return zonas[zona] || zona;
    }

    onMesaClick(mesa) {
        // Opciones según el estado actual
        let opciones = {
            'disponible': {
                'ocupada': '🔴 Marcar como Ocupada',
                'reservada': '🟡 Marcar como Reservada',
                'mantenimiento': '⚫ Marcar en Mantenimiento'
            },
            'ocupada': {
                'disponible': '🟢 Liberar Mesa',
                'mantenimiento': '⚫ Marcar en Mantenimiento'
            },
            'reservada': {
                'disponible': '🟢 Liberar Mesa',
                'ocupada': '🔴 Marcar como Ocupada'
            },
            'mantenimiento': {
                'disponible': '🟢 Marcar como Disponible'
            }
        };

        // Crear HTML de opciones
        let opcionesHTML = '';
        const estadosDisponibles = opciones[mesa.estado] || {};

        for (let [nuevoEstado, texto] of Object.entries(estadosDisponibles)) {
            opcionesHTML += `
                <button 
                    class="swal2-confirm swal2-styled" 
                    onclick="cambiarEstadoMesaIndividual(${mesa.id}, '${nuevoEstado}')"
                    style="margin: 5px;">
                    ${texto}
                </button>
            `;
        }

        // Formatear capacidad
        let capacidadText;
        if (mesa.capacidad_minima && mesa.capacidad_maxima) {
            if (mesa.capacidad_minima === mesa.capacidad_maxima) {
                capacidadText = `${mesa.capacidad_maxima} personas`;
            } else {
                capacidadText = `${mesa.capacidad_minima} a ${mesa.capacidad_maxima} personas`;
            }
        } else {
            capacidadText = `${mesa.capacidad} personas`;
        }

        let detallesHTML = `
            <div style="text-align: left; margin-bottom: 20px;">
                <p><strong>🏷️ Zona:</strong> ${this.getZonaText(mesa.ubicacion)}</p>
                <p><strong>🎯 Estado:</strong> ${this.getEstadoText(mesa.estado)}</p>
                <p><strong>👥 Capacidad:</strong> ${capacidadText}</p>
                ${mesa.descripcion ? `<p><strong>💬 Descripción:</strong> ${mesa.descripcion}</p>` : ''}
            </div>
        `;

        if (mesa.reserva) {
            detallesHTML += `
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: left; margin-bottom: 20px;">
                    <h4 style="margin-top: 0;">📋 Reserva Activa</h4>
                    <p><strong>👤 Cliente:</strong> ${mesa.reserva.cliente}</p>
                    <p><strong>📞 Teléfono:</strong> ${mesa.reserva.telefono || 'No disponible'}</p>
                    <p><strong>📅 Fecha:</strong> ${mesa.reserva.fecha}</p>
                    <p><strong>🕐 Hora:</strong> ${mesa.reserva.hora}</p>
                    <p><strong>👥 Personas:</strong> ${mesa.reserva.personas}</p>
                    ${mesa.reserva.notas ? `<p><strong>📝 Notas:</strong> ${mesa.reserva.notas}</p>` : ''}
                </div>
            `;
        }

        detallesHTML += `<div style="margin-top: 20px;"><strong>Cambiar estado de la mesa:</strong></div>`;

        Swal.fire({
            title: `Mesa ${mesa.numero}`,
            html: detallesHTML + opcionesHTML,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'Cerrar',
            width: '600px'
        });
    }

    updateStats(resumen) {
        if (document.getElementById('statDisponibles')) {
            document.getElementById('statDisponibles').textContent = resumen.disponibles || 0;
        }
        if (document.getElementById('statOcupadas')) {
            document.getElementById('statOcupadas').textContent = resumen.ocupadas || 0;
        }

        // Calcular reservadas
        const reservadas = this.mesas.filter(m => m.estado === 'reservada').length;
        if (document.getElementById('statReservadas')) {
            document.getElementById('statReservadas').textContent = reservadas;
        }

        if (document.getElementById('statOcupacion')) {
            document.getElementById('statOcupacion').textContent = `${resumen.porcentaje_ocupacion || 0}%`;
        }
    }

    showError(message) {
        const zones = document.getElementById('restaurantZones');
        zones.innerHTML = `
            <div class="loading-restaurant">
                <div class="text-danger">⚠️</div>
                <div class="text-danger">${message}</div>
                <button class="btn btn-sm btn-outline-primary mt-2" onclick="window.restaurantLayout.loadMesas()">
                    Reintentar
                </button>
            </div>
        `;
    }

    startAutoUpdate() {
        // Actualizar cada 15 segundos
        this.updateInterval = setInterval(() => {
            this.loadMesas();
        }, 15000);
    }

    stopAutoUpdate() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }

    refresh() {
        this.loadMesas();
    }

    destroy() {
        this.stopAutoUpdate();
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

// Función global para cambiar estado de mesa individual
async function cambiarEstadoMesaIndividual(mesaId, nuevoEstado) {
    Swal.close(); // Cerrar el diálogo actual

    try {
        const response = await fetch('app/api/cambiar_estado_mesa.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                mesas: [mesaId],
                estado: nuevoEstado
            })
        });

        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Estado Actualizado!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });

            // Recargar mesas
            if (window.restaurantLayout) {
                window.restaurantLayout.loadMesas();
            }

            // Actualizar estadísticas globales
            if (typeof actualizarEstadisticas === 'function') {
                actualizarEstadisticas();
            }
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo cambiar el estado'
        });
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('restaurant-layout-container');
    if (container) {
        window.restaurantLayout = new RestaurantLayout('restaurant-layout-container');
    }
});

// Función global para inicializar manualmente
window.initRestaurantLayout = function () {
    if (window.restaurantLayout) {
        window.restaurantLayout.destroy();
    }
    window.restaurantLayout = new RestaurantLayout('restaurant-layout-container');
};