
<!-- session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("Location: /PHP/Login/login.php");
    exit;
} -->


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Página con Sidebar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/public/CSS/style.css">
</head>
<body>

  <div class="layout-container">
    
    <header>
      <img src="/public/IMG/img-logo-circular.png" alt="Logo" class="logo">
      <hr>
      <nav class="nav flex-column w-100">
        <a href="/admin/dashboard" class="text-decoration-none text-dark">Panel Admin</a>
        <a href="/admin/aprobaciones" class="text-decoration-none text-dark">Aprobaciones</a>
        <a href="/admin/consultas-soporte" class="text-decoration-none text-dark">Consultas Soporte</a>
        <a href="/admin/aprobaciones" class="text-decoration-none text-dark">Aprobaciones</a>
      </nav>

      <hr>

      <div class="btn-group w-100 d-flex justify-content-around mt-3">
        <a href="/logout" class="btn btn-light btn-sm">Cerrar Sesión</a>
  
      </div>

      <div class="footer mt-4">
        &copy; 2025 Colibrí
      </div>
    </header>

    <div class="content-area">
    <main>
        <h1>Aprobaciones</h1>
        <a href="/admin/aprobaciones-cuenta" class="tarjeta">
            <div>
                <h2>Cuentas Dueños</h2>
                <p>Consultar solicaaitudes de Dueños nuevos y Promociones</p>
            </div>
        </a>

        <a href="/admin/solicitudes-locales" class="tarjeta">
            <div>
                <h2>Nuevos Locales</h2>
                <p>Consultar solicitudes de problemas de usuarios con el fin de responderlas</p>
            </div>
        </a>
        <a href="ruta_destino_2.html" class="tarjeta">
            <div>
                <h2>Promociones</h2>
                <p>Consultar solicitudes de problemas de usuarios con el fin de responderlas</p>
            </div>
        </a>
    </main>

    <footer>
        <p>footer</p>
    </footer>
    </div>

  </div>

</body>
</html>

