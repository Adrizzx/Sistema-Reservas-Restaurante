<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Twilio WhatsApp</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .info { color: #2196F3; }
        h1 { color: #333; }
        h2 { color: #555; border-bottom: 2px solid #d4af37; padding-bottom: 10px; }
        pre {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin: 5px;
        }
        .badge-success { background: #4CAF50; color: white; }
        .badge-error { background: #f44336; color: white; }
        .badge-warning { background: #ff9800; color: white; }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #d4af37;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .button:hover { background: #b8962d; }
    </style>
</head>
<body>
    <h1>🔍 Estado del Sistema WhatsApp - Twilio</h1>

    <?php
    require_once 'conexion/db.php';
    $config = require 'config/whatsapp_config.php';
    ?>

    <!-- CONFIGURACIÓN -->
    <div class="card">
        <h2>⚙️ Configuración de Twilio</h2>
        <table style="width:100%">
            <tr>
                <td><strong>Account SID:</strong></td>
                <td><?= $config['twilio_account_sid'] ?></td>
            </tr>
            <tr>
                <td><strong>Sandbox Number:</strong></td>
                <td><?= $config['twilio_whatsapp_from'] ?></td>
            </tr>
            <tr>
                <td><strong>Código de País:</strong></td>
                <td>+<?= $config['country_code'] ?></td>
            </tr>
            <tr>
                <td><strong>Auto Send:</strong></td>
                <td>
                    <?php if ($config['auto_send_enabled']): ?>
                        <span class="status-badge badge-success">✓ HABILITADO</span>
                    <?php else: ?>
                        <span class="status-badge badge-error">✗ DESHABILITADO</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Modo de Prueba:</strong></td>
                <td>
                    <?php if ($config['test_mode']): ?>
                        <span class="status-badge badge-warning">⚠ ACTIVADO (no envía)</span>
                    <?php else: ?>
                        <span class="status-badge badge-success">✓ PRODUCCIÓN</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- ÚLTIMA NOTIFICACIÓN -->
    <div class="card">
        <h2>📨 Última Notificación Enviada</h2>
        <?php
        $stmt = $pdo->query("
            SELECT n.*, r.fecha_reserva, r.hora_reserva, c.nombre, c.apellido
            FROM notificaciones_whatsapp n
            INNER JOIN reservas r ON n.reserva_id = r.id
            INNER JOIN clientes c ON r.cliente_id = c.id
            ORDER BY n.id DESC LIMIT 1
        ");
        $ultima = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ultima):
        ?>
            <table style="width:100%">
                <tr>
                    <td><strong>Reserva ID:</strong></td>
                    <td><?= $ultima['reserva_id'] ?></td>
                </tr>
                <tr>
                    <td><strong>Cliente:</strong></td>
                    <td><?= $ultima['nombre'] . ' ' . $ultima['apellido'] ?></td>
                </tr>
                <tr>
                    <td><strong>Teléfono:</strong></td>
                    <td><?= $ultima['telefono'] ?> (whatsapp:+<?= $config['country_code'] . ltrim($ultima['telefono'], '0') ?>)</td>
                </tr>
                <tr>
                    <td><strong>Estado:</strong></td>
                    <td>
                        <?php if ($ultima['estado'] == 'enviado'): ?>
                            <span class="status-badge badge-success">✓ ENVIADO</span>
                        <?php elseif ($ultima['estado'] == 'fallido'): ?>
                            <span class="status-badge badge-error">✗ FALLIDO</span>
                        <?php else: ?>
                            <span class="status-badge badge-warning">⏳ PENDIENTE</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>SID Twilio:</strong></td>
                    <td><code><?= $ultima['sid_twilio'] ?: 'N/A' ?></code></td>
                </tr>
                <tr>
                    <td><strong>Fecha/Hora:</strong></td>
                    <td><?= $ultima['fecha_envio'] ?></td>
                </tr>
                <?php if ($ultima['error_mensaje']): ?>
                <tr>
                    <td><strong>Error:</strong></td>
                    <td class="error"><?= $ultima['error_mensaje'] ?></td>
                </tr>
                <?php endif; ?>
            </table>
            
            <h3>Mensaje Enviado:</h3>
            <pre><?= htmlspecialchars($ultima['mensaje']) ?></pre>
        <?php else: ?>
            <p class="warning">⚠ No hay notificaciones registradas</p>
        <?php endif; ?>
    </div>

    <!-- ESTADÍSTICAS -->
    <div class="card">
        <h2>📊 Estadísticas</h2>
        <?php
        $stats = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados,
                SUM(CASE WHEN estado = 'fallido' THEN 1 ELSE 0 END) as fallidos,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes
            FROM notificaciones_whatsapp
        ")->fetch(PDO::FETCH_ASSOC);
        
        $reservas_sin_notif = $pdo->query("
            SELECT COUNT(*) as total
            FROM reservas r
            LEFT JOIN notificaciones_whatsapp n ON r.id = n.reserva_id
            WHERE n.id IS NULL
        ")->fetchColumn();
        ?>
        
        <table style="width:100%">
            <tr>
                <td><strong>Total Notificaciones:</strong></td>
                <td><?= $stats['total'] ?></td>
            </tr>
            <tr>
                <td><strong>Enviadas Exitosamente:</strong></td>
                <td class="success"><?= $stats['enviados'] ?></td>
            </tr>
            <tr>
                <td><strong>Fallidas:</strong></td>
                <td class="error"><?= $stats['fallidos'] ?></td>
            </tr>
            <tr>
                <td><strong>Pendientes:</strong></td>
                <td class="warning"><?= $stats['pendientes'] ?></td>
            </tr>
            <tr>
                <td><strong>Reservas sin Notificación:</strong></td>
                <td class="warning"><?= $reservas_sin_notif ?></td>
            </tr>
        </table>
    </div>

    <!-- INSTRUCCIONES SANDBOX -->
    <div class="card">
        <h2>📱 ¿No recibes mensajes?</h2>
        <p class="info"><strong>El sandbox de Twilio requiere que tu número esté conectado primero.</strong></p>
        
        <h3>Pasos para conectar tu número:</h3>
        <ol>
            <li>Abre WhatsApp en tu teléfono</li>
            <li>Envía un mensaje a: <strong class="info">+1 415 523 8886</strong></li>
            <li>El mensaje debe ser exactamente: <code style="background:#f0f0f0;padding:5px;">join tall-everybody</code></li>
            <li>Espera la confirmación de Twilio (llega en segundos)</li>
            <li>Una vez confirmado, los mensajes te llegarán automáticamente</li>
        </ol>
        
        <p class="warning">⚠️ <strong>Importante:</strong> Sin conectar tu número al sandbox, los mensajes se envían pero NO te llegan.</p>
    </div>

    <!-- ACCIONES -->
    <div class="card">
        <h2>🛠️ Acciones Disponibles</h2>
        <a href="test_envio_manual.php" class="button">📤 Enviar Notificaciones Pendientes</a>
        <a href="mesas.php" class="button">🪑 Hacer Nueva Reserva</a>
        <a href="perfil_cliente.php" class="button">👤 Ver Mis Reservas</a>
    </div>

    <div style="text-align:center;margin-top:30px;color:#888;">
        <p>Le Salon de Lumière - Sistema de Notificaciones WhatsApp</p>
    </div>
</body>
</html>
