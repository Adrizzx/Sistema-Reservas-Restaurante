<?php
// Página simple de registro / login para clientes
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Registro de Cliente - Restaurante Elegante</title>
    <link rel="stylesheet" href="public/bootstrap/css/bootstrap.min.css">
    <script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body{background:#f7f7f7;padding:30px;font-family:Arial}</style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card mt-4">
                <div class="card-header bg-dark text-white">Registro de Cliente</div>
                <div class="card-body">
                    <form id="registroForm">
                        <div class="row">
                            <div class="col-md-6 mb-3"><input class="form-control" name="nombre" placeholder="Nombre" required></div>
                            <div class="col-md-6 mb-3"><input class="form-control" name="apellido" placeholder="Apellido" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><input class="form-control" name="cedula" placeholder="Cédula" required></div>
                            <div class="col-md-6 mb-3"><input class="form-control" name="telefono" placeholder="Teléfono" required></div>
                        </div>
                        <div class="mb-3"><input class="form-control" name="ciudad" placeholder="Ciudad"></div>
                        <hr>
                        <h6>Credenciales de acceso</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3"><input class="form-control" name="usuario" placeholder="Usuario" required></div>
                            <div class="col-md-6 mb-3"><input type="password" class="form-control" name="password" placeholder="Contraseña" required></div>
                        </div>
                        <div class="d-grid"><button class="btn btn-primary" type="submit">Crear Cuenta y Entrar</button></div>
                    </form>
                    <hr>
                    <p class="text-muted">¿Ya tienes cuenta? <a href="#" id="showLogin">Iniciar sesión</a></p>
                    <div id="loginBox" style="display:none;">
                        <form id="loginForm">
                            <div class="mb-3"><input class="form-control" name="usuario" placeholder="Usuario o email" required></div>
                            <div class="mb-3"><input type="password" class="form-control" name="password" placeholder="Contraseña o teléfono" required></div>
                            <div class="d-grid"><button class="btn btn-success" type="submit">Entrar</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('showLogin').addEventListener('click', function(e){ e.preventDefault(); document.getElementById('loginBox').style.display='block'; window.scrollTo(0,document.body.scrollHeight); });

document.getElementById('registroForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
        const res = await fetch('app/registro_cliente.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Registro exitoso', timer: 1500, showConfirmButton: false });
            // redirigir al next si existe
            const urlParams = new URLSearchParams(location.search);
            const next = urlParams.get('next') || 'mesas.php';
            setTimeout(()=> location.href = next, 1200);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo registrar' });
        }
    } catch(err){
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
    }
});

document.getElementById('loginForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
        const res = await fetch('app/validar_cliente.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Bienvenido', timer: 1000, showConfirmButton: false });
            const urlParams = new URLSearchParams(location.search);
            const next = urlParams.get('next') || 'mesas.php';
            setTimeout(()=> location.href = next, 800);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Credenciales incorrectas' });
        }
    } catch(err){
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor' });
    }
});
</script>

</body>
</html>
