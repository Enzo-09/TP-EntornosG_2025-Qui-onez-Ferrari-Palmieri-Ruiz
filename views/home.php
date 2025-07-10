<?php
// views/home.php
?>

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
        <a class="nav-link" href="/">Inicio</a>
        <a class="nav-link" href="#">Servicios</a>
        <a class="nav-link" href="#">Nosotros</a>
        <a class="nav-link" href="#">Contacto</a>
      </nav>

      <hr>

      <div class="btn-group w-100 d-flex justify-content-around mt-3">
        <a href="/login" class="btn btn-light btn-sm">Iniciar</a>
        <a href="/register" class="btn btn-outline-light btn-sm">Registro</a>
      </div>

      <div class="footer mt-4">
        &copy; 2025 Colibrí
      </div>
    </header>

    <div class="content-area">
      <main>
        <h2>Presentación</h2>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam vehicula tincidunt risus, nec hendrerit erat.</p>
        <div class="placeholder-img"></div>
      </main>

      <footer>
        <p>footer</p>
      </footer>
    </div>

  </div>

</body>
</html>
