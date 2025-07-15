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
  <style>
    /* CSS del footer */
    .main-footer {
      background-color: #9b89b3;
      color: white;
      padding: 10px 0;
      margin-top: auto;
      font-size: 12px;
    }
    
    .footer-link {
      color: white !important;
      text-decoration: none;
      font-size: 11px;
      font-weight: 500;
      transition: color 0.3s ease;
      display: inline-block;
    }
    
    .footer-link:hover {
      color: #ddd !important;
      text-decoration: underline;
    }
    
    .footer-social {
      color: white;
      text-decoration: none;
      font-size: 14px;
      transition: transform 0.3s ease;
      display: inline-block;
    }
    
    .footer-social:hover {
      transform: scale(1.1);
      color: white;
    }
    
    /* Ajustes responsive adicionales */
    @media (max-width: 576px) {
      .main-footer {
        font-size: 11px;
      }
      
      .footer-link {
        font-size: 10px;
      }
      
      .footer-social {
        font-size: 12px;
      }
    }
    .content-area p {
      margin-top: 0.3em;
      margin-bottom: 0.3em;
    }

  /* CSS del texto de bienvenida */
    .texto-bienvenida {
      margin-bottom: 2rem;
    }

    .texto-bienvenida h2 {
      color: #9b89b3;
      font-weight: 600;
      margin-bottom: 1rem;
    }

    .texto-bienvenida p {
      color: #555;
      line-height: 1.5s;
    }

    .texto-bienvenida img {
      max-width: 85%; 
      height: auto;
      border-radius: 10px;
    }
    
 
    @media (max-width: 768px) {
      .texto-bienvenida {
        text-align: center;
      }

      .texto-bienvenida .row {
        flex-direction: column-reverse;
      }

      .texto-bienvenida img {
        margin-bottom: 1rem;
      }
    }
    /* CSS del contenido de abajo */
    .contenido-parte-baja h2 {
      color: #9b89b3;
      font-weight: 600;
      margin-bottom: 1rem;
    }
    
    
    .contenido-parte-baja .card {
      border: none;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      margin-bottom: 1rem;
    }
    
    .contenido-parte-baja .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .contenido-parte-baja .card-img-top {
      border-radius: 8px 8px 0 0;
    }
    
    .contenido-parte-baja .card-title {
      font-size: 1.1rem;
      font-weight: 600;
    }
    
    .contenido-parte-baja .card-text {
      color: #666;
      font-size: 0.9rem;
      line-height: 1.5;
    }
    
    .contenido-parte-baja .btn {
      border-radius: 25px;
      padding: 8px 20px;
      font-size: 0.9rem;
      font-weight: 500;
    }
    
    @media (max-width: 768px) {
      .contenido-parte-baja .card-img-top {
        height: 180px !important;
      }
      
      .contenido-parte-baja .card-title {
        font-size: 1rem;
      }
      
      .contenido-parte-baja .card-text {
        font-size: 0.85rem;
      }
    }
  </style>
