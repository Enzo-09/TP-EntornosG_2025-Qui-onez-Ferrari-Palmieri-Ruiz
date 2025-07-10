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

                    <div class="local-info">
                        <div class="info-item">
                            <div class="info-label">Código del Local</div>
                            <div class="info-value"><?php echo htmlspecialchars($local['codLocal']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Nombre del Local</div>
                            <div class="info-value"><?php echo htmlspecialchars($local['nombreLocal']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Ubicación</div>
                            <div class="info-value"><?php echo htmlspecialchars($local['ubicacionLocal']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Rubro</div>
                            <div class="info-value"><?php echo htmlspecialchars($local['rubroLocal']); ?></div>
                        </div>
                    </div>

                    <?php if ($estadoLocal === 'ACTIVO'): ?>
                    <div class="buttons-container">
                        <button class="btn btn-danger" onclick="toggleForm('cerrar-form')">
                            Cerrar Local
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($estadoLocal === 'ACTIVO'): ?>
                <!-- Formulario para cerrar local -->
                <div id="cerrar-form" class="form-container">
                    <h3 style="margin-bottom: 20px; color: #dc3545;">Confirmar Cierre de Local</h3>
                    <p style="margin-bottom: 20px; color: #666;">
                        ¿Está seguro de que desea cerrar el local "<strong><?php echo htmlspecialchars($local['nombreLocal']); ?></strong>"? 
                        Esta acción no se puede deshacer.
                    </p>
                    <form method="POST">
                        <input type="hidden" name="codLocal" value="<?php echo $local['codLocal']; ?>">
                        <div class="buttons-container">
                            <button type="submit" name="cerrar_local" class="btn btn-danger">
                                Confirmar Cierre
                            </button>
                            <button type="button" class="btn btn-primary" onclick="toggleForm('cerrar-form')">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Usuario no tiene un local -->
                <div class="status-card no-local">
                    <div class="status-title">
                        <div class="status-icon warning">!</div>
                        Estado del Local: NO POSEE LOCAL
                    </div>
                    <p style="color: #666; margin-bottom: 20px;">
                        Actualmente no tiene ningún local registrado en el sistema. Puede crear uno nuevo utilizando el formulario a continuación.
                    </p>

                    <div class="buttons-container">
                        <button class="btn btn-success" onclick="toggleForm('crear-form')">
                            Crear Nuevo Local
                        </button>
                    </div>
                    <div id="crear-form" class="form-container">
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5>Crear Nuevo Local</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="nombreLocal" class="form-label">Nombre del Local *</label>
                                        <input type="text" class="form-control" id="nombreLocal" name="nombreLocal" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="ubicacionLocal" class="form-label">Ubicación *</label>
                                        <input type="text" class="form-control" id="ubicacionLocal" name="ubicacionLocal" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="rubroLocal" class="form-label">Rubro *</label>
                                        <select class="form-select" id="rubroLocal" name="rubroLocal" required>
                                            <option value="">Seleccione un rubro</option>
                                            <option value="Restaurante">Restaurante</option>
                                            <option value="Tienda de Ropa">Tienda de Ropa</option>
                                            <option value="Supermercado">Supermercado</option>
                                            <option value="Farmacia">Farmacia</option>
                                            <option value="Panadería">Panadería</option>
                                            <option value="Librería">Librería</option>
                                            <option value="Ferretería">Ferretería</option>
                                            <option value="Otros">Otros</option>
                                        </select>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="crear_local" class="btn btn-primary">
                                            Crear Local
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="toggleForm('crear-form')">
                                            Cancelar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
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

</body>
</html>


