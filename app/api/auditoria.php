<?php
/**
 * API para consultar auditoría del sistema
 */

header('Content-Type: application/json; charset=UTF-8');
session_start();

require_once '../../conexion/db.php';
require_once '../../controllers/AuditoriaController.php';

// Verificar autenticación de administrador
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_authenticated']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit;
}

try {
    $auditoriaController = new AuditoriaController($pdo);
    
    $tipo = $_GET['tipo'] ?? 'horarios'; // horarios, reservas, admin, sistema
    $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 50;
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    switch ($tipo) {
        case 'horarios':
            // Si hay ID, obtener registro específico
            if ($id) {
                $stmt = $pdo->prepare("
                    SELECT 
                        h.id,
                        h.admin_id,
                        CONCAT(u.nombre, ' ', u.apellido) as admin_nombre,
                        h.fecha_cambio,
                        h.configuracion_anterior,
                        h.configuracion_nueva,
                        h.reservas_afectadas,
                        h.reservas_canceladas,
                        h.notificaciones_enviadas,
                        h.observaciones,
                        h.ip_address,
                        h.user_agent
                    FROM auditoria_horarios h
                    LEFT JOIN usuarios u ON h.admin_id = u.id
                    WHERE h.id = ?
                ");
                $stmt->execute([$id]);
                $datos = [$stmt->fetch(PDO::FETCH_ASSOC)];
            } else {
                // Historial de cambios de horarios
                $datos = $auditoriaController->obtenerHistorialHorarios($limite);
            }
            
            // Formatear datos para mostrar
            $resultado = array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'admin' => $item['admin_nombre'],
                    'accion' => $item['accion'] ?? 'Cambio de horarios',
                    'fecha' => date('d/m/Y H:i:s', strtotime($item['fecha_cambio'])),
                    'reservas_afectadas' => $item['reservas_afectadas'],
                    'reservas_canceladas' => $item['reservas_canceladas'],
                    'notificaciones_enviadas' => $item['notificaciones_enviadas'],
                    'configuracion_anterior' => $item['configuracion_anterior'],
                    'configuracion_nueva' => $item['configuracion_nueva'],
                    'observaciones' => $item['observaciones'],
                    'ip' => $item['ip_address']
                ];
            }, $datos);
            
            echo json_encode([
                'success' => true,
                'tipo' => 'horarios',
                'total' => count($resultado),
                'datos' => $resultado
            ]);
            break;
            
        case 'reserva':
            // Historial de una reserva específica
            $reservaId = $_GET['reserva_id'] ?? null;
            if (!$reservaId) {
                throw new Exception('Se requiere reserva_id');
            }
            
            $datos = $auditoriaController->obtenerHistorialReserva($reservaId);
            
            $resultado = array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'admin' => $item['admin_nombre_completo'] ?? 'Sistema',
                    'accion' => $item['accion'],
                    'fecha' => date('d/m/Y H:i:s', strtotime($item['fecha_accion'])),
                    'estado_anterior' => $item['estado_anterior'],
                    'estado_nuevo' => $item['estado_nuevo'],
                    'datos_anteriores' => json_decode($item['datos_anteriores'], true),
                    'datos_nuevos' => json_decode($item['datos_nuevos'], true),
                    'motivo' => $item['motivo'],
                    'ip' => $item['ip_address']
                ];
            }, $datos);
            
            echo json_encode([
                'success' => true,
                'tipo' => 'reserva',
                'reserva_id' => $reservaId,
                'total' => count($resultado),
                'datos' => $resultado
            ]);
            break;
            
        case 'admin':
            // Acciones de un administrador específico
            $adminId = $_GET['admin_id'] ?? $_SESSION['admin_id'];
            $fechaInicio = $_GET['fecha_inicio'] ?? null;
            $fechaFin = $_GET['fecha_fin'] ?? null;
            
            $datos = $auditoriaController->obtenerAccionesAdmin($adminId, $fechaInicio, $fechaFin);
            
            echo json_encode([
                'success' => true,
                'tipo' => 'admin',
                'admin_id' => $adminId,
                'total' => count($datos),
                'datos' => $datos
            ]);
            break;
            
        case 'resumen':
            // Resumen de auditoría
            $stmt = $pdo->query("
                SELECT 
                    (SELECT COUNT(*) FROM auditoria_horarios) as total_cambios_horarios,
                    (SELECT COUNT(*) FROM auditoria_reservas) as total_acciones_reservas,
                    (SELECT COUNT(*) FROM auditoria_horarios WHERE DATE(fecha_cambio) = CURDATE()) as cambios_horarios_hoy,
                    (SELECT COUNT(*) FROM auditoria_reservas WHERE DATE(fecha_accion) = CURDATE()) as acciones_reservas_hoy,
                    (SELECT SUM(reservas_canceladas) FROM auditoria_horarios) as total_reservas_canceladas,
                    (SELECT SUM(notificaciones_enviadas) FROM auditoria_horarios) as total_notificaciones
            ");
            $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Últimos cambios de horarios
            $stmtUltimos = $pdo->query("
                SELECT admin_nombre, fecha_cambio, reservas_afectadas, observaciones
                FROM auditoria_horarios 
                ORDER BY fecha_cambio DESC 
                LIMIT 5
            ");
            $ultimosCambios = $stmtUltimos->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'tipo' => 'resumen',
                'resumen' => $resumen,
                'ultimos_cambios' => $ultimosCambios
            ]);
            break;
            
        default:
            throw new Exception('Tipo de auditoría no válido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
