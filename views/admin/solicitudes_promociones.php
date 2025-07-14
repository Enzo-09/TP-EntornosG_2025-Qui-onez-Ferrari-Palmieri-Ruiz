<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: /login');
    exit;
}

// Verificar que sea admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

// Función para obtener promociones pendientes
function getPromocionesPendientes($pdo) {
    $stmt = $pdo->prepare("
        SELECT p.*, l.nombreLocal, u.username as nombreDuenio 
        FROM promociones p 
        INNER JOIN locales l ON p.codLocal = l.codLocal 
        INNER JOIN users u ON l.codUsuario = u.id 
        WHERE p.estado = 'INACTIVA' 
        ORDER BY p.fechaCreacion DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para aprobar promoción
function aprobarPromocion($pdo, $codPromocion) {
    $stmt = $pdo->prepare("UPDATE promociones SET estado = 'ACTIVA' WHERE codPromocion = ?");
    return $stmt->execute([$codPromocion]);
}

// Función para rechazar promoción
function rechazarPromocion($pdo, $codPromocion, $motivo) {
    try {
        $pdo->beginTransaction();
        
        // Actualizar estado de la promoción
        $stmt = $pdo->prepare("UPDATE promociones SET estado = 'RECHAZADA' WHERE codPromocion = ?");
        $stmt->execute([$codPromocion]);
        
        // Insertar motivo del rechazo (opcional: crear tabla de motivos_rechazo)
        $stmt = $pdo->prepare("
            INSERT INTO motivos_rechazo (codPromocion, motivo, fechaRechazo) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$codPromocion, $motivo]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al rechazar promoción: " . $e->getMessage());
        return false;
    }
}

$mensaje = '';
$tipoMensaje = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['aprobar_promocion'])) {
        $codPromocion = intval($_POST['codPromocion']);
        if (aprobarPromocion($pdo, $codPromocion)) {
            $mensaje = 'Promoción aprobada exitosamente';
            $tipoMensaje = 'success';
        } else {
            $mensaje = 'Error al aprobar la promoción';
            $tipoMensaje = 'error';
        }
    }
    
    if (isset($_POST['rechazar_promocion'])) {
        $codPromocion = intval($_POST['codPromocion']);
        $motivo = trim($_POST['motivo_rechazo']);
        
        if (empty($motivo)) {
            $mensaje = 'El motivo del rechazo es obligatorio';
            $tipoMensaje = 'error';
        } else {
            if (rechazarPromocion($pdo, $codPromocion, $motivo)) {
                $mensaje = 'Promoción rechazada exitosamente';
                $tipoMensaje = 'success';
            } else {
                $mensaje = 'Error al rechazar la promoción';
                $tipoMensaje = 'error';
            }
        }
    }
}

// Obtener promociones pendientes
$promocionesPendientes = getPromocionesPendientes($pdo);

?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Promociones - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="/public/CSS/style.css">
  <style>
    /* Estilos para las tarjetas de promociones */
    .promotion-card {
      transition: transform 0.2s ease-in-out;
      border: 1px solid #e0e0e0;
    }
    .promotion-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    }
    
    /* Animación del badge pendiente */
    .badge-pendiente {
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.7; }
      100% { opacity: 1; }
    }

    /* Estilos para el main y contenido */
    main.container-fluid {
      background-color: #f8f9fa;
      min-height: 100vh;
    }

    /* Estilos para las tarjetas */
    .card {
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 1.5rem;
    }

    .card-header {
      border-bottom: 2px solid rgba(0,0,0,0.1);
      padding: 1rem 1.5rem;
    }

    .card-body {
      padding: 1.5rem;
    }

    /* Estilos para la información de promociones */
    .card-body strong {
      color: #495057;
      font-weight: 600;
    }

    .card-body p {
      color: #6c757d;
      line-height: 1.6;
    }

    /* Estilos para los botones de acción */
    .btn-lg {
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      font-weight: 600;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .btn-success:hover {
      background-color: #198754;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
    }

    .btn-danger:hover {
      background-color: #dc3545;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    /* Estilos para el título principal */
    .h2 {
      color: #343a40;
      font-weight: 700;
      margin-bottom: 2rem;
      position: relative;
    }

    .h2::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 60px;
      height: 3px;
      background: linear-gradient(90deg, #ffc107, #fd7e14);
      border-radius: 2px;
    }

    /* Estilos para alertas */
    .alert {
      border-radius: 10px;
      border: none;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .alert-info {
      background: linear-gradient(135deg, #d1ecf1, #bee5eb);
      color: #0c5460;
    }

    /* Estilos para el modal */
    .modal-content {
      border-radius: 12px;
      border: none;
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
    }

    .modal-header {
      border-bottom: 2px solid rgba(255,255,255,0.2);
      border-radius: 12px 12px 0 0;
    }

    .modal-footer {
      border-top: 1px solid #e9ecef;
      border-radius: 0 0 12px 12px;
    }

    /* Estilos para el textarea */
    .form-control {
      border-radius: 8px;
      border: 2px solid #e9ecef;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .form-control:focus {
      border-color: #ffc107;
      box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
    }

    /* Estilos para badges */
    .badge {
      font-weight: 600;
      letter-spacing: 0.5px;
      padding: 0.5rem 1rem;
    }

    /* Espaciado mejorado */
    .row.mb-3 {
      margin-bottom: 1.5rem !important;
    }

    /* Estilos para iconos */
    .fas {
      margin-right: 0.5rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .card-body .row {
        margin-bottom: 1rem;
      }
      
      .btn-lg {
        font-size: 0.9rem;
        padding: 0.6rem 1.2rem;
      }
      
      .h2 {
        font-size: 1.5rem;
      }
    }

    /* Estado hover para toda la tarjeta */
    .card:hover .card-header {
      background-color: #e0a800 !important;
    }

    /* Mejora visual para fechas */
    .text-muted.small {
      background: #f8f9fa;
      padding: 0.5rem;
      border-radius: 6px;
      border-left: 3px solid #ffc107;
    }
  </style>
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
    <main class="container-fluid p-4">
        <div class="row">
            <div class="col-12">
                <h1 class="h2 mb-4">Gestión de Solicitudes de Promociones</h1>
                
                <!-- Mostrar mensajes -->
                <?php if ($mensaje): ?>
                    <div class="alert <?php echo $tipoMensaje === 'success' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($mensaje); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Lista de promociones pendientes -->
                <?php if (empty($promocionesPendientes)): ?>
                    <div class="alert alert-info" role="alert">
                        <h4 class="alert-heading">No hay promociones pendientes</h4>
                        <p>Actualmente no hay promociones esperando aprobación.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($promocionesPendientes as $promocion): ?>
                            <div class="col-12 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-warning text-dark">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <h5 class="card-title mb-0">
                                                    <i class="fas fa-tag me-2"></i>
                                                    <?php echo htmlspecialchars($promocion['nombrePromocion']); ?>
                                                </h5>
                                            </div>
                                            <div class="col-md-6 text-md-end">
                                                <span class="badge bg-warning text-dark fs-6">PENDIENTE</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <!-- Información de la promoción -->
                                            <div class="col-md-8">
                                                <div class="row mb-3">
                                                    <div class="col-sm-6">
                                                        <strong>Local:</strong> <?php echo htmlspecialchars($promocion['nombreLocal']); ?>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <strong>Dueño:</strong> <?php echo htmlspecialchars($promocion['nombreDuenio']); ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mb-3">
                                                    <div class="col-sm-6">
                                                        <strong>Tipo:</strong> <?php echo htmlspecialchars($promocion['tipoPromocion']); ?>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <strong>Descuento:</strong> <?php echo number_format($promocion['descuento'], 2); ?>%
                                                    </div>
                                                </div>

                                                <div class="row mb-3">
                                                    <div class="col-sm-6">
                                                        <strong>Fecha Inicio:</strong> <?php echo date('d/m/Y', strtotime($promocion['fechaInicio'])); ?>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <strong>Fecha Fin:</strong> <?php echo date('d/m/Y', strtotime($promocion['fechaFin'])); ?>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <strong>Descripción:</strong>
                                                    <p class="mt-1"><?php echo nl2br(htmlspecialchars($promocion['descripcion'])); ?></p>
                                                </div>

                                                <?php if (!empty($promocion['condiciones'])): ?>
                                                    <div class="mb-3">
                                                        <strong>Condiciones:</strong>
                                                        <p class="mt-1 text-muted"><?php echo nl2br(htmlspecialchars($promocion['condiciones'])); ?></p>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="text-muted small">
                                                    <strong>Fecha de solicitud:</strong> <?php echo date('d/m/Y H:i', strtotime($promocion['fechaCreacion'])); ?>
                                                </div>
                                            </div>

                                            <!-- Botones de acción -->
                                            <div class="col-md-4">
                                                <div class="d-grid gap-2">
                                                    <!-- Botón Aprobar -->
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="codPromocion" value="<?php echo $promocion['codPromocion']; ?>">
                                                        <button type="submit" name="aprobar_promocion" class="btn btn-success btn-lg w-100" 
                                                                onclick="return confirm('¿Está seguro de que desea aprobar esta promoción?')">
                                                            <i class="fas fa-check me-2"></i>Aprobar
                                                        </button>
                                                    </form>

                                                    <!-- Botón Rechazar -->
                                                    <button type="button" class="btn btn-danger btn-lg w-100" 
                                                            data-bs-toggle="modal" data-bs-target="#modalRechazo<?php echo $promocion['codPromocion']; ?>">
                                                        <i class="fas fa-times me-2"></i>Rechazar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal para rechazo -->
                                <div class="modal fade" id="modalRechazo<?php echo $promocion['codPromocion']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Rechazar Promoción</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="codPromocion" value="<?php echo $promocion['codPromocion']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <h6>Promoción: <?php echo htmlspecialchars($promocion['nombrePromocion']); ?></h6>
                                                        <p class="text-muted">Local: <?php echo htmlspecialchars($promocion['nombreLocal']); ?></p>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="motivo_rechazo<?php echo $promocion['codPromocion']; ?>" class="form-label">
                                                            <strong>Motivo del rechazo <span class="text-danger">*</span></strong>
                                                        </label>
                                                        <textarea 
                                                            class="form-control" 
                                                            id="motivo_rechazo<?php echo $promocion['codPromocion']; ?>" 
                                                            name="motivo_rechazo" 
                                                            rows="4" 
                                                            placeholder="Explique el motivo por el cual se rechaza esta promoción..."
                                                            required
                                                        ></textarea>
                                                        <div class="form-text">Este motivo será comunicado al dueño del local.</div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" name="rechazar_promocion" class="btn btn-danger">
                                                        <i class="fas fa-times me-2"></i>Rechazar Promoción
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>footer</p>
    </footer>
    </div>

  </div>

  <!-- Bootstrap JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>