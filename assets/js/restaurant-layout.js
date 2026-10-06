/**
 * RestaurantLayout.js - Visualización interactiva del restaurante
 * Muestra las mesas distribuidas por zonas con estados en tiempo real
 */

class RestaurantLayout {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.mesas = [];
        this.updateInterval = null;
        this.init();
    }

    class RestaurantLayout {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.mesas = [];
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
            
            const response = await fetch('app/api/mesas_estado.php');
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
        mesaEl.className = `mesa mesa-${mesa.estado}`;
        mesaEl.innerHTML = `
            ${mesa.numero}
            <div class="mesa-tooltip">
                ${this.getMesaTooltipContent(mesa)}
            </div>
        `;

        mesaEl.addEventListener('click', () => this.onMesaClick(mesa));
        
        return mesaEl;
    }

    getMesaTooltipContent(mesa) {
        let content = `<strong>🪑 Mesa ${mesa.numero}</strong><br>`;
        content += `👥 ${mesa.capacidad} personas<br>`;
        content += `${this.getEstadoIcon(mesa.estado)} ${this.getEstadoText(mesa.estado)}<br>`;
        content += `📍 ${this.getZonaText(mesa.ubicacion)}`;
        
        if (mesa.reserva) {
            content += `<br><br><strong>📋 Reserva Activa:</strong><br>`;
            content += `👤 ${mesa.reserva.cliente}<br>`;
            content += `🕐 ${mesa.reserva.hora}<br>`;
            content += `👥 ${mesa.reserva.personas} personas`;
            if (mesa.reserva.observaciones) {
                content += `<br>📝 ${mesa.reserva.observaciones.substring(0, 30)}${mesa.reserva.observaciones.length > 30 ? '...' : ''}`;
            }
        } else if (mesa.estado === 'disponible') {
            content += `<br><br><span style="color: #32d74b;">✨ Lista para reservar</span>`;
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
        const estados = {
            'disponible': '🟢 Disponible',
            'ocupada': '🔴 Ocupada',
            'reservada': '🟡 Reservada',
            'mantenimiento': '⚫ Mantenimiento'
        };
        return estados[estado] || estado;
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
        const info = {
            title: `🪑 Mesa ${mesa.numero}`,
            html: `
                <div class="mesa-info-modal">
                    <h5>🏷️ Información General</h5>
                    <p><strong>Zona:</strong> ${this.getZonaText(mesa.ubicacion)}</p>
                    <p><strong>Estado:</strong> 
                        <span class="badge badge-${mesa.estado}">
                            ${this.getEstadoIcon(mesa.estado)} ${this.getEstadoText(mesa.estado)}
                        </span>
                    </p>
                    <p><strong>Capacidad:</strong> 👥 ${mesa.capacidad} personas</p>
                    
                    ${mesa.reserva ? `
                        <hr>
                        <h6>📋 Información de la Reserva:</h6>
                        <p><strong>Cliente:</strong> ${mesa.reserva.cliente}</p>
                        <p><strong>Teléfono:</strong> ${mesa.reserva.telefono || 'No disponible'}</p>
                        <p><strong>Fecha:</strong> ${mesa.reserva.fecha}</p>
                        <p><strong>Hora:</strong> ${mesa.reserva.hora}</p>
                        <p><strong>Personas:</strong> ${mesa.reserva.personas}</p>
                        ${mesa.reserva.observaciones ? `<p><strong>Observaciones:</strong> ${mesa.reserva.observaciones}</p>` : ''}
                    ` : '<p class="text-muted">Sin reserva activa</p>'}
                </div>
            `,
            icon: mesa.estado === 'disponible' ? 'success' : 
                  mesa.estado === 'ocupada' ? 'error' :
                  mesa.estado === 'reservada' ? 'warning' : 'info',
            confirmButtonText: '✅ Cerrar',
            width: 400
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire(info);
        } else {
            alert(`Mesa ${mesa.numero}\nEstado: ${mesa.estado}\nCapacidad: ${mesa.capacidad} personas`);
        }
    }



    updateStats(resumen) {
        document.getElementById('statDisponibles').textContent = resumen.disponibles || 0;
        document.getElementById('statOcupadas').textContent = resumen.ocupadas || 0;
        
        // Calcular reservadas (que no sea ni disponible ni ocupada)
        const reservadas = this.mesas.filter(m => m.estado === 'reservada').length;
        document.getElementById('statReservadas').textContent = reservadas;
        
        document.getElementById('statOcupacion').textContent = `${resumen.porcentaje_ocupacion || 0}%`;
    }

    showError(message) {
        const zones = document.getElementById('restaurantZones');
        zones.innerHTML = `
            <div class="loading-restaurant">
                <div class="text-danger">⚠️</div>
                <div class="text-danger">${message}</div>
                <button class="btn btn-sm btn-outline-primary mt-2" onclick="restaurantLayout.loadMesas()">
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
        this.container.innerHTML = '';
    }
}

// Función global para inicializar
window.initRestaurantLayout = function(containerId) {
    return new RestaurantLayout(containerId);
};

// Auto-inicializar si existe el contenedor
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('restaurant-layout-container');
    if (container) {
        window.restaurantLayout = new RestaurantLayout('restaurant-layout-container');
    }
});