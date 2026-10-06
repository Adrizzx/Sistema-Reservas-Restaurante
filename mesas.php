<?php
session_start();

// Verificar si el cliente está autenticado
if (!isset($_SESSION['cliente_id']) || !isset($_SESSION['cliente_authenticated']) || $_SESSION['cliente_authenticated'] !== true) {
    header('Location: index.html');
    exit;
}

require_once 'conexion/db.php';

$cliente_id = $_SESSION['cliente_id'];
$cliente_nombre = $_SESSION['cliente_nombre'] ?? '';
$cliente_apellido = $_SESSION['cliente_apellido'] ?? '';
$cliente_email = $_SESSION['cliente_email'] ?? '';

// Verificar si ya hay una mesa seleccionada en la sesión
$mesa_seleccionada_id = $_SESSION['mesa_seleccionada_id'] ?? null;
$mesa_seleccionada = null;

if ($mesa_seleccionada_id) {
    // Obtener información de la mesa seleccionada
    $stmt = $pdo->prepare("SELECT * FROM mesas WHERE id = ?");
    $stmt->execute([$mesa_seleccionada_id]);
    $mesa_seleccionada = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Le Salon de Lumière - Reservar Mesa</title>
    
    <link rel="stylesheet" href="public/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary-color: #1a1a1a;
            --secondary-color: #0d0d0d;
            --gold-color: #d4af37;
            --gradient-gold: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
            --gradient-dark: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--gradient-dark);
            min-height: 100vh;
        }

        .navbar-custom {
            background: var(--secondary-color) !important;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
            padding: 15px 0;
            border-bottom: 2px solid var(--gold-color);
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--gold-color) !important;
            text-shadow: 0 2px 10px rgba(212, 175, 55, 0.5);
        }

        .hero-section {
            background: var(--gradient-dark);
            color: white;
            padding: 60px 0;
            margin-bottom: 50px;
            border-bottom: 3px solid var(--gold-color);
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 700;
            color: var(--gold-color);
            margin-bottom: 15px;
        }

        .mesa-seleccionada-card {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(212, 175, 55, 0.5);
            border: 3px solid var(--gold-color);
            margin-bottom: 30px;
            animation: pulseGold 2s infinite;
            color: white;
        }

        @keyframes pulseGold {
            0%, 100% { box-shadow: 0 10px 40px rgba(212, 175, 55, 0.5); }
            50% { box-shadow: 0 15px 50px rgba(212, 175, 55, 0.7); }
        }

        .mesa-card-mini {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid rgba(212, 175, 55, 0.3);
            color: white;
        }

        .mesa-card-mini:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(212, 175, 55, 0.6);
            border-color: var(--gold-color);
        }

        .mesa-card-mini.disponible {
            border-left: 5px solid #28a745;
        }

        .mesa-card-mini.ocupada {
            border-left: 5px solid #dc3545;
            opacity: 0.6;
            cursor: not-allowed;
        }

        .mesa-card-mini.reservada {
            border-left: 5px solid #ffc107;
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-gold {
            background: var(--gradient-gold);
            color: var(--primary-color);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.6);
            color: var(--primary-color);
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(212, 175, 55, 0.3);
            color: white;
            border-radius: 10px;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--gold-color);
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
            color: white;
        }

        .form-label {
            color: var(--gold-color);
            font-weight: 600;
        }

        .badge-precio {
            background: var(--gradient-gold);
            color: var(--primary-color);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 1.2rem;
            font-weight: 700;
        }

        /* ===== ESTILOS DEL MENÚ DE PLATOS ===== */
        .plato-card {
            background: linear-gradient(135deg, rgba(26, 26, 26, 0.95) 0%, rgba(13, 13, 13, 0.95) 100%);
            border: 2px solid rgba(212, 175, 55, 0.3);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .plato-card:hover {
            transform: translateY(-5px);
            border-color: var(--gold-color);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
        }

        .plato-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .plato-card h5 {
            color: white;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .plato-card p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .plato-precio {
            color: var(--gold-color);
            font-size: 1.3rem;
            font-weight: 700;
        }

        .plato-stock {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .plato-stock.bajo {
            background: rgba(220, 53, 69, 0.8);
        }

        .categoria-header {
            background: var(--gradient-gold);
            color: var(--primary-color);
            padding: 15px 25px;
            border-radius: 10px;
            margin: 30px 0 20px 0;
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* ===== CARRITO LATERAL ===== */
        .carrito-sidebar {
            position: sticky;
            top: 20px;
            background: linear-gradient(135deg, rgba(26, 26, 26, 0.98) 0%, rgba(13, 13, 13, 0.98) 100%);
            border: 2px solid var(--gold-color);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .carrito-header {
            background: var(--gradient-gold);
            color: var(--primary-color);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .carrito-header h4 {
            margin: 0;
            font-weight: 700;
        }

        .carrito-body {
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
        }

        .carrito-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .carrito-item-nombre {
            color: white;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .carrito-item-precio {
            color: var(--gold-color);
            font-size: 1.1rem;
            font-weight: 700;
        }

        .carrito-item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .carrito-item-controls button {
            background: rgba(212, 175, 55, 0.2);
            border: 1px solid var(--gold-color);
            color: var(--gold-color);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .carrito-item-controls button:hover {
            background: var(--gold-color);
            color: var(--primary-color);
        }

        .carrito-item-controls input {
            width: 50px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 5px;
            padding: 5px;
        }

        .carrito-footer {
            padding: 20px;
            border-top: 2px solid rgba(212, 175, 55, 0.3);
        }

        /* Scrollbar personalizado */
        .carrito-body::-webkit-scrollbar {
            width: 8px;
        }

        .carrito-body::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .carrito-body::-webkit-scrollbar-thumb {
            background: var(--gold-color);
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <i class="bi bi-cup-hot-fill me-2"></i>
                Le Salon de Lumière
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($cliente_nombre . ' ' . $cliente_apellido); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="perfil_cliente.php">
                            <i class="bi bi-person-circle"></i> Mi Perfil
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="cerrarSesion()">
                            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="hero-title">
                <i class="bi bi-calendar-heart me-3"></i>
                Reserva tu Mesa
            </h1>
            <p class="lead" style="color: rgba(255,255,255,0.8);">
                Selecciona tu espacio perfecto en nuestro elegante restaurante
            </p>
            <div class="mt-4">
                <button onclick="mostrarReservaZona()" class="btn btn-lg" 
                        style="background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%); 
                               color: #1a1a1a; font-weight: 700; padding: 15px 40px; 
                               border-radius: 50px; border: none; box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);">
                    <i class="bi bi-building me-2"></i>
                    🎉 Reservar Zona Completa
                </button>
                <p class="mt-2" style="color: rgba(212, 175, 55, 0.9); font-size: 0.9rem;">
                    <i class="bi bi-star-fill"></i> ¿Evento especial? Reserva salas completas desde $60
                </p>
            </div>
        </div>
    </section>

    <div class="container pb-5">
        <?php if ($mesa_seleccionada): ?>
        <!-- Mesa Ya Seleccionada -->
        <div class="mesa-seleccionada-card">
            <div class="text-center mb-4">
                <h2 style="color: var(--gold-color);">
                    <i class="bi bi-check-circle-fill"></i> Mesa Seleccionada
                </h2>
                <p style="color: rgba(255,255,255,0.7);">Esta es tu mesa reservada. Puedes cambiarla si lo deseas.</p>
            </div>
            
            <div class="row align-items-center">
                <div class="col-md-4 text-center mb-3 mb-md-0">
                    <div style="font-size: 5rem; color: var(--gold-color);">
                        <?php 
                        $iconos = ['interior' => '🏛️', 'terraza' => '🌿', 'vip' => '👑', 'bar' => '🍸'];
                        echo $iconos[$mesa_seleccionada['ubicacion']] ?? '🍽️';
                        ?>
                    </div>
                    <h3 style="color: white; font-weight: 700;">
                        Mesa <?php echo htmlspecialchars($mesa_seleccionada['numero_mesa']); ?>
                    </h3>
                </div>
                
                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 rounded" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(212, 175, 55, 0.3);">
                                <i class="bi bi-geo-alt-fill" style="color: var(--gold-color);"></i>
                                <strong style="color: white;">Ubicación:</strong><br>
                                <span style="color: rgba(255,255,255,0.8);"><?php echo ucfirst($mesa_seleccionada['ubicacion']); ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(212, 175, 55, 0.3);">
                                <i class="bi bi-people-fill" style="color: var(--gold-color);"></i>
                                <strong style="color: white;">Capacidad:</strong><br>
                                <span style="color: rgba(255,255,255,0.8);"><?php echo $mesa_seleccionada['capacidad_minima']; ?>-<?php echo $mesa_seleccionada['capacidad_maxima']; ?> personas</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 text-center" style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.2) 0%, rgba(212, 175, 55, 0.1) 100%); border-radius: 15px; border: 2px solid var(--gold-color);">
                                <div style="font-size: 0.9rem; color: var(--gold-color); margin-bottom: 5px; font-weight: 600;">Precio de Reserva</div>
                                <span class="badge-precio">$<?php echo number_format($mesa_seleccionada['precio_reserva'], 2); ?></span>
                                <div style="font-size: 0.8rem; color: rgba(255,255,255,0.6); margin-top: 5px;">+ Consumo de platos</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <button class="btn me-2" onclick="cambiarMesa()" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="bi bi-arrow-repeat"></i> Cambiar Mesa
                        </button>
                        <button class="btn btn-gold" onclick="confirmarReserva()">
                            <i class="bi bi-calendar-check"></i> Confirmar Reserva
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Seleccionar Mesa -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%); border: 2px solid rgba(212, 175, 55, 0.3) !important;">
                    <div class="card-header text-white text-center" style="background: var(--gradient-gold); padding: 20px; border: none;">
                        <h4 class="mb-0" style="color: var(--primary-color);">
                            <i class="bi bi-diagram-3-fill me-2"></i>
                            Selecciona tu Mesa Disponible
                        </h4>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Leyenda -->
                        <div class="text-center mb-4">
                            <span class="badge bg-success me-2">
                                <i class="bi bi-check-circle"></i> Disponible
                            </span>
                            <span class="badge bg-danger me-2">
                                <i class="bi bi-x-circle"></i> Ocupada
                            </span>
                            <span class="badge bg-warning">
                                <i class="bi bi-calendar-check"></i> Reservada
                            </span>
                        </div>
                        
                        <!-- Grid de Mesas -->
                        <div class="row g-3" id="mesasContainer">
                            <div class="col-12 text-center py-5">
                                <div class="spinner-border" style="color: var(--gold-color);" role="status"></div>
                                <p class="mt-3" style="color: rgba(255,255,255,0.7);">Cargando mesas disponibles...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- SECCIÓN DE MENÚ DE PLATOS (Estilo KFC) -->
        <!-- ========================================== -->
        <?php if ($mesa_seleccionada): ?>
        <div class="menu-section mt-5" id="menuSection">
            <div class="text-center mb-5">
                <h2 style="color: var(--gold-color); font-family: 'Playfair Display', serif; font-size: 2.5rem;">
                    <i class="bi bi-book-fill me-3"></i>
                    Nuestro Menú Gourmet
                </h2>
                <p style="color: rgba(255,255,255,0.7); font-size: 1.1rem;">
                    Selecciona tus platos favoritos y personaliza tu experiencia
                </p>
            </div>

            <div class="row">
                <!-- PANEL IZQUIERDO: Categorías y Platos -->
                <div class="col-lg-8 mb-4">
                    <!-- Filtro de Categorías -->
                    <div class="mb-4">
                        <div class="d-flex flex-wrap gap-2 justify-content-center" id="categoriasFilter">
                            <button class="btn btn-gold active" onclick="filtrarPorCategoria(null)">
                                <i class="bi bi-grid-fill"></i> Todos
                            </button>
                            <!-- Se llenarán dinámicamente -->
                        </div>
                    </div>

                    <!-- Contenedor de Platos -->
                    <div id="platosContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border" style="color: var(--gold-color);" role="status"></div>
                            <p class="mt-3" style="color: rgba(255,255,255,0.7);">Cargando menú...</p>
                        </div>
                    </div>
                </div>

                <!-- PANEL DERECHO: Carrito de Compras -->
                <div class="col-lg-4">
                    <div class="carrito-sidebar" id="carritoSidebar">
                        <div class="carrito-header">
                            <h4>
                                <i class="bi bi-cart-fill me-2"></i>
                                Tu Pedido
                            </h4>
                            <span class="badge bg-danger" id="carritoCount">0</span>
                        </div>

                        <div class="carrito-body" id="carritoItems">
                            <div class="text-center py-5">
                                <i class="bi bi-cart-x" style="font-size: 3rem; color: rgba(255,255,255,0.3);"></i>
                                <p style="color: rgba(255,255,255,0.5); margin-top: 15px;">
                                    Tu carrito está vacío
                                </p>
                            </div>
                        </div>

                        <div class="carrito-footer">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span style="color: rgba(255,255,255,0.7);">Subtotal:</span>
                                    <strong style="color: white;" id="carritoSubtotal">$0.00</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span style="color: rgba(255,255,255,0.7);">IVA (12%):</span>
                                    <strong style="color: white;" id="carritoIVA">$0.00</strong>
                                </div>
                                <hr style="border-color: rgba(255,255,255,0.2);">
                                <div class="d-flex justify-content-between">
                                    <strong style="color: var(--gold-color); font-size: 1.2rem;">TOTAL:</strong>
                                    <strong style="color: var(--gold-color); font-size: 1.2rem;" id="carritoTotal">$0.00</strong>
                                </div>
                            </div>

                            <button class="btn btn-gold w-100" id="btnConfirmarPedido" onclick="confirmarPedidoCompleto()" disabled>
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Confirmar Pedido
                            </button>
                            
                            <button class="btn w-100 mt-2" onclick="vaciarCarrito()" style="background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid #dc3545;">
                                <i class="bi bi-trash-fill me-2"></i>
                                Vaciar Carrito
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Cargar mesas disponibles
        async function cargarMesas() {
            try {
                const response = await fetch('app/api/mesas_estado.php');
                const data = await response.json();
                
                if (data.success) {
                    mostrarMesas(data.mesas);
                } else {
                    console.error('Error:', data.message);
                }
            } catch (error) {
                console.error('Error cargando mesas:', error);
            }
        }

        function mostrarMesas(mesas) {
            const container = document.getElementById('mesasContainer');
            if (!container) return;
            
            container.innerHTML = '';
            
            mesas.forEach(mesa => {
                const disponible = mesa.estado === 'disponible';
                const iconos = {
                    'interior': '🏛️',
                    'terraza': '🌿',
                    'vip': '👑',
                    'bar': '🍸'
                };
                
                const col = document.createElement('div');
                col.className = 'col-md-4 col-lg-3';
                col.innerHTML = `
                    <div class="mesa-card-mini ${mesa.estado}" onclick="${disponible ? `seleccionarMesa(${mesa.id})` : ''}">
                        <div class="text-center mb-2">
                            <div style="font-size: 2.5rem;">${iconos[mesa.ubicacion] || '🍽️'}</div>
                            <h5 style="color: white; font-weight: 700; margin: 10px 0;">
                                Mesa ${mesa.numero}
                            </h5>
                        </div>
                        
                        <div class="mb-2">
                            <small style="color: rgba(255,255,255,0.7);"><i class="bi bi-geo-alt"></i> ${mesa.ubicacion}</small><br>
                            <small style="color: rgba(255,255,255,0.7);"><i class="bi bi-people"></i> ${mesa.capacidad_minima}-${mesa.capacidad_maxima} personas</small>
                        </div>
                        
                        <div class="text-center mb-2">
                            <span class="badge ${disponible ? 'bg-success' : 'bg-danger'}">
                                ${disponible ? 'Disponible' : mesa.estado.charAt(0).toUpperCase() + mesa.estado.slice(1)}
                            </span>
                        </div>
                        
                        ${disponible ? `
                        <div class="text-center p-2" style="background: rgba(212, 175, 55, 0.2); border-radius: 10px; border: 1px solid var(--gold-color);">
                            <strong style="color: var(--gold-color); font-size: 1.2rem;">$${parseFloat(mesa.precio_reserva || 5).toFixed(2)}</strong>
                        </div>
                        ` : ''}
                    </div>
                `;
                
                container.appendChild(col);
            });
        }

        async function seleccionarMesa(mesaId) {
            const result = await Swal.fire({
                title: '¿Confirmar selección?',
                text: 'Esta mesa quedará guardada para tu reserva',
                icon: 'question',
                background: '#1a1a1a',
                color: '#ffffff',
                showCancelButton: true,
                confirmButtonText: 'Sí, seleccionar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d4af37',
                cancelButtonColor: '#666'
            });
            
            if (result.isConfirmed) {
                try {
                    const response = await fetch('app/api/seleccionar_mesa.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ mesa_id: mesaId })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Mesa Seleccionada!',
                            text: 'Tu mesa ha sido guardada exitosamente',
                            background: '#1a1a1a',
                            color: '#ffffff',
                            confirmButtonColor: '#d4af37'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            background: '#1a1a1a',
                            color: '#ffffff',
                            confirmButtonColor: '#d4af37'
                        });
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo seleccionar la mesa',
                        background: '#1a1a1a',
                        color: '#ffffff',
                        confirmButtonColor: '#d4af37'
                    });
                }
            }
        }

        function cambiarMesa() {
            Swal.fire({
                title: '¿Cambiar mesa?',
                text: 'Se deseleccionará tu mesa actual',
                icon: 'question',
                background: '#1a1a1a',
                color: '#ffffff',
                showCancelButton: true,
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d4af37',
                cancelButtonColor: '#666'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('app/api/deseleccionar_mesa.php', {
                        method: 'POST'
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        }

        async function confirmarReserva() {
            // Primero obtener el horario disponible
            let horarioDisponible = null;
            
            try {
                const fechaHoy = new Date().toISOString().split('T')[0];
                const horaActual = new Date().toTimeString().slice(0,5);
                
                const validacionResponse = await fetch('app/api/validar_horario_reserva.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        fecha: fechaHoy,
                        hora: horaActual
                    })
                });
                
                const validacionData = await validacionResponse.json();
                if (validacionData.success && validacionData.horario_disponible) {
                    horarioDisponible = validacionData.horario_disponible;
                }
            } catch (error) {
                console.error('Error al obtener horarios:', error);
            }
            
            const minHora = horarioDisponible ? horarioDisponible.inicio : '10:00';
            const maxHora = horarioDisponible ? horarioDisponible.fin : '22:00';
            const tipoDia = horarioDisponible ? horarioDisponible.tipo_dia : '';
            
            // Recuperar datos guardados si existen
            let datosGuardados = null;
            const datosTemp = sessionStorage.getItem('reserva_temporal');
            if (datosTemp) {
                try {
                    datosGuardados = JSON.parse(datosTemp);
                } catch (e) {
                    console.error('Error al recuperar datos:', e);
                }
            }
            
            Swal.fire({
                title: 'Confirmar Reserva',
                background: '#1a1a1a',
                color: '#ffffff',
                html: `
                    <style>
                        .swal2-popup {
                            background: #1a1a1a !important;
                        }
                        .reserva-form-label {
                            color: #d4af37 !important;
                            font-weight: 600;
                            display: block;
                            text-align: left;
                            margin-bottom: 8px;
                            margin-top: 15px;
                        }
                        .reserva-form-control {
                            background: rgba(255, 255, 255, 0.95) !important;
                            border: 2px solid #d4af37 !important;
                            color: #1a1a1a !important;
                            border-radius: 10px;
                            padding: 12px;
                            width: 100%;
                            font-size: 1rem;
                            font-weight: 500;
                        }
                        .reserva-form-control:focus {
                            background: white !important;
                            border-color: #d4af37 !important;
                            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.3);
                            outline: none;
                        }
                    </style>
                    ${tipoDia ? `<p style="color: var(--gold-color); margin-bottom: 15px;">📅 <strong>${tipoDia}</strong>: Horario de ${minHora} a ${maxHora}</p>` : ''}
                    <div style="margin-top: 20px;">
                        <label class="reserva-form-label">Fecha de Reserva</label>
                        <input type="date" id="fecha_reserva" class="reserva-form-control" 
                            min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div style="margin-top: 20px;">
                        <label class="reserva-form-label">Hora de Reserva</label>
                        <input type="time" id="hora_reserva" class="reserva-form-control" 
                            min="${minHora}" max="${maxHora}" required>
                    </div>
                    <div style="margin-top: 20px;">
                        <label class="reserva-form-label">Número de Personas</label>
                        <input type="number" id="num_personas" class="reserva-form-control" 
                            min="<?php echo $mesa_seleccionada['capacidad_minima'] ?? 1; ?>" 
                            max="<?php echo $mesa_seleccionada['capacidad_maxima'] ?? 10; ?>" 
                            value="<?php echo $mesa_seleccionada['capacidad_minima'] ?? 1; ?>"
                            placeholder="Ingrese cantidad de personas" required>
                    </div>
                    <small style="color: rgba(255,255,255,0.6); display: block; margin-top: 10px;">
                        ⓘ Mesa <?php echo $mesa_seleccionada['numero_mesa']; ?>: de <?php echo $mesa_seleccionada['capacidad_minima']; ?> a <?php echo $mesa_seleccionada['capacidad_maxima']; ?> personas
                    </small>
                `,
                showCancelButton: true,
                confirmButtonText: 'Reservar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d4af37',
                cancelButtonColor: '#666',
                width: '500px',
                didOpen: () => {
                    // Enfocar el primer campo al abrir
                    document.getElementById('fecha_reserva').focus();
                },
                preConfirm: () => {
                    const fecha = document.getElementById('fecha_reserva').value.trim();
                    const hora = document.getElementById('hora_reserva').value.trim();
                    const personas = document.getElementById('num_personas').value.trim();
                    
                    console.log('Validando:', {fecha, hora, personas}); // Debug
                    
                    if (!fecha) {
                        Swal.showValidationMessage('Por favor selecciona una fecha');
                        return false;
                    }
                    
                    if (!hora) {
                        Swal.showValidationMessage('Por favor selecciona una hora');
                        return false;
                    }
                    
                    if (!personas) {
                        Swal.showValidationMessage('Por favor ingresa el número de personas');
                        return false;
                    }
                    
                    const min = <?php echo $mesa_seleccionada['capacidad_minima'] ?? 1; ?>;
                    const max = <?php echo $mesa_seleccionada['capacidad_maxima'] ?? 10; ?>;
                    const numPersonas = parseInt(personas);
                    
                    if (isNaN(numPersonas) || numPersonas < min || numPersonas > max) {
                        Swal.showValidationMessage(`El número de personas debe estar entre ${min} y ${max}`);
                        return false;
                    }
                    
                    return { fecha, hora, personas: numPersonas };
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const { fecha, hora, personas } = result.value;
                    
                    try {
                        // VALIDAR HORARIO PRIMERO
                        const validacionResponse = await fetch('app/api/validar_horario_reserva.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ fecha: fecha, hora: hora })
                        });
                        
                        const validacionData = await validacionResponse.json();
                        
                        if (!validacionData.valido) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Horario no permitido',
                                text: validacionData.message,
                                background: '#1a1a1a',
                                color: '#ffffff',
                                confirmButtonColor: '#d4af37'
                            });
                            return;
                        }
                        
                        // Si la validación pasa, GUARDAR datos y mostrar opciones (NO crear reserva aún)
                        sessionStorage.setItem('reserva_temporal', JSON.stringify({
                            fecha: fecha,
                            hora: hora,
                            personas: personas
                        }));
                        
                        // Mostrar opciones: terminar o agregar platos
                        Swal.fire({
                            icon: 'question',
                            title: '¿Deseas agregar platos a tu reserva?',
                            html: `
                                <p style="color: #ffffff; margin-bottom: 30px;">Puedes terminar tu reserva ahora o agregar platos del menú</p>
                                <div style="display: flex; gap: 15px; justify-content: center;">
                                    <button onclick="terminarReservaSinPlatos()" class="btn btn-gold" style="flex: 1;">
                                        <i class="bi bi-check-circle-fill me-2"></i>Terminar Reserva
                                    </button>
                                    <button onclick="reservarPlatos()" class="btn btn-gold" style="flex: 1;">
                                        <i class="bi bi-basket-fill me-2"></i>Agregar Platos
                                    </button>
                                </div>
                            `,
                            background: '#1a1a1a',
                            color: '#ffffff',
                            showConfirmButton: false,
                            showCloseButton: true
                        });
                    } catch (error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo procesar la reserva',
                            background: '#1a1a1a',
                            color: '#ffffff',
                            confirmButtonColor: '#d4af37'
                        });
                    }
                }
            });
        }

        // Terminar reserva SIN platos (crear reserva básica)
        async function terminarReservaSinPlatos() {
            const datosTemp = sessionStorage.getItem('reserva_temporal');
            if (!datosTemp) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se encontraron los datos de la reserva',
                    background: '#1a1a1a',
                    color: '#ffffff',
                    confirmButtonColor: '#d4af37'
                });
                return;
            }
            
            const datos = JSON.parse(datosTemp);
            
            try {
                const response = await fetch('app/crear_reserva_admin.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        cliente_id: <?php echo $cliente_id; ?>,
                        mesa_id: <?php echo $mesa_seleccionada['id'] ?? 0; ?>,
                        fecha_reserva: datos.fecha,
                        hora_reserva: datos.hora,
                        numero_personas: datos.personas
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Limpiar datos temporales
                    sessionStorage.removeItem('reserva_temporal');
                    
                    // Preparar datos para WhatsApp
                    const datosWhatsApp = {
                        clienteNombre: '<?php echo $cliente_nombre . " " . $cliente_apellido; ?>',
                        clienteTelefono: '<?php echo $_SESSION["cliente_telefono"] ?? ""; ?>',
                        numeroMesa: '<?php echo $mesa_seleccionada["numero_mesa"] ?? ""; ?>',
                        fechaReserva: datos.fecha,
                        horaReserva: datos.hora,
                        numeroPersonas: datos.personas,
                        numeroNota: 'NC-' + new Date().toISOString().split('T')[0].replace(/-/g, '') + '-000000',
                        platosIncluidos: [],
                        precioMesa: <?php echo $mesa_seleccionada['precio_reserva'] ?? 0; ?>,
                        subtotalPlatos: 0,
                        impuesto: 0,
                        total: <?php echo $mesa_seleccionada['precio_reserva'] ?? 0; ?>,
                        tienePlatos: false
                    };
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Reserva Confirmada!',
                        text: 'Tu reserva ha sido creada exitosamente',
                        background: '#1a1a1a',
                        color: '#ffffff',
                        confirmButtonColor: '#d4af37',
                        confirmButtonText: 'Ver Mi Perfil'
                    }).then(() => {
                        window.location.href = 'perfil_cliente.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudo crear la reserva',
                        background: '#1a1a1a',
                        color: '#ffffff',
                        confirmButtonColor: '#d4af37'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo procesar la reserva',
                    background: '#1a1a1a',
                    color: '#ffffff',
                    confirmButtonColor: '#d4af37'
                });
            }
        }

        function reservarPlatos() {
            // Cerrar el modal y mostrar el menú de platos
            Swal.close();
            
            // Scroll suave al menú de platos
            document.getElementById('menuSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Mostrar mensaje informativo
            Swal.fire({
                icon: 'info',
                title: 'Selecciona tus platos',
                text: 'Agrega los platos que desees al carrito y confirma tu pedido',
                background: '#1a1a1a',
                color: '#ffffff',
                confirmButtonColor: '#d4af37',
                timer: 3000,
                timerProgressBar: true,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        }

        function cerrarSesion() {
            window.location.href = 'app/logout.php';
        }

        // Cargar mesas al iniciar
        <?php if (!$mesa_seleccionada): ?>
        document.addEventListener('DOMContentLoaded', cargarMesas);
        <?php endif; ?>

        // ========================================
        // SISTEMA DE MENÚ Y CARRITO DE PLATOS
        // ========================================
        <?php if ($mesa_seleccionada): ?>
        let platosData = [];
        let categoriasData = [];
        let carrito = [];
        let categoriaActiva = null;

        // Cargar menú al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarMenu();
            cargarCarrito();
        });

        // Cargar menú desde API
        async function cargarMenu() {
            try {
                const response = await fetch('app/api/obtener_platos_por_categoria.php');
                const data = await response.json();

                if (data.success) {
                    categoriasData = data.categorias;
                    platosData = data.categorias.flatMap(c => c.platos.map(p => ({ ...p, categoria_id: c.categoria_id, categoria_nombre: c.categoria_nombre })));
                    
                    renderizarCategorias();
                    renderizarPlatos();
                }
            } catch (error) {
                console.error('Error al cargar menú:', error);
                document.getElementById('platosContainer').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Error al cargar el menú
                    </div>
                `;
            }
        }

        // Renderizar botones de categorías
        function renderizarCategorias() {
            const container = document.getElementById('categoriasFilter');
            const botonTodos = container.querySelector('button');
            
            categoriasData.forEach(cat => {
                const btn = document.createElement('button');
                btn.className = 'btn btn-gold';
                btn.style.background = 'rgba(212, 175, 55, 0.2)';
                btn.style.border = '1px solid var(--gold-color)';
                btn.style.color = 'var(--gold-color)';
                btn.innerHTML = `<i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> ${cat.categoria_nombre}`;
                btn.onclick = () => filtrarPorCategoria(cat.categoria_id);
                container.appendChild(btn);
            });
        }

        // Filtrar platos por categoría
        function filtrarPorCategoria(catId) {
            categoriaActiva = catId;
            
            // Actualizar botones activos
            const botones = document.querySelectorAll('#categoriasFilter button');
            botones.forEach((btn, index) => {
                if ((catId === null && index === 0) || (catId !== null && index > 0 && btn.textContent.includes(categoriasData.find(c => c.categoria_id === catId)?.categoria_nombre))) {
                    btn.classList.add('active');
                    btn.style.background = 'var(--gradient-gold)';
                    btn.style.color = 'var(--primary-color)';
                } else {
                    btn.classList.remove('active');
                    btn.style.background = 'rgba(212, 175, 55, 0.2)';
                    btn.style.color = 'var(--gold-color)';
                }
            });

            renderizarPlatos();
        }

        // Renderizar platos
        function renderizarPlatos() {
            const container = document.getElementById('platosContainer');
            container.innerHTML = '';

            const categoriasFiltradas = categoriaActiva === null 
                ? categoriasData 
                : categoriasData.filter(c => c.categoria_id === categoriaActiva);

            categoriasFiltradas.forEach(categoria => {
                // Header de categoría
                const header = document.createElement('div');
                header.className = 'categoria-header';
                header.innerHTML = `
                    <i class="bi bi-bookmark-fill me-2"></i>
                    ${categoria.categoria_nombre}
                `;
                container.appendChild(header);

                // Grid de platos
                const grid = document.createElement('div');
                grid.className = 'row g-3';

                categoria.platos.forEach(plato => {
                    const col = document.createElement('div');
                    col.className = 'col-md-6 col-xl-4';
                    
                    const stockBajo = plato.stock_disponible < 5;
                    const sinStock = plato.stock_disponible === 0;

                    col.innerHTML = `
                        <div class="plato-card ${sinStock ? 'opacity-50' : ''}" onclick="${!sinStock ? `agregarAlCarrito(${plato.id})` : ''}">
                            <span class="plato-stock ${stockBajo ? 'bajo' : ''}">
                                ${sinStock ? 'Sin stock' : `Stock: ${plato.stock_disponible}`}
                            </span>
                            <img src="${plato.imagen_url}" alt="${plato.nombre}" onerror="this.src='https://via.placeholder.com/300x180?text=Sin+Imagen'">
                            <h5>${plato.nombre}</h5>
                            <p>${plato.descripcion || 'Delicioso plato preparado con ingredientes frescos'}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="plato-precio">$${parseFloat(plato.precio).toFixed(2)}</span>
                                <small style="color: rgba(255,255,255,0.5);">
                                    <i class="bi bi-clock"></i> ${plato.tiempo_preparacion} min
                                </small>
                            </div>
                            ${!sinStock ? `
                                <button class="btn btn-gold w-100 mt-3" onclick="event.stopPropagation(); agregarAlCarrito(${plato.id})">
                                    <i class="bi bi-plus-circle"></i> Agregar
                                </button>
                            ` : `
                                <button class="btn w-100 mt-3" disabled style="background: rgba(220, 53, 69, 0.3); color: #dc3545; border: 1px solid #dc3545;">
                                    <i class="bi bi-x-circle"></i> No disponible
                                </button>
                            `}
                        </div>
                    `;

                    grid.appendChild(col);
                });

                container.appendChild(grid);
            });
        }

        // Agregar plato al carrito
        async function agregarAlCarrito(platoId) {
            try {
                const response = await fetch('app/api/gestionar_carrito.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add',
                        plato_id: platoId,
                        cantidad: 1
                    })
                });

                const data = await response.json();

                if (data.success) {
                    carrito = data.carrito;
                    actualizarCarritoUI(data.totales);
                    
                    // Feedback visual
                    Swal.fire({
                        icon: 'success',
                        title: '¡Agregado!',
                        text: data.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        background: '#1a1a1a',
                        color: '#ffffff'
                    });
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo agregar el plato',
                    background: '#1a1a1a',
                    color: '#ffffff',
                    confirmButtonColor: '#d4af37'
                });
            }
        }

        // Actualizar UI del carrito
        function actualizarCarritoUI(totales) {
            const itemsContainer = document.getElementById('carritoItems');
            const countBadge = document.getElementById('carritoCount');
            const subtotalEl = document.getElementById('carritoSubtotal');
            const ivaEl = document.getElementById('carritoIVA');
            const totalEl = document.getElementById('carritoTotal');
            const btnConfirmar = document.getElementById('btnConfirmarPedido');

            // Actualizar contador
            countBadge.textContent = totales.items;

            // Actualizar totales
            subtotalEl.textContent = `$${totales.subtotal.toFixed(2)}`;
            ivaEl.textContent = `$${totales.impuesto.toFixed(2)}`;
            totalEl.textContent = `$${totales.total.toFixed(2)}`;

            // Habilitar/deshabilitar botón
            btnConfirmar.disabled = carrito.length === 0;

            // Renderizar items
            if (carrito.length === 0) {
                itemsContainer.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x" style="font-size: 3rem; color: rgba(255,255,255,0.3);"></i>
                        <p style="color: rgba(255,255,255,0.5); margin-top: 15px;">
                            Tu carrito está vacío
                        </p>
                    </div>
                `;
            } else {
                itemsContainer.innerHTML = carrito.map(item => `
                    <div class="carrito-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="carrito-item-nombre">${item.nombre}</span>
                            <button class="btn-close btn-close-white" onclick="eliminarDelCarrito(${item.id})" style="font-size: 0.7rem;"></button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="carrito-item-precio">$${item.subtotal.toFixed(2)}</span>
                            <div class="carrito-item-controls">
                                <button onclick="actualizarCantidad(${item.id}, ${item.cantidad - 1})">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" value="${item.cantidad}" readonly>
                                <button onclick="actualizarCantidad(${item.id}, ${item.cantidad + 1})">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                        </div>
                        <small style="color: rgba(255,255,255,0.5);">
                            $${item.precio.toFixed(2)} c/u
                        </small>
                    </div>
                `).join('');
            }
        }

        // Cargar carrito actual
        async function cargarCarrito() {
            try {
                const response = await fetch('app/api/gestionar_carrito.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get' })
                });

                const data = await response.json();
                if (data.success) {
                    carrito = data.carrito;
                    actualizarCarritoUI(data.totales);
                }
            } catch (error) {
                console.error('Error al cargar carrito:', error);
            }
        }

        // Actualizar cantidad
        async function actualizarCantidad(platoId, nuevaCantidad) {
            try {
                const response = await fetch('app/api/gestionar_carrito.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update',
                        plato_id: platoId,
                        cantidad: nuevaCantidad
                    })
                });

                const data = await response.json();
                if (data.success) {
                    carrito = data.carrito;
                    actualizarCarritoUI(data.totales);
                }
            } catch (error) {
                console.error('Error al actualizar cantidad:', error);
            }
        }

        // Eliminar del carrito
        async function eliminarDelCarrito(platoId) {
            try {
                const response = await fetch('app/api/gestionar_carrito.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'remove',
                        plato_id: platoId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    carrito = data.carrito;
                    actualizarCarritoUI(data.totales);
                }
            } catch (error) {
                console.error('Error al eliminar:', error);
            }
        }

        // Vaciar carrito
        async function vaciarCarrito() {
            const result = await Swal.fire({
                title: '¿Vaciar carrito?',
                text: 'Se eliminarán todos los platos seleccionados',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d4af37',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Sí, vaciar',
                cancelButtonText: 'Cancelar',
                background: '#1a1a1a',
                color: '#ffffff'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch('app/api/gestionar_carrito.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'clear' })
                    });

                    const data = await response.json();
                    if (data.success) {
                        carrito = [];
                        actualizarCarritoUI({ items: 0, subtotal: 0, impuesto: 0, total: 0 });
                    }
                } catch (error) {
                    console.error('Error al vaciar carrito:', error);
                }
            }
        }

        // Confirmar pedido completo
        async function confirmarPedidoCompleto() {
            // Primero obtener el horario disponible para validación
            let horarioDisponible = null;
            
            try {
                const fechaHoy = new Date().toISOString().split('T')[0];
                const horaActual = new Date().toTimeString().slice(0,5);
                
                const validacionResponse = await fetch('app/api/validar_horario_reserva.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        fecha: fechaHoy,
                        hora: horaActual
                    })
                });
                
                const validacionData = await validacionResponse.json();
                if (validacionData.success && validacionData.horario_disponible) {
                    horarioDisponible = validacionData.horario_disponible;
                }
            } catch (error) {
                console.error('Error al obtener horarios:', error);
            }
            
            const minHora = horarioDisponible ? horarioDisponible.inicio : '10:00';
            const maxHora = horarioDisponible ? horarioDisponible.fin : '22:00';
            const tipoDia = horarioDisponible ? horarioDisponible.tipo_dia : '';
            
            // Recuperar datos guardados si existen
            let datosGuardados = null;
            const datosTemp = sessionStorage.getItem('reserva_temporal');
            if (datosTemp) {
                try {
                    datosGuardados = JSON.parse(datosTemp);
                } catch (e) {
                    console.error('Error al recuperar datos:', e);
                }
            }
            
            const { value: formValues } = await Swal.fire({
                title: 'Confirmar Pedido Completo',
                background: '#1a1a1a',
                color: '#ffffff',
                html: `
                    <div style="text-align: left;">
                        ${tipoDia ? `<p style="color: var(--gold-color); margin-bottom: 15px;">📅 <strong>${tipoDia}</strong>: Horario de ${minHora} a ${maxHora}</p>` : ''}
                        
                        <label class="reserva-form-label">Fecha de Reserva:</label>
                        <input type="date" id="fecha" class="swal2-input" min="${new Date().toISOString().split('T')[0]}" value="${datosGuardados?.fecha || new Date().toISOString().split('T')[0]}">
                        
                        <label class="reserva-form-label">Hora de Reserva:</label>
                        <input type="time" id="hora" class="swal2-input" min="${minHora}" max="${maxHora}" value="${datosGuardados?.hora || new Date().toTimeString().slice(0,5)}">
                        
                        <label class="reserva-form-label">Número de Personas:</label>
                        <input type="number" id="personas" class="swal2-input" min="<?php echo $mesa_seleccionada['capacidad_minima']; ?>" max="<?php echo $mesa_seleccionada['capacidad_maxima']; ?>" value="${datosGuardados?.personas || '<?php echo $mesa_seleccionada['capacidad_minima']; ?>'}">
                        
                        <small style="color: rgba(255,255,255,0.6); display: block; margin-top: 10px;">
                            ⓘ Mesa <?php echo $mesa_seleccionada['numero_mesa']; ?>: de <?php echo $mesa_seleccionada['capacidad_minima']; ?> a <?php echo $mesa_seleccionada['capacidad_maxima']; ?> personas
                        </small>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Confirmar Pedido',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d4af37',
                cancelButtonColor: '#666',
                preConfirm: () => {
                    const fecha = document.getElementById('fecha').value;
                    const hora = document.getElementById('hora').value;
                    const personas = parseInt(document.getElementById('personas').value);
                    
                    // Validaciones en el cliente
                    if (!fecha || !hora || !personas) {
                        Swal.showValidationMessage('Por favor complete todos los campos');
                        return false;
                    }
                    
                    if (personas < <?php echo $mesa_seleccionada['capacidad_minima']; ?>) {
                        Swal.showValidationMessage('Mínimo <?php echo $mesa_seleccionada['capacidad_minima']; ?> personas para esta mesa');
                        return false;
                    }
                    
                    if (personas > <?php echo $mesa_seleccionada['capacidad_maxima']; ?>) {
                        Swal.showValidationMessage('Máximo <?php echo $mesa_seleccionada['capacidad_maxima']; ?> personas para esta mesa');
                        return false;
                    }
                    
                    return { fecha, hora, personas };
                }
            });

            if (formValues) {
                try {
                    // VALIDAR HORARIO PRIMERO
                    const validacionResponse = await fetch('app/api/validar_horario_reserva.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            fecha: formValues.fecha, 
                            hora: formValues.hora 
                        })
                    });
                    
                    const validacionData = await validacionResponse.json();
                    
                    if (!validacionData.valido) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Horario no permitido',
                            text: validacionData.message,
                            background: '#1a1a1a',
                            color: '#ffffff',
                            confirmButtonColor: '#d4af37'
                        });
                        return;
                    }
                    
                    // Si la validación pasa, confirmar la reserva con platos
                    const response = await fetch('app/api/confirmar_reserva_con_platos.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            fecha_reserva: formValues.fecha,
                            hora_reserva: formValues.hora,
                            numero_personas: parseInt(formValues.personas)
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Limpiar datos temporales
                        sessionStorage.removeItem('reserva_temporal');
                        
                        await Swal.fire({
                            icon: 'success',
                            title: '¡Reserva Confirmada!',
                            html: `
                                <div style="text-align: left; color: white;">
                                    <p><strong>Nota:</strong> ${data.reserva.numero_nota}</p>
                                    <p><strong>Mesa:</strong> ${data.reserva.mesa}</p>
                                    <p><strong>Fecha:</strong> ${data.reserva.fecha}</p>
                                    <p><strong>Hora:</strong> ${data.reserva.hora}</p>
                                    <hr style="border-color: rgba(212, 175, 55, 0.3);">
                                    <p><strong style="color: var(--gold-color);">Total: $${parseFloat(data.reserva.total).toFixed(2)}</strong></p>
                                    <small style="color: rgba(255,255,255,0.6);">
                                        (Incluye ${data.reserva.platos.length} plato(s) + IVA)
                                    </small>
                                </div>
                            `,
                            background: '#1a1a1a',
                            color: '#ffffff',
                            confirmButtonColor: '#d4af37',
                            confirmButtonText: 'Ver Mis Reservas'
                        });

                        // Redirigir al perfil después de un delay
                        setTimeout(() => {
                            window.location.href = 'perfil_cliente.php';
                        }, 2500);
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'No se pudo confirmar el pedido',
                        background: '#1a1a1a',
                        color: '#ffffff',
                        confirmButtonColor: '#d4af37'
                    });
                }
            }
        }
        <?php endif; ?>
    </script>
    
    <!-- Script de reserva de zonas -->
    <script src="public/js/reserva-zonas.js"></script>
</body>
</html>
