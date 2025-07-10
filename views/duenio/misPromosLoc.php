<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: /login');
    exit;
}

// Conexión a la base de datos con PDO
$host = 'localhost';
$db = 'users';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

$codUsuario = $_SESSION['id'] ?? 1;

function tieneLocal($pdo, $codUsuario) {
    $stmt = $pdo->prepare("SELECT * FROM locales WHERE codUsuario = ?");
    $stmt->execute([$codUsuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function crearLocal($pdo, $datos) {
    $stmt = $pdo->prepare("INSERT INTO locales (nombreLocal, ubicacionLocal, rubroLocal, codUsuario, estado) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([
        $datos['nombreLocal'],
        $datos['ubicacionLocal'],
        $datos['rubroLocal'],
        $datos['codUsuario'],
        $datos['estado'] // 'EN EVALUACION'
    ]);
}

function cerrarLocal($pdo, $codLocal) {
    $stmt = $pdo->prepare("DELETE FROM locales WHERE codLocal = ?");
    return $stmt->execute([$codLocal]);
}

$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['crear_local'])) {
        $datosLocal = [
            'nombreLocal' => trim($_POST['nombreLocal']),
            'ubicacionLocal' => trim($_POST['ubicacionLocal']),
            'rubroLocal' => trim($_POST['rubroLocal']),
            'codUsuario' => $codUsuario,
            'estado' => 'EN EVALUACION'
        ];

        if (crearLocal($pdo, $datosLocal)) {
            $mensaje = 'Local creado exitosamente y está EN EVALUACIÓN';
            $tipoMensaje = 'success';
        } else {
            $mensaje = 'Error al crear el local';
            $tipoMensaje = 'error';
        }
    }

    if (isset($_POST['cerrar_local'])) {
        $codLocal = $_POST['codLocal'];
        if (cerrarLocal($pdo, $codLocal)) {
            $mensaje = 'Local cerrado exitosamente';
            $tipoMensaje = 'success';
        } else {
            $mensaje = 'Error al cerrar el local';
            $tipoMensaje = 'error';
        }
    }
}

$local = tieneLocal($pdo, $codUsuario);
$estadoLocal = $local['estado'] ?? null;

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
        <a href="/admin/dashboard" class="text-decoration-none text-dark">Inicio</a>
        <a href="/duenio/dashboard" class="text-decoration-none text-dark">Panel Duenio</a>
        <a href="/consultas-soporte" class="text-decoration-none text-dark">Consultas Soporte</a>
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
    <main class="panelDuenio">
      <div class="container">
          <div class="header">
              <h1>Gestión de Local</h1>
              <p>Sistema de administración y control de locales comerciales</p>
          </div>

          <div class="content">
              <?php if ($mensaje): ?>
                  <div class="mensaje <?php echo $tipoMensaje; ?>">
                      <?php echo htmlspecialchars($mensaje); ?>
                  </div>
              <?php endif; ?>

              <?php if ($local): ?>
                <div class="status-card">
                    <div class="status-title">
                        <?php if ($estadoLocal === 'ACTIVO'): ?>
                            <div class="status-icon success">✓</div>
                            Estado del Local: ACTIVO
                        <?php else: ?>
                            <div class="status-icon warning">!</div>
                            Estado del Local: EN EVALUACIÓN
                        <?php endif; ?>
                    </div>
                    <!-- Botones según el estado del local -->
                    <?php if ($estadoLocal === 'ACTIVO'): ?>
                        <div class="buttons-container mt-4">
                            <h3 class="mb-3">Gestión de Promociones</h3>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <a href="/duenio/mis-promociones/crear" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-plus-circle me-2"></i>
                                        Crear Promoción
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="/promociones/consultar" class="btn btn-info btn-lg w-100">
                                        <i class="fas fa-search me-2"></i>
                                        Consultar Promociones
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="evaluation-message mt-4">
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-clock me-2"></i>Local en Evaluación</h5>
                                <p class="mb-0">Su local se encuentra actualmente en proceso de evaluación. Una vez que sea aprobado por nuestro equipo, podrá acceder a todas las funcionalidades de gestión de promociones.</p>
                                <hr>
                                <small class="text-muted">
                                    <strong>Tiempo estimado de evaluación:</strong> 2-3 días hábiles<br>
                                    <strong>Estado actual:</strong> Pendiente de revisión
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Usuario no tiene un local -->
                <div class="status-card no-local">
                    <div class="status-title">
                        <div class="status-icon warning">!</div>
                        Estado del Local: NO POSEE LOCAL
                    </div>
                    <p style="color: #666; margin-bottom: 20px;">
                        Actualmente no tiene ningún local registrado en el sistema. Para comenzar a gestionar promociones, debe crear un local primero.
                    </p>
                </div>
            <?php endif; ?>

          </div>
      </div>
      
    <script>
        function toggleForm(formId) {
            const form = document.getElementById(formId);
            if (form.classList.contains('active')) {
                form.classList.remove('active');
            } else {
                // Ocultar otros formularios
                document.querySelectorAll('.form-container').forEach(f => f.classList.remove('active'));
                form.classList.add('active');
            }
        }

        // Validación del formulario
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const inputs = form.querySelectorAll('input[required], select[required]');
                    let valid = true;
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            valid = false;
                            input.style.borderColor = '#dc3545';
                        } else {
                            input.style.borderColor = '#e9ecef';
                        }
                    });
                    
                    if (!valid) {
                        e.preventDefault();
                        alert('Por favor, complete todos los campos obligatorios.');
                    }
                });
            });
        });
    </script>            

    </main>

    <footer>
        <p>footer</p>
    </footer>
    </div>

  </div>

  <!-- Font Awesome para los iconos -->
  <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>

</body>
</html>