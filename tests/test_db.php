<?php
// Test de conexión para el Sistema de Reservas de Restaurante
include_once 'conexion/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Base de Datos - Restaurante</title>
    <link href="public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #fff8dc 0%, #f5f2ed 100%); min-height: 100vh;">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card shadow-lg border-0" style="border-radius: 15px;">
                <div class="card-header text-white text-center py-4" style="background: linear-gradient(135deg, #2d1b12 0%, #4a3529 100%); border-radius: 15px 15px 0 0;">
                    <h2 class="mb-0">
                        <i class="bi bi-database-check me-2"></i>
                        Test de Conexión - Restaurante Elegante
                    </h2>
                </div>
                
                <div class="card-body p-4">
                    <?php
                    try {
                        echo '<div class="alert alert-success border-0 shadow-sm" role="alert">';
                        echo '<h4 class="alert-heading"><i class="bi bi-check-circle-fill me-2"></i>Conexión Exitosa</h4>';
                        echo '<hr>';
                        echo '<p class="mb-1"><strong>📊 Base de datos:</strong> ' . htmlspecialchars($dbname) . '</p>';
                        echo '<p class="mb-1"><strong>🌐 Host:</strong> ' . htmlspecialchars($host) . '</p>';
                        echo '<p class="mb-0"><strong>👤 Usuario:</strong> ' . htmlspecialchars($username) . '</p>';
                        echo '</div>';
                        
                        // Versión de MySQL
                        $stmt = $pdo->query("SELECT VERSION() as version");
                        $version = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo '<div class="alert alert-info border-0 shadow-sm mb-4">';
                        echo '<p class="mb-0"><i class="bi bi-gear-fill me-2"></i><strong>Versión de MySQL:</strong> ' . htmlspecialchars($version['version']) . '</p>';
                        echo '</div>';
                        
                        // Mostrar estadísticas generales
                        echo '<div class="row mb-4">';
                        $stats = [
                            ['tabla' => 'mesas', 'icon' => 'table', 'color' => 'primary'],
                            ['tabla' => 'categorias_platos', 'icon' => 'tags', 'color' => 'success'],
                            ['tabla' => 'platos', 'icon' => 'egg-fried', 'color' => 'warning'],
                            ['tabla' => 'administradores', 'icon' => 'person-gear', 'color' => 'danger']
                        ];
                        
                        foreach ($stats as $stat) {
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$stat['tabla']}");
                            $count = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo '<div class="col-md-3 mb-3">';
                            echo '<div class="card border-0 shadow-sm h-100">';
                            echo '<div class="card-body text-center">';
                            echo '<i class="bi bi-' . $stat['icon'] . ' fs-1 text-' . $stat['color'] . ' mb-2"></i>';
                            echo '<h3 class="text-' . $stat['color'] . '">' . $count['count'] . '</h3>';
                            echo '<p class="text-muted mb-0">' . ucwords(str_replace('_', ' ', $stat['tabla'])) . '</p>';
                            echo '</div></div></div>';
                        }
                        echo '</div>';
                        
                        // Mostrar mesas disponibles
                        echo '<div class="card border-0 shadow-sm mb-4">';
                        echo '<div class="card-header bg-light">';
                        echo '<h5 class="mb-0"><i class="bi bi-table me-2"></i>Mesas Disponibles</h5>';
                        echo '</div>';
                        echo '<div class="card-body">';
                        
                        $stmt = $pdo->query("SELECT numero_mesa, capacidad, ubicacion, estado FROM mesas ORDER BY ubicacion, numero_mesa");
                        $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $ubicaciones = ['interior' => 'Interior', 'terraza' => 'Terraza', 'vip' => 'VIP', 'bar' => 'Bar'];
                        
                        foreach ($ubicaciones as $ubi => $nombre) {
                            $mesas_filtradas = array_filter($mesas, function($mesa) use ($ubi) {
                                return $mesa['ubicacion'] === $ubi;
                            });
                            
                            if (!empty($mesas_filtradas)) {
                                echo '<h6 class="text-muted mb-2">' . $nombre . '</h6>';
                                echo '<div class="row mb-3">';
                                foreach ($mesas_filtradas as $mesa) {
                                    $color = $mesa['estado'] === 'disponible' ? 'success' : 'secondary';
                                    echo '<div class="col-md-2 col-sm-3 col-4 mb-2">';
                                    echo '<div class="card border-' . $color . ' text-center">';
                                    echo '<div class="card-body py-2">';
                                    echo '<small class="fw-bold">' . $mesa['numero_mesa'] . '</small><br>';
                                    echo '<small><i class="bi bi-people"></i> ' . $mesa['capacidad'] . '</small>';
                                    echo '</div></div></div>';
                                }
                                echo '</div>';
                            }
                        }
                        echo '</div></div>';
                        
                        // Mostrar platos por categoría
                        echo '<div class="card border-0 shadow-sm mb-4">';
                        echo '<div class="card-header bg-light">';
                        echo '<h5 class="mb-0"><i class="bi bi-book me-2"></i>Menú Disponible</h5>';
                        echo '</div>';
                        echo '<div class="card-body">';
                        
                        $stmt = $pdo->query("
                            SELECT 
                                c.nombre as categoria,
                                p.nombre as plato,
                                p.precio,
                                p.stock_disponible as stock
                            FROM platos p 
                            JOIN categorias_platos c ON p.categoria_id = c.id 
                            ORDER BY c.orden_menu, p.precio DESC
                        ");
                        $menu = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $categoria_actual = '';
                        foreach ($menu as $item) {
                            if ($categoria_actual !== $item['categoria']) {
                                if ($categoria_actual !== '') echo '</div>';
                                $categoria_actual = $item['categoria'];
                                echo '<h6 class="text-muted mb-2 mt-3">' . $item['categoria'] . '</h6>';
                                echo '<div class="row">';
                            }
                            
                            echo '<div class="col-md-6 mb-2">';
                            echo '<div class="d-flex justify-content-between align-items-center border-bottom pb-1">';
                            echo '<div>';
                            echo '<span class="fw-bold">' . htmlspecialchars($item['plato']) . '</span><br>';
                            echo '<small class="text-muted">Stock: ' . $item['stock'] . '</small>';
                            echo '</div>';
                            echo '<span class="text-success fw-bold">$' . number_format($item['precio'], 2) . '</span>';
                            echo '</div></div>';
                        }
                        echo '</div>';
                        echo '</div></div>';
                        
                        // Datos del administrador
                        echo '<div class="card border-0 shadow-sm">';
                        echo '<div class="card-header bg-light">';
                        echo '<h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Usuario Administrador</h5>';
                        echo '</div>';
                        echo '<div class="card-body">';
                        echo '<div class="alert alert-warning border-0">';
                        echo '<p class="mb-1"><strong>Usuario:</strong> admin</p>';
                        echo '<p class="mb-1"><strong>Contraseña:</strong> password</p>';
                        echo '<p class="mb-0"><small class="text-muted">Cambia estas credenciales en producción por seguridad.</small></p>';
                        echo '</div>';
                        echo '</div></div>';
                        
                    } catch (PDOException $e) {
                        echo '<div class="alert alert-danger border-0 shadow-sm" role="alert">';
                        echo '<h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error de Conexión</h4>';
                        echo '<hr>';
                        echo '<p class="mb-0">' . htmlspecialchars($e->getMessage()) . '</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
                
                <div class="card-footer text-center bg-light" style="border-radius: 0 0 15px 15px;">
                    <small class="text-muted">
                        <i class="bi bi-calendar"></i>
                        Sistema de Reservas - Restaurante Elegante | <?php echo date('d/m/Y H:i:s'); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background-color: #f5f5f5;
}

h2, h3, h4 {
    color: #333;
}

table {
    width: 100%;
    background-color: white;
    border: 1px solid #ddd;
}

th {
    background-color: #4CAF50;
    color: white;
    padding: 8px;
    text-align: left;
}

td {
    padding: 8px;
    border-bottom: 1px solid #ddd;
}

ul {
    background-color: white;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

li {
    margin: 5px 0;
}
</style>