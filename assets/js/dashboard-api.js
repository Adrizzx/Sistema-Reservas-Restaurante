/**
 * Dashboard Dinámico con APIs - Restaurante Elegante
 * Sistema de actualización en tiempo real
 * Versión: 2.0
 */

class RestauranteDashboardAPI {
    constructor() {
        this.intervalos = [];
        this.ultimaActualizacion = null;
        this.configuracion = {
            intervalos: {
                estadisticas: 15000,  // 15 segundos
                mesas: 10000,         // 10 segundos
                reservas: 20000       // 20 segundos
            },
            animaciones: true,
            notificaciones: true,
            debug: false
        };

        this.init();
    }

    /**
     * Inicializar el dashboard dinámico
     */
    async init() {
        console.log('🚀 Inicializando Dashboard Dinámico...');

        try {
            // Cargar datos iniciales
            await this.cargarDatosIniciales();

            // Configurar actualizaciones automáticas
            this.configurarActualizacionesAutomaticas();

            // Configurar eventos de interfaz
            this.configurarEventosUI();

            // Mostrar indicador de estado
            this.mostrarEstadoConexion('conectado');

            console.log('✅ Dashboard inicializado correctamente');
        } catch (error) {
            console.error('❌ Error inicializando dashboard:', error);
            this.mostrarEstadoConexion('error');
        }
    }

    /**
     * Cargar todos los datos iniciales
     */
    async cargarDatosIniciales() {
        const promises = [
            this.actualizarEstadisticas(),
            this.actualizarMesas(),
            this.actualizarReservas()
        ];

        await Promise.all(promises);
    }

    /**
     * Configurar las actualizaciones automáticas
     */
    configurarActualizacionesAutomaticas() {
        // Limpiar intervalos existentes
        this.intervalos.forEach(intervalo => clearInterval(intervalo));
        this.intervalos = [];

        // Estadísticas cada 15 segundos
        this.intervalos.push(
            setInterval(() => this.actualizarEstadisticas(), this.configuracion.intervalos.estadisticas)
        );

        // Estado de mesas cada 10 segundos
        this.intervalos.push(
            setInterval(() => this.actualizarMesas(), this.configuracion.intervalos.mesas)
        );

        // Reservas cada 20 segundos
        this.intervalos.push(
            setInterval(() => this.actualizarReservas(), this.configuracion.intervalos.reservas)
        );

        console.log('⏰ Actualizaciones automáticas configuradas');
    }

