<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría del Sistema</title>
    <link rel="stylesheet" href="../public/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .audit-card { border-left: 4px solid #0d6efd; margin-bottom: 1rem; }
        .audit-card.horarios { border-left-color: #0dcaf0; }
        .audit-card.reservas { border-left-color: #198754; }
        .audit-card.cancelacion { border-left-color: #dc3545; }
        .badge-admin { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .json-viewer { background: #f8f9fa; padding: 1rem; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 0.9rem; }
        .diff-removed { background: #ffebee; color: #c62828; }
        .diff-added { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>
<?php
session_start();

// Verificar que el usuario es administrador
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin.php');
    exit();
}

$admin_nombre = $_SESSION['admin_nombre'] ?? 'Administrador';
$admin_id = $_SESSION['admin_id'];
?>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="bi bi-clipboard-data"></i> Auditoría del Sistema</h2>
                    <div>
                        <span class="badge bg-primary me-2"><?php echo htmlspecialchars($admin_nombre); ?></span>
                        <a href="../admin.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver al Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 id="total-cambios-horarios" class="text-primary">--</h3>
                        <p class="mb-0">Cambios de Horarios</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 id="total-acciones-reservas" class="text-success">--</h3>
                        <p class="mb-0">Acciones en Reservas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 id="total-canceladas" class="text-danger">--</h3>
                        <p class="mb-0">Reservas Canceladas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 id="total-notificaciones" class="text-info">--</h3>
                        <p class="mb-0">Notificaciones Enviadas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Tipo de Auditoría</label>
                        <select id="tipo-auditoria" class="form-select">
                            <option value="horarios">Cambios de Horarios</option>
                            <option value="admin">Mis Acciones</option>
                            <option value="resumen">Resumen General</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Límite de Registros</label>
                        <select id="limite" class="form-select">
                            <option value="25">25</option>
                            <option value="50" selected>50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary w-100" onclick="cargarAuditoria()">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-success w-100" onclick="exportarCSV()">
                            <i class="bi bi-file-earmark-excel"></i> Exportar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resultados -->
        <div id="resultados-auditoria">
            <div class="text-center py-5">
                <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted">Selecciona un tipo de auditoría y haz clic en Buscar</p>
            </div>
        </div>
    </div>

    <script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Cargar resumen al inicio
        cargarResumen();

        async function cargarResumen() {
            try {
                const response = await fetch('../app/api/auditoria.php?tipo=resumen');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('total-cambios-horarios').textContent = data.resumen.total_cambios_horarios || 0;
                    document.getElementById('total-acciones-reservas').textContent = data.resumen.total_acciones_reservas || 0;
                    document.getElementById('total-canceladas').textContent = data.resumen.total_reservas_canceladas || 0;
                    document.getElementById('total-notificaciones').textContent = data.resumen.total_notificaciones || 0;
                }
            } catch (error) {
                console.error('Error cargando resumen:', error);
            }
        }

        async function cargarAuditoria() {
            const tipo = document.getElementById('tipo-auditoria').value;
            const limite = document.getElementById('limite').value;
            
            Swal.fire({
                title: 'Cargando...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            try {
                const response = await fetch(`../app/api/auditoria.php?tipo=${tipo}&limite=${limite}`);
                const data = await response.json();
                
                Swal.close();
                
                if (data.success) {
                    mostrarResultados(data, tipo);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'No se pudo cargar la auditoría', 'error');
                console.error(error);
            }
        }

        function mostrarResultados(data, tipo) {
            const container = document.getElementById('resultados-auditoria');
            
            if (!data.datos || data.datos.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No se encontraron registros
                    </div>
                `;
                return;
            }
            
            let html = '';
            
            if (tipo === 'horarios') {
                html = data.datos.map(item => `
                    <div class="card audit-card horarios">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title">
                                        <i class="bi bi-clock-history"></i> Cambio de Horarios
                                    </h5>
                                    <p class="text-muted mb-2">
                                        <span class="badge badge-admin">${item.admin}</span>
                                        <span class="ms-2"><i class="bi bi-calendar"></i> ${item.fecha}</span>
                                        <span class="ms-2"><i class="bi bi-geo-alt"></i> ${item.ip}</span>
                                    </p>
                                </div>
                                <div class="text-end">
                                    ${item.reservas_afectadas > 0 ? `
                                        <span class="badge bg-danger">${item.reservas_canceladas} Canceladas</span>
                                        <span class="badge bg-info">${item.notificaciones_enviadas} WhatsApp</span>
                                    ` : '<span class="badge bg-success">Sin afectaciones</span>'}
                                </div>
                            </div>
                            
                            ${item.observaciones ? `
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="bi bi-exclamation-triangle"></i> ${item.observaciones}
                                </div>
                            ` : ''}
                            
                            <div class="mt-3">
                                <button class="btn btn-sm btn-outline-primary" onclick="verDetalles(${item.id}, 'horarios')">
                                    <i class="bi bi-eye"></i> Ver Detalles
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else if (tipo === 'admin') {
                html = data.datos.map(item => `
                    <div class="card audit-card ${item.tipo === 'horarios' ? 'horarios' : 'reservas'}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span class="badge ${item.tipo === 'horarios' ? 'bg-info' : 'bg-success'}">
                                        ${item.tipo.toUpperCase()}
                                    </span>
                                    <strong class="ms-2">${item.accion}</strong>
                                </div>
                                <small class="text-muted">${item.fecha}</small>
                            </div>
                            ${item.observaciones ? `<p class="mb-0 mt-2">${item.observaciones}</p>` : ''}
                            ${item.reservas_afectadas ? `
                                <span class="badge bg-warning text-dark mt-2">${item.reservas_afectadas} reservas afectadas</span>
                            ` : ''}
                        </div>
                    </div>
                `).join('');
            }
            
            container.innerHTML = html;
        }

        function verDetalles(id, tipo) {
            fetch(`../app/api/auditoria.php?tipo=${tipo}&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success || !data.datos || data.datos.length === 0) {
                        Swal.fire('Error', 'No se encontraron detalles', 'error');
                        return;
                    }
                    
                    const item = data.datos[0];
                    let html = '';
                    
                    if (tipo === 'horarios') {
                        const antes = JSON.parse(item.configuracion_anterior || '{}');
                        const despues = JSON.parse(item.configuracion_nueva || '{}');
                        
                        html = `
                            <div class="text-start">
                                <h6><i class="bi bi-person-badge"></i> Administrador: <strong>${item.admin}</strong></h6>
                                <p><i class="bi bi-calendar"></i> Fecha: ${item.fecha}</p>
                                <p><i class="bi bi-geo-alt"></i> IP: ${item.ip}</p>
                                <hr>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-danger"><i class="bi bi-dash-circle"></i> Configuración Anterior</h6>
                                        <div class="json-viewer diff-removed">
                                            <pre>${JSON.stringify(antes, null, 2)}</pre>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-success"><i class="bi bi-plus-circle"></i> Configuración Nueva</h6>
                                        <div class="json-viewer diff-added">
                                            <pre>${JSON.stringify(despues, null, 2)}</pre>
                                        </div>
                                    </div>
                                </div>
                                
                                ${item.reservas_afectadas > 0 ? `
                                    <hr>
                                    <div class="alert alert-warning">
                                        <strong><i class="bi bi-exclamation-triangle"></i> Impacto:</strong><br>
                                        • ${item.reservas_canceladas} reservas canceladas<br>
                                        • ${item.notificaciones_enviadas} notificaciones WhatsApp enviadas
                                    </div>
                                ` : ''}
                                
                                ${item.observaciones ? `
                                    <div class="alert alert-info">
                                        <strong>Observaciones:</strong><br>
                                        ${item.observaciones}
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }
                    
                    Swal.fire({
                        title: 'Detalles de Auditoría',
                        html: html,
                        width: '800px',
                        showCloseButton: true,
                        showConfirmButton: false
                    });
                })
                .catch(err => {
                    Swal.fire('Error', 'No se pudieron cargar los detalles', 'error');
                    console.error(err);
                });
        }

        function exportarCSV() {
            Swal.fire({
                title: 'Exportar a CSV',
                text: 'Función en desarrollo',
                icon: 'info'
            });
        }
    </script>
</body>
</html>
