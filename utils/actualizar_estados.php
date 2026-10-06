<?php
/**
 * Utilidad para ejecutar manualmente la actualización de estados de reservas
 * Acceder a: http://localhost/PRY_PROYECTO/utils/actualizar_estados.php
 */

require_once __DIR__ . '/../conexion/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Estados de Reservas</title>
    <link rel="stylesheet" href="../public/bootstrap/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #3d2e24 0%, #2d1b12 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 600px;
        }
        .btn-actualizar {
            background: linear-gradient(135deg, #d4af37 0%, #b8941f 100%);
            color: #2d1b12;
            font-weight: 600;
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
        }
        .resultado {
            margin-top: 20px;
            padding: 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4" style="color: #d4af37;">
            🔄 Actualizar Estados de Reservas
        </h2>
        
        <div class="text-center mb-4">
            <p class="text-muted">
                Este proceso actualizará automáticamente los estados de todas las reservas según las siguientes reglas:
            </p>
            <ul class="text-start">
                <li>Reservas confirmadas que ya pasaron → <strong>Finalizada</strong></li>
                <li>Reservas confirmadas actuales → <strong>En curso</strong></li>
                <li>Reservas en curso antiguas (+ 3 horas) → <strong>Finalizada</strong></li>
                <li>Libera mesas de reservas finalizadas</li>
            </ul>
        </div>

        <div class="text-center">
            <button id="btnActualizar" class="btn btn-actualizar">
                ⚡ Ejecutar Actualización Ahora
            </button>
        </div>

        <div id="resultado" class="resultado d-none"></div>

        <div class="text-center mt-4">
            <a href="../admin.php" class="btn btn-secondary">← Volver al Panel</a>
        </div>
    </div>

    <script>
        document.getElementById('btnActualizar').addEventListener('click', function() {
            const btn = this;
            const resultado = document.getElementById('resultado');
            
            btn.disabled = true;
            btn.innerHTML = '⏳ Procesando...';
            resultado.classList.add('d-none');

            fetch('../scripts/actualizar_estados_reservas.php')
                .then(response => response.json())
                .then(data => {
                    resultado.classList.remove('d-none');
                    
                    if (data.success) {
                        resultado.className = 'resultado alert alert-success';
                        resultado.innerHTML = `
                            <h5>✅ ¡Actualización Exitosa!</h5>
                            <p><strong>Reservas finalizadas:</strong> ${data.reservas_finalizadas}</p>
                            <p><strong>Hora de ejecución:</strong> ${data.timestamp}</p>
                            <p class="mb-0">${data.message}</p>
                        `;
                    } else {
                        resultado.className = 'resultado alert alert-danger';
                        resultado.innerHTML = `
                            <h5>❌ Error</h5>
                            <p class="mb-0">${data.message}</p>
                        `;
                    }
                })
                .catch(error => {
                    resultado.classList.remove('d-none');
                    resultado.className = 'resultado alert alert-danger';
                    resultado.innerHTML = `
                        <h5>❌ Error de Conexión</h5>
                        <p class="mb-0">No se pudo conectar con el servidor: ${error.message}</p>
                    `;
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '⚡ Ejecutar Actualización Ahora';
                });
        });
    </script>
</body>
</html>