</head>
<body>

  <div class="layout-container">

    <header>
      <img src="/public/IMG/img-logo-circular.png" alt="Logo" class="logo">
      <hr>
      <nav class="nav flex-column w-100">
        <a class="nav-link" href="/">Inicio</a>
        <a class="nav-link" href="/promociones">Promociones</a>
        <a class="nav-link" href="/locales">Locales</a>
        <a class="nav-link" href="/nosotros">Nosotros</a>
        <a class="nav-link" href="/contacto">Contacto</a>
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
    <!-- Sección de bienvenida -->
    <div class="texto-bienvenida">
      <main>
        <div class="container-fluid texto-bienvenida">
          <div class="row align-items-center">
            <div class="col-md-6 col-12">
              <h2>Bienvenidos al centro comercial colibri</h2>
              <p>descubre un mundo de opciones en el centro comercial colibri, Aqui encontraras tus tiendas favoritas, una variada oferta gastronomica, los ultimos estrenos de cine y eventos para toda la familia. Diseñada para tu comodidad y entretenimiento,Colibri es el lugar perfecto para tus compras, ocio y momentos especiales ¡Te esperamos!</p>
            </div>
            
            
            <div class="col-md-6 col-12 text-center">
              <img src="public/IMG/ChatGPT Image 15 jul 2025, 12_27_58 p.m..png" alt="Centro Comercial Colibrí" class="img-fluid rounded shadow">
            </div>
          </div>
        </div>
          <!-- Sección de novedades y destacados -->
        <div class="container-fluid contenido-parte-baja">
          <div class="row">
            <div class="col-12">
              <h2 class="text-center mb-4">Novedades y Destacados</h2>
            </div>
          </div>
          
          <div class="row g-4">
            <!-- Sección de Evento -->
            <div class="col-md-4 col-12">
              <div class="card h-100 shadow-sm">
                <img src="public/IMG/ChatGPT Image 15 jul 2025, 12_00_32 p.m..png" class="card-img-top" alt="Evento Especial" style="height: 200px; object-fit: cover;">
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title text-primary">🎉 Evento Especial</h5>
                  <p class="card-text flex-grow-1">Gran inauguración de la nueva zona gastronómica. Ven y disfruta de degustaciones gratuitas, música en vivo y sorpresas para toda la familia.</p>
                  <a href="/eventos" class="btn btn-primary mt-auto">Ver Evento</a>
                </div>
              </div>
            </div>
            
            <!-- Sección de Descuento -->
            <div class="col-md-4 col-12">
              <div class="card h-100 shadow-sm">
                <img src="public/IMG/ChatGPT Image 15 jul 2025, 12_19_17 p.m..png" class="card-img-top" alt="Descuento Especial" style="height: 200px; object-fit: cover;">
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title text-success">💰 Descuento Especial</h5>
                  <p class="card-text flex-grow-1">¡Hasta 50% de descuento en ropa de temporada! Aprovecha esta increíble oportunidad en tus tiendas favoritas de moda.</p>
                  <a href="/promociones" class="btn btn-success mt-auto">Ver Promociones</a>
                </div>
              </div>
            </div>
            
            <!-- Sección de Tienda Destacada -->
            <div class="col-md-4 col-12">
              <div class="card h-100 shadow-sm">
                <img src="public/IMG/ChatGPT Image 15 jul 2025, 12_14_16 p.m..png" class="card-img-top" alt="Tienda Destacada" style="height: 200px; object-fit: cover;">
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title text-warning">⭐ Tienda Destacada</h5>
                  <p class="card-text flex-grow-1">Descubre la nueva tienda de tecnología con los últimos dispositivos y accesorios. Encuentra todo lo que necesitas en un solo lugar.</p>
                  <a href="/locales" class="btn btn-warning mt-auto">Ver Tiendas</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>

    <footer class="main-footer">
      <div class="container-fluid">
        <div class="row align-items-center py-2">
          <!-- Enlaces de navegación -->
          <div class="col-md-4 col-12 mb-2 mb-md-0">
            <div class="d-flex flex-column gap-1">
              <a href="/" class="footer-link">INICIO</a>
              <a href="/promociones" class="footer-link">PROMOCIONES</a>
              <a href="/locales" class="footer-link">LOCALES</a>
              <a href="/nosotros" class="footer-link">NOSOTROS</a>
              <a href="/contacto" class="footer-link">CONTACTO</a>
            </div>
          </div>
          
          <!-- Derechos reservados -->
          <div class="col-md-4 col-12 text-center mb-2 mb-md-0">
            <small class="text-light">2025 Colibrí - Diseño y desarrollo - Miguel Ángel</small>
          </div>
          
          <!-- Redes sociales -->
          <div class="col-md-4 col-12 text-center text-md-end">
            <div class="d-flex justify-content-center justify-content-md-end gap-2">
              <a href="https://wa.me/1234567890" class="footer-social" title="WhatsApp">💬</a>
              <a href="https://instagram.com/colibri" class="footer-social" title="Instagram">📷</a>
              <a href="https://twitter.com/colibri" class="footer-social" title="Twitter">🐦</a>
            </div>
          </div>
        </div>
      </div>
    </footer>

    </div>

  </div>

</body>
</html>