    /**
     * Actualizar estadísticas del dashboard
     */
    async actualizarEstadisticas() {
        try {
            const response = await fetch('app/api/dashboard_stats.php', {
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();

            if (data.success) {
                this.renderizarEstadisticas(data.data);
                this.log('📊 Estadísticas actualizadas');
            } else {
                throw new Error(data.message || 'Error en la respuesta');
            }
        } catch (error) {
            console.error('Error actualizando estadísticas:', error);
            this.mostrarEstadoConexion('error');
        }
    }

    /**
     * Actualizar estado de mesas
     */
    async actualizarMesas() {
        try {
            const response = await fetch('app/api/mesas_estado.php', {
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();

            if (data.success) {
                this.renderizarMesas(data.mesas, data.resumen);
                this.log('🪑 Estado de mesas actualizado');
            } else {
                throw new Error(data.message || 'Error en la respuesta');
            }
        } catch (error) {
            console.error('Error actualizando mesas:', error);
        }
    }

    /**
     * Actualizar reservas recientes
     */
    async actualizarReservas() {
        try {
            // Obtener reservas próximas
            const response = await fetch('app/api/reservas_recientes.php?tipo=proximas&limit=5', {
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();

            if (data.success) {
                this.renderizarReservas(data.reservas, data.estadisticas);
                this.log('📅 Reservas actualizadas');
            } else {
                throw new Error(data.message || 'Error en la respuesta');
            }
        } catch (error) {
            console.error('Error actualizando reservas:', error);
        }
    }

    /**
     * Renderizar estadísticas en el dashboard
     */
    renderizarEstadisticas(stats) {
        // Animar números con efecto contador
        this.animarContador('total-mesas', stats.total_mesas);
        this.animarContador('mesas-disponibles', stats.mesas_disponibles);
        this.animarContador('reservas-hoy', stats.reservas_hoy);
        this.animarContador('clientes-registrados', stats.clientes_registrados);

        // Actualizar porcentaje de ocupación
        this.actualizarPorcentajeOcupacion(stats.porcentaje_ocupacion);

        // Actualizar timestamp
        const timestampEl = document.getElementById('ultima-actualizacion');
        if (timestampEl) {
            timestampEl.textContent = `Última actualización: ${new Date().toLocaleTimeString()}`;
        }
    }

    /**
     * Renderizar estado de mesas
     */
    renderizarMesas(mesas, resumen) {
        const containerMesas = document.getElementById('mesas-grid');
        if (!containerMesas) return;

        // Generar HTML para cada mesa
        const mesasHTML = mesas.map(mesa => {
            const estadoClass = mesa.estado === 'ocupada' ? 'mesa-ocupada' : 'mesa-disponible';
            const estadoIcon = mesa.estado === 'ocupada' ? '🔴' : '🟢';

            return `
                <div class="mesa-card ${estadoClass}" data-mesa-id="${mesa.id}">
                    <div class="mesa-header">
                        <span class="mesa-numero">${mesa.numero}</span>
                        <span class="mesa-estado">${estadoIcon}</span>
                    </div>
                    <div class="mesa-info">
                        <div class="capacidad">👥 ${mesa.capacidad} personas</div>
                        <div class="ubicacion">📍 ${mesa.ubicacion}</div>
                        ${mesa.reserva ? `
                            <div class="reserva-info">
                                <div class="cliente">👤 ${mesa.reserva.cliente}</div>
                                <div class="hora">🕐 ${mesa.reserva.hora}</div>
                            </div>
                        ` : '<div class="disponible-text">✨ Disponible</div>'}
                    </div>
                    ${mesa.estado === 'ocupada' ? `
                        <button class="btn-liberar" onclick="dashboard.liberarMesa('${mesa.id}')">
                            Liberar Mesa
                        </button>
                    ` : ''}
                </div>
            `;
        }).join('');

        containerMesas.innerHTML = mesasHTML;

        // Actualizar contador de disponibilidad
        const disponibilidadEl = document.getElementById('mesas-disponibilidad');
        if (disponibilidadEl) {
            disponibilidadEl.innerHTML = `
                <span class="disponibles">${resumen.disponibles} disponibles</span> / 
                <span class="ocupadas">${resumen.ocupadas} ocupadas</span>
            `;
        }
    }

    /**
     * Renderizar reservas recientes
     */
    renderizarReservas(reservas, stats) {
        const containerReservas = document.getElementById('reservas-recientes');
        if (!containerReservas) return;

        const reservasHTML = reservas.map(reserva => {
            const urgenciaClass = reserva.urgencia || '';

            return `
                <div class="reserva-item ${urgenciaClass}" data-reserva-id="${reserva.id}">
                    <div class="reserva-header">
                        <span class="cliente-nombre">${reserva.cliente.nombre}</span>
                        <span class="estado-badge" style="background-color: ${reserva.estado_color}">
                            ${reserva.estado_texto}
                        </span>
                    </div>
                    <div class="reserva-detalles">
                        <div class="fecha-hora">📅 ${reserva.fecha} 🕐 ${reserva.hora}</div>
                        <div class="mesa-personas">🪑 ${reserva.mesa.numero} - 👥 ${reserva.personas} personas</div>
                        ${reserva.tiempo_restante ? `
                            <div class="tiempo-restante">⏰ En ${reserva.tiempo_restante}</div>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');

        containerReservas.innerHTML = reservasHTML;
    }

    /**
     * Animar contador numérico
     */
    animarContador(elementId, valorNuevo) {
        const elemento = document.getElementById(elementId);
        if (!elemento) return;

        const valorActual = parseInt(elemento.textContent) || 0;
        const diferencia = valorNuevo - valorActual;

        if (diferencia === 0) return;

        const pasos = 20;
        const incremento = diferencia / pasos;
        let contador = valorActual;
        let paso = 0;

        const intervalo = setInterval(() => {
            paso++;
            contador += incremento;

            if (paso >= pasos) {
                elemento.textContent = valorNuevo;
                clearInterval(intervalo);

                // Efecto visual de actualización
                if (this.configuracion.animaciones) {
                    elemento.classList.add('actualizado');
                    setTimeout(() => elemento.classList.remove('actualizado'), 1000);
                }
            } else {
                elemento.textContent = Math.round(contador);
            }
        }, 50);
    }

    /**
     * Actualizar porcentaje de ocupación con barra animada
     */
    actualizarPorcentajeOcupacion(porcentaje) {
        const barraEl = document.getElementById('barra-ocupacion');
        const textoEl = document.getElementById('porcentaje-ocupacion');

        if (barraEl) {
            barraEl.style.width = `${porcentaje}%`;
            barraEl.style.transition = 'width 0.8s ease-in-out';
        }

        if (textoEl) {
            textoEl.textContent = `${porcentaje}%`;
        }
    }

    /**
     * Liberar mesa (acción manual)
     */
    async liberarMesa(mesaId) {
        try {
            const response = await fetch('app/api/mesas_estado.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mesa_id: mesaId,
                    accion: 'liberar'
                })
            });

            const data = await response.json();

            if (data.success) {
                // Actualizar inmediatamente
                this.actualizarMesas();
                this.actualizarEstadisticas();

                // Notificación
                if (this.configuracion.notificaciones) {
                    this.mostrarNotificacion('Mesa liberada correctamente', 'success');
                }
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error liberando mesa:', error);
            this.mostrarNotificacion('Error liberando mesa', 'error');
        }
    }

    /**
     * Configurar eventos de interfaz
     */
    configurarEventosUI() {
        // Botón de actualización manual
        const btnActualizar = document.getElementById('btn-actualizar-manual');
        if (btnActualizar) {
            btnActualizar.addEventListener('click', () => {
                this.cargarDatosIniciales();
                this.mostrarNotificacion('Dashboard actualizado manualmente', 'info');
            });
        }

        // Toggle de actualizaciones automáticas
        const toggleAuto = document.getElementById('toggle-auto-update');
        if (toggleAuto) {
            toggleAuto.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.configurarActualizacionesAutomaticas();
                } else {
                    this.intervalos.forEach(intervalo => clearInterval(intervalo));
                    this.intervalos = [];
                }
            });
        }
    }

    /**
     * Mostrar estado de conexión
     */
    mostrarEstadoConexion(estado) {
        const indicador = document.getElementById('indicador-conexion');
        if (!indicador) return;

        const estados = {
            conectado: { color: '#28a745', texto: 'Conectado', icon: '🟢' },
            error: { color: '#dc3545', texto: 'Error de conexión', icon: '🔴' },
            cargando: { color: '#ffc107', texto: 'Actualizando...', icon: '🟡' }
        };

        const config = estados[estado] || estados.error;
        indicador.innerHTML = `${config.icon} ${config.texto}`;
        indicador.style.color = config.color;
    }

    /**
     * Mostrar notificación temporal
     */
    mostrarNotificacion(mensaje, tipo = 'info') {
        const contenedor = document.getElementById('notificaciones') || document.body;

        const notificacion = document.createElement('div');
        notificacion.className = `notificacion notificacion-${tipo}`;
        notificacion.innerHTML = `
            <span>${mensaje}</span>
            <button onclick="this.parentElement.remove()">✕</button>
        `;

        contenedor.appendChild(notificacion);

        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            if (notificacion.parentElement) {
                notificacion.remove();
            }
        }, 5000);
    }

    /**
     * Log para debug
     */
    log(mensaje) {
        if (this.configuracion.debug) {
            console.log(`[Dashboard] ${mensaje} - ${new Date().toLocaleTimeString()}`);
        }
    }

    /**
     * Destruir dashboard (limpiar intervalos)
     */
    destruir() {
        this.intervalos.forEach(intervalo => clearInterval(intervalo));
        this.intervalos = [];
        console.log('🗑️ Dashboard destruido');
    }
}

// CSS para las animaciones y estilos
const estilosDashboard = `
<style>
.actualizado {
    animation: pulso-actualizado 0.8s ease-in-out;
    color: #28a745 !important;
    font-weight: bold;
}

@keyframes pulso-actualizado {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); background-color: rgba(40, 167, 69, 0.1); }
    100% { transform: scale(1); }
}

.mesa-card {
    border: 2px solid #ddd;
    border-radius: 10px;
    padding: 15px;
    margin: 10px;
    transition: all 0.3s ease;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.mesa-ocupada {
    border-color: #dc3545;
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(255, 255, 255, 0.9) 100%);
}

.mesa-disponible {
    border-color: #28a745;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(255, 255, 255, 0.9) 100%);
}

.mesa-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.mesa-numero {
    font-weight: bold;
    font-size: 1.2em;
}

.btn-liberar {
    background: #dc3545;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 10px;
    width: 100%;
    transition: all 0.3s ease;
}

.btn-liberar:hover {
    background: #c82333;
    transform: translateY(-2px);
}

.reserva-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 12px;
    margin: 8px 0;
    background: white;
    transition: all 0.3s ease;
}

.reserva-item.alta {
    border-left: 4px solid #dc3545;
    background: rgba(220, 53, 69, 0.05);
}

.reserva-item.media {
    border-left: 4px solid #ffc107;
    background: rgba(255, 193, 7, 0.05);
}

.reserva-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.estado-badge {
    padding: 4px 8px;
    border-radius: 12px;
    color: white;
    font-size: 0.8em;
    font-weight: bold;
}

.notificacion {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-width: 300px;
    z-index: 1000;
    animation: slideIn 0.3s ease-out;
}

.notificacion-success { background: #28a745; }
.notificacion-error { background: #dc3545; }
.notificacion-info { background: #17a2b8; }

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

#indicador-conexion {
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.9);
}

#barra-ocupacion {
    height: 20px;
    background: linear-gradient(135deg, #d4af37 0%, #ffd700 100%);
    border-radius: 10px;
    transition: width 0.8s ease-in-out;
}
</style>
`;

// Insertar estilos en el documento
document.head.insertAdjacentHTML('beforeend', estilosDashboard);

// Variable global para acceso fácil
let dashboard;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    dashboard = new RestauranteDashboardAPI();

    // Exponer globalmente para debugging
    window.dashboard = dashboard;
});

// Limpiar al cerrar la página
window.addEventListener('beforeunload', function () {
    if (dashboard) {
        dashboard.destruir();
    }
});