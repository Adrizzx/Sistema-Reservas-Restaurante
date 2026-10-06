class RestaurantLayout {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Contenedor no encontrado:', containerId);
            return;
        }
        this.mesas = [];
        this.updateInterval = null;
        this.init();
    }

    async init() {
        console.log('Inicializando RestaurantLayout...');
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
            if (!response.ok) {
                throw new Error('Error en la respuesta de la API');
            }

            const data = await response.json();

            if (data.success) {
                this.mesas = data.mesas;
                console.log('Mesas cargadas:', this.mesas.length);
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
        console.log('Mesas por zona:', mesasPorZona);

        // Renderizar mesas en cada zona
        Object.entries(mesasPorZona).forEach(([zona, mesas]) => {
            const container = document.querySelector(`[data-zona="${zona}"]`);

            if (container) {
                console.log(`Renderizando ${mesas.length} mesas en zona ${zona}`);
                mesas.forEach(mesa => {
                    const mesaElement = this.createMesaElement(mesa);
                    container.appendChild(mesaElement);
                });
            } else {
                console.error(`Contenedor no encontrado para zona: ${zona}`);
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

        let mensaje = `Mesa ${mesa.numero}
        
🏷️ Zona: ${this.getZonaText(mesa.ubicacion)}
🎯 Estado: ${this.getEstadoText(mesa.estado)}
👥 Capacidad: ${capacidadText}`;

        if (mesa.descripcion) {
            mensaje += `\n💬 ${mesa.descripcion}`;
        }

        if (mesa.reserva) {
            mensaje += `\n
📋 Reserva:
👤 Cliente: ${mesa.reserva.cliente}
📞 Teléfono: ${mesa.reserva.telefono || 'No disponible'}
📅 Fecha: ${mesa.reserva.fecha}
🕐 Hora: ${mesa.reserva.hora}
👥 Personas: ${mesa.reserva.personas}`;

            if (mesa.reserva.notas) {
                mensaje += `\n📝 Notas: ${mesa.reserva.notas}`;
            }
        } else {
            mensaje += '\n\nSin reserva activa';
        }

        alert(mensaje);
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

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM cargado, buscando contenedor...');
    const container = document.getElementById('restaurant-layout-container');
    if (container) {
        console.log('Contenedor encontrado, inicializando RestaurantLayout...');
        window.restaurantLayout = new RestaurantLayout('restaurant-layout-container');
    } else {
        console.log('Contenedor no encontrado aún, esperando...');
    }
});

// Función global para inicializar manualmente
window.initRestaurantLayout = function () {
    console.log('Inicialización manual solicitada...');
    if (window.restaurantLayout) {
        window.restaurantLayout.destroy();
    }
    window.restaurantLayout = new RestaurantLayout('restaurant-layout-container');
};