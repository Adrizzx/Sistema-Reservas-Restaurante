<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Reservas - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gold-color: #d4af37;
            --dark-bg: #1a1a1a;
        }
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            color: white;
            padding: 20px;
        }
        .reserva-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .reserva-card:hover {
            border-color: var(--gold-color);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.3);
        }
        .reserva-card.pendiente {
            border-left: 5px solid #ff9800;
        }
        .reserva-card.confirmada {
            border-left: 5px solid #4CAF50;
        }
        .btn-confirm {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.3s;
        }
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }
        .btn-reject {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        .badge-pendiente {
            background: #ff9800;
            color: white;
        }
        .badge-confirmada {
            background: #4CAF50;
            color: white;
        }
        h1 {
            color: var(--gold-color);
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 0 20px rgba(212, 175, 55, 0.5);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .info-label {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
        }
        .info-value {
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-clipboard-check"></i> Confirmar Reservas</h1>
        
        <div class="text-center mb-4">
            <a href="admin.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Panel
            </a>
        </div>

        <div id="reservas-container"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Cargar reservas
        async function cargarReservas() {
            try {
                const response = await fetch('../app/obtener_reservas.php?tipo=todas');
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Error al cargar reservas');
                }
                
                mostrarReservas(data.reservas);
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron cargar las reservas',
                    background: '#1a1a1a',
                    color: '#ffffff'
                });
            }
        }

        function mostrarReservas(reservas) {
            const container = document.getElementById('reservas-container');
            
            // Filtrar y ordenar: primero pendientes, luego confirmadas
            const pendientes = reservas.filter(r => r.estado === 'pendiente');
            const confirmadas = reservas.filter(r => r.estado === 'confirmada');
            const ordenadas = [...pendientes, ...confirmadas];
            
            if (ordenadas.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> No hay reservas para mostrar
                    </div>
                `;
                return;
            }
            
            container.innerHTML = ordenadas.map(reserva => `
                <div class="reserva-card ${reserva.estado}" id="reserva-${reserva.id}">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 style="color: var(--gold-color); margin: 0;">
                                <i class="fas fa-user"></i> ${reserva.cliente_nombre} ${reserva.cliente_apellido}
                            </h4>
                            <small style="color: rgba(255,255,255,0.6);">Mesa: ${reserva.mesa}</small>
                        </div>
                        <span class="status-badge badge-${reserva.estado}">
                            ${reserva.estado === 'pendiente' ? '⏳ PENDIENTE' : '✓ CONFIRMADA'}
                        </span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-calendar"></i> Fecha:</span>
                        <span class="info-value">${formatearFecha(reserva.fecha_reserva)}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-clock"></i> Hora:</span>
                        <span class="info-value">${reserva.hora_reserva}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-chair"></i> Mesa:</span>
                        <span class="info-value">${reserva.mesa}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-users"></i> Personas:</span>
                        <span class="info-value">${reserva.numero_personas}</span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label"><i class="fas fa-phone"></i> Teléfono:</span>
                        <span class="info-value">${reserva.cliente_telefono}</span>
                    </div>
                    
                    ${reserva.estado === 'pendiente' ? `
                        <div class="mt-3 d-flex gap-2 justify-content-end">
                            <button class="btn btn-confirm" onclick="confirmarReserva(${reserva.id})">
                                <i class="fas fa-check"></i> Confirmar y Enviar WhatsApp
                            </button>
                            <button class="btn btn-reject" onclick="rechazarReserva(${reserva.id})">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    ` : `
                        <div class="mt-3 text-center">
                            <i class="fas fa-check-circle" style="color: #4CAF50; font-size: 1.2rem;"></i>
                            <span style="color: #4CAF50; margin-left: 10px;">Reserva confirmada y WhatsApp enviado</span>
                        </div>
                    `}
                </div>
            `).join('');
        }

        function formatearFecha(fecha) {
            const d = new Date(fecha + 'T00:00:00');
            const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
            return d.toLocaleDateString('es-ES', opciones);
        }

        async function confirmarReserva(id) {
            const result = await Swal.fire({
                title: '¿Confirmar Reserva?',
                html: `
                    <p>Se confirmará la reserva y se enviará un WhatsApp de confirmación al cliente.</p>
                    <p style="color: #ff9800;"><strong>¿Desea continuar?</strong></p>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, Confirmar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#4CAF50',
                cancelButtonColor: '#f44336',
                background: '#1a1a1a',
                color: '#ffffff'
            });
            
            if (!result.isConfirmed) return;
            
            try {
                Swal.fire({
                    title: 'Confirmando...',
                    html: 'Enviando WhatsApp al cliente...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                const response = await fetch('../app/api/confirmar_reserva_admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ reserva_id: id })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: '¡Confirmado!',
                        html: `
                            <p>Reserva confirmada exitosamente</p>
                            <p>WhatsApp: ${data.whatsapp.enviado ? '✓ Enviado' : '✗ Error: ' + data.whatsapp.error}</p>
                        `,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#4CAF50',
                        background: '#1a1a1a',
                        color: '#ffffff'
                    });
                    
                    cargarReservas();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo confirmar la reserva',
                    background: '#1a1a1a',
                    color: '#ffffff'
                });
            }
        }

        async function rechazarReserva(id) {
            const result = await Swal.fire({
                title: '¿Cancelar Reserva?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, Cancelar',
                cancelButtonText: 'No',
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#666',
                background: '#1a1a1a',
                color: '#ffffff'
            });
            
            if (!result.isConfirmed) return;
            
            try {
                const response = await fetch('../app/editar_reserva.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        id: id,
                        action: 'cambiar_estado',
                        nuevo_estado: 'cancelada'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelada',
                        text: 'La reserva ha sido cancelada',
                        confirmButtonColor: '#4CAF50',
                        background: '#1a1a1a',
                        color: '#ffffff'
                    });
                    cargarReservas();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo cancelar la reserva',
                    background: '#1a1a1a',
                    color: '#ffffff'
                });
            }
        }

        // Cargar al inicio y actualizar cada 30 segundos
        cargarReservas();
        setInterval(cargarReservas, 30000);
    </script>
</body>
</html>
