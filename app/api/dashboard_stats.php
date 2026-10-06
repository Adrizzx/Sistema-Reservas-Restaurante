<?php
session_start();
header('Content-Type: application/json');
require_once '../../conexion/db.php';

// TEMPORAL: Comentar verificación de sesión para debug
// TODO: Descomentar esto después de probar
/*
// DEBUG: Ver estado de la sesión
error_log("SESSION DATA: " . print_r($_SESSION, true));
error_log("Has admin_id: " . (isset($_SESSION['admin_id']) ? 'YES' : 'NO'));

// Verificar que sea admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'error' => 'No autorizado',
        'debug' => [
            'session_id' => session_id(),
            'has_session' => !empty($_SESSION),
            'session_keys' => array_keys($_SESSION)
        ]
    ]);
    exit;
}
*/

try {
    $stats = [];

    // 1. RESERVAS DE LOS ÚLTIMOS 30 DÍAS (AGRUPADAS POR SEMANAS)
    $sqlReservasMes = "SELECT 
        CASE 
            WHEN DATEDIFF(CURDATE(), fecha_reserva) BETWEEN 0 AND 7 THEN 4
            WHEN DATEDIFF(CURDATE(), fecha_reserva) BETWEEN 8 AND 14 THEN 3
            WHEN DATEDIFF(CURDATE(), fecha_reserva) BETWEEN 15 AND 21 THEN 2
            WHEN DATEDIFF(CURDATE(), fecha_reserva) BETWEEN 22 AND 30 THEN 1
        END AS semana_num,
        COUNT(*) as total
        FROM reservas 
        WHERE fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND fecha_reserva <= CURDATE()
        GROUP BY semana_num
        ORDER BY semana_num";
    
    $stmt = $pdo->query($sqlReservasMes);
    $reservasMes = [];
    $semanasData = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['semana_num']) {
            $semanasData[$row['semana_num']] = (int)$row['total'];
        }
    }
    
    // Crear las 4 semanas con etiquetas claras
    for ($i = 1; $i <= 4; $i++) {
        $label = '';
        switch($i) {
            case 1: $label = 'Hace 22-30 días'; break;
            case 2: $label = 'Hace 15-21 días'; break;
            case 3: $label = 'Hace 8-14 días'; break;
            case 4: $label = 'Últimos 7 días'; break;
        }
        $reservasMes[] = [
            'semana' => $label,
            'total' => isset($semanasData[$i]) ? $semanasData[$i] : 0
        ];
    }
    
    $stats['reservasMes'] = $reservasMes;

    // 2. HORARIOS MÁS POPULARES
    $sqlHorarios = "SELECT 
        CASE 
            WHEN HOUR(hora_reserva) BETWEEN 12 AND 13 THEN '12:00-14:00'
            WHEN HOUR(hora_reserva) BETWEEN 14 AND 15 THEN '14:00-16:00'
            WHEN HOUR(hora_reserva) BETWEEN 16 AND 18 THEN '16:00-19:00'
            WHEN HOUR(hora_reserva) BETWEEN 19 AND 20 THEN '19:00-21:00'
            WHEN HOUR(hora_reserva) BETWEEN 21 AND 22 THEN '21:00-23:00'
            ELSE 'Otros'
        END as rango_horario,
        COUNT(*) as total
        FROM reservas 
        WHERE fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY rango_horario
        ORDER BY total DESC";
    
    $stmt = $pdo->query($sqlHorarios);
    $horariosPopulares = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['rango_horario'] != 'Otros') {
            $horariosPopulares[] = [
                'horario' => $row['rango_horario'],
                'total' => (int)$row['total']
            ];
        }
    }
    $stats['horariosPopulares'] = $horariosPopulares;

    // 3. MESAS MÁS RESERVADAS
    $sqlMesas = "SELECT 
        m.numero_mesa,
        COUNT(r.id) as total_reservas
        FROM mesas m
        LEFT JOIN reservas r ON m.id = r.mesa_id
        WHERE r.fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY m.id, m.numero_mesa
        ORDER BY total_reservas DESC
        LIMIT 5";
    
    $stmt = $pdo->query($sqlMesas);
    $mesasPopulares = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mesasPopulares[] = [
            'mesa' => 'Mesa ' . $row['numero_mesa'],
            'total' => (int)$row['total_reservas']
        ];
    }
    $stats['mesasPopulares'] = $mesasPopulares;

    // 4. ESTADO DE RESERVAS
    $sqlEstados = "SELECT 
        estado,
        COUNT(*) as total
        FROM reservas 
        WHERE fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY estado";
    
    $stmt = $pdo->query($sqlEstados);
    $estadosReservas = [];
    $nombresEstados = [
        'pendiente' => 'Pendientes',
        'confirmada' => 'Confirmadas',
        'en_curso' => 'En Curso',
        'finalizada' => 'Finalizadas',
        'cancelada' => 'Canceladas'
    ];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $estadosReservas[] = [
            'estado' => $nombresEstados[$row['estado']] ?? ucfirst($row['estado']),
            'total' => (int)$row['total']
        ];
    }
    $stats['estadosReservas'] = $estadosReservas;

    // 5. ESTADÍSTICAS GENERALES PARA LAS CARDS
    // Total de mesas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM mesas");
    $stats['totalMesas'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Mesas disponibles
    $stmt = $pdo->query("SELECT COUNT(*) as disponibles FROM mesas WHERE estado = 'disponible'");
    $stats['mesasDisponibles'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['disponibles'];
    
    // Mesas ocupadas
    $stmt = $pdo->query("SELECT COUNT(*) as ocupadas FROM mesas WHERE estado IN ('ocupada', 'reservada')");
    $stats['mesasOcupadas'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['ocupadas'];
    
    // Reservas de hoy
    $stmt = $pdo->query("SELECT COUNT(*) as hoy FROM reservas WHERE DATE(fecha_reserva) = CURDATE()");
    $stats['reservasHoy'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['hoy'];
    
    // Reservas pendientes
    $stmt = $pdo->query("SELECT COUNT(*) as pendientes FROM reservas WHERE estado = 'pendiente'");
    $stats['reservasPendientes'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['pendientes'];
    
    // Total de clientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
    $stats['clientesTotal'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de platos activos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM platos WHERE activo = 1");
    $stats['platosActivos'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Porcentaje de ocupación
    $ocupacion = $stats['totalMesas'] > 0 ? ($stats['mesasOcupadas'] / $stats['totalMesas']) * 100 : 0;
    $stats['porcentajeOcupacion'] = round($ocupacion, 1);

    echo json_encode(['success' => true, 'data' => $stats]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error al obtener estadísticas: ' . $e->getMessage()]);
}
?>