<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: /login');
    exit;
}

// Configuración de la base de datos
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

// Función para verificar local activo
function verificarLocalActivo($pdo, $codUsuario) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM locales WHERE codUsuario = ? AND estado = 'ACTIVO' LIMIT 1");
        $stmt->execute([$codUsuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al verificar local activo: " . $e->getMessage());
        return false;
    }
}

// Función para crear promoción
function crearPromocion($pdo, $datos) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO promociones (
                codLocal, 
                nombrePromocion, 
                descripcion, 
                descuento, 
                fechaInicio, 
                fechaFin, 
                estado, 
                tipoPromocion, 
                condiciones,
                fechaCreacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $datos['codLocal'],
            $datos['nombrePromocion'],
            $datos['descripcion'],
            $datos['descuento'],
            $datos['fechaInicio'],
            $datos['fechaFin'],
            'PENDIENTE',
            $datos['tipoPromocion'],
            $datos['condiciones']
        ]);
    } catch (PDOException $e) {
        error_log("Error al crear promoción: " . $e->getMessage());
        return false;
    }
}

// Función para validar datos de entrada
function validarDatosPromocion($datos) {
    $errores = [];
    
    // Validar nombre
    if (empty(trim($datos['nombrePromocion']))) {
        $errores[] = 'El nombre de la promoción es requerido';
    } elseif (strlen(trim($datos['nombrePromocion'])) > 100) {
        $errores[] = 'El nombre de la promoción no puede exceder 100 caracteres';
    }
    
    // Validar descripción
    if (empty(trim($datos['descripcion']))) {
        $errores[] = 'La descripción es requerida';
    } elseif (strlen(trim($datos['descripcion'])) > 500) {
        $errores[] = 'La descripción no puede exceder 500 caracteres';
    }
    
    // Validar descuento
    if (!is_numeric($datos['descuento']) || $datos['descuento'] <= 0 || $datos['descuento'] > 100) {
        $errores[] = 'El descuento debe ser un número entre 1 y 100';
    }
    
    // Validar tipo de promoción
    $tiposValidos = ['DESCUENTO_PORCENTAJE', '2X1', 'COMBO', 'HAPPY_HOUR'];
    if (!in_array($datos['tipoPromocion'], $tiposValidos)) {
        $errores[] = 'El tipo de promoción no es válido';
    }
    
    // Validar fechas
    if (empty($datos['fechaInicio']) || empty($datos['fechaFin'])) {
        $errores[] = 'Las fechas de inicio y fin son requeridas';
    } else {
        $fechaInicio = strtotime($datos['fechaInicio']);
        $fechaFin = strtotime($datos['fechaFin']);
        $hoy = strtotime('today');
        
        if ($fechaInicio === false || $fechaFin === false) {
            $errores[] = 'Las fechas ingresadas no son válidas';
        } else {
            if ($fechaInicio < $hoy) {
                $errores[] = 'La fecha de inicio no puede ser anterior a hoy';
            }
            
            if ($fechaInicio >= $fechaFin) {
                $errores[] = 'La fecha de inicio debe ser anterior a la fecha de fin';
            }
            
            // Verificar que la diferencia no sea mayor a 1 año
            $diferenciaDias = ($fechaFin - $fechaInicio) / (60 * 60 * 24);
            if ($diferenciaDias > 365) {
                $errores[] = 'La promoción no puede durar más de un año';
            }
        }
    }
    
    // Validar condiciones (opcional)
    if (!empty($datos['condiciones']) && strlen(trim($datos['condiciones'])) > 300) {
        $errores[] = 'Las condiciones no pueden exceder 300 caracteres';
    }
    
    return $errores;
}

// Función para limpiar datos de entrada
function limpiarDatos($datos) {
    return [
        'nombrePromocion' => trim($datos['nombrePromocion']),
        'descripcion' => trim($datos['descripcion']),
        'descuento' => floatval($datos['descuento']),
        'tipoPromocion' => trim($datos['tipoPromocion']),
        'fechaInicio' => trim($datos['fechaInicio']),
        'fechaFin' => trim($datos['fechaFin']),
        'condiciones' => trim($datos['condiciones'] ?? '')
    ];
}

// Función para obtener el texto del tipo de promoción
function getTipoPromocionTexto($tipo) {
    switch ($tipo) {
        case 'DESCUENTO_PORCENTAJE':
            return '💰 Descuento por Porcentaje';
        case '2X1':
            return '🎁 2x1';
        case 'COMBO':
            return '🍽️ Combo Especial';
        case 'HAPPY_HOUR':
            return '🍻 Happy Hour';
        default:
            return 'Tipo no definido';
    }
}

// Verificar que el usuario tenga un local activo
$local = verificarLocalActivo($pdo, $codUsuario);

if (!$local) {
    $_SESSION['error'] = 'No tienes un local activo para crear promociones';
    header('Location: /duenio/dashboard');
    exit;
}

// Variables para mensajes y estado
$mensaje = '';
$tipoMensaje = '';
$promocionCreada = false;
$datosFormulario = [];

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_promocion'])) {
    // Limpiar datos
    $datosFormulario = limpiarDatos($_POST);
    $datosFormulario['codLocal'] = $local['codLocal'];
    
    // Validar datos
    $errores = validarDatosPromocion($datosFormulario);
    
    if (empty($errores)) {
        // Intentar crear la promoción
        if (crearPromocion($pdo, $datosFormulario)) {
            $mensaje = 'Promoción creada exitosamente';
            $tipoMensaje = 'success';
            $promocionCreada = true;
            
            // Limpiar datos del formulario después de crear exitosamente
            $datosFormulario = [];
        } else {
            $mensaje = 'Error al crear la promoción. Por favor, intenta nuevamente.';
            $tipoMensaje = 'error';
        }
    } else {
        // Mostrar errores
        $mensaje = implode('<br>', $errores);
        $tipoMensaje = 'error';
    }
}

// Función para mostrar valor del formulario
function mostrarValor($campo, $datosFormulario) {
    return htmlspecialchars($datosFormulario[$campo] ?? '');
}

// Función para mostrar opción seleccionada
function mostrarSelected($valor, $valorEsperado, $datosFormulario) {
    return ($datosFormulario[$valor] ?? '') === $valorEsperado ? 'selected' : '';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Menu Dueño</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/public/CSS/style.css">
</head>
<body>

  <div class="layout-container">
    
    <header>
      <img src="/public/IMG/img-logo-circular.png" alt="Logo" class="logo">
      <hr>
      <nav class="nav flex-column w-100">
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
    <main>
        <div class="formularioCreaPromo">
            <div class="form-header">
                <h1><i class="fas fa-percentage"></i> Crear Nueva Promoción</h1>
                <p>Diseña promociones atractivas para tu local</p>
            </div>

            <div class="form-content">
                <div class="form-section">
                    <h3><i class="fas fa-edit"></i> Información de la Promoción</h3>
                    
                    <!-- Información del local -->
                    <div class="local-info">
                        <h4><i class="fas fa-store-alt"></i> Local Actual</h4>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($local['nombreLocal']); ?></p>
                        <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($local['ubicacionLocal']); ?></p>
                        <p><strong>Rubro:</strong> <?php echo htmlspecialchars($local['rubroLocal']); ?></p>
                        <p><strong>Estado:</strong> <span class="badge">ACTIVO</span></p>
                    </div>

                    <!-- Mostrar mensajes -->
                    <?php if ($mensaje): ?>
                        <div class="mensaje <?php echo $tipoMensaje; ?>">
                            <i class="fas fa-<?php echo $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Mostrar botón para crear otra promoción o ver promociones -->
                    <?php if ($promocionCreada): ?>
                        <div style="text-align: center; margin-bottom: 20px;">
                            <a href="/promociones/consultar" class="btn-secondary">
                                <i class="fas fa-eye"></i> Ver Mis Promociones
                            </a>
                            <a href="/promociones/crear" class="btn-secondary">
                                <i class="fas fa-plus"></i> Crear Otra Promoción
                            </a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="promocionForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="nombrePromocion">
                                    <i class="fas fa-tag"></i> Nombre de la Promoción <span class="required-indicator">*</span>
                                </label>
                                <input type="text" class="form-input" id="nombrePromocion" name="nombrePromocion" 
                                    placeholder="Ej: Descuento de Verano" required maxlength="100"
                                    value="<?php echo mostrarValor('nombrePromocion', $datosFormulario); ?>">
                                <div class="form-text">Máximo 100 caracteres</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="tipoPromocion">
                                    <i class="fas fa-list"></i> Tipo de Promoción <span class="required-indicator">*</span>
                                </label>
                                <select class="form-input form-select" id="tipoPromocion" name="tipoPromocion" required>
                                    <option value="">Seleccione un tipo</option>
                                    <option value="DESCUENTO_PORCENTAJE" <?php echo mostrarSelected('tipoPromocion', 'DESCUENTO_PORCENTAJE', $datosFormulario); ?>>💰 Descuento por Porcentaje</option>
                                    <option value="2X1" <?php echo mostrarSelected('tipoPromocion', '2X1', $datosFormulario); ?>>🎁 2x1</option>
                                    <option value="COMBO" <?php echo mostrarSelected('tipoPromocion', 'COMBO', $datosFormulario); ?>>🍽️ Combo Especial</option>
                                    <option value="HAPPY_HOUR" <?php echo mostrarSelected('tipoPromocion', 'HAPPY_HOUR', $datosFormulario); ?>>🍻 Happy Hour</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="descripcion">
                                <i class="fas fa-align-left"></i> Descripción <span class="required-indicator">*</span>
                            </label>
                            <textarea class="form-input form-textarea" id="descripcion" name="descripcion" 
                                    placeholder="Describe los detalles de tu promoción..." required maxlength="500"><?php echo mostrarValor('descripcion', $datosFormulario); ?></textarea>
                            <div class="form-text">Máximo 500 caracteres</div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="descuento">
                                    <i class="fas fa-percent"></i> Descuento (%) <span class="required-indicator">*</span>
                                </label>
                                <input type="number" class="form-input" id="descuento" name="descuento" 
                                    min="1" max="100" step="0.01" placeholder="15" required
                                    value="<?php echo mostrarValor('descuento', $datosFormulario); ?>">
                                <div class="form-text">Entre 1% y 100%</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="fechaInicio">
                                    <i class="fas fa-calendar-alt"></i> Fecha de Inicio <span class="required-indicator">*</span>
                                </label>
                                <input type="date" class="form-input" id="fechaInicio" name="fechaInicio" required
                                    min="<?php echo date('Y-m-d'); ?>"
                                    value="<?php echo mostrarValor('fechaInicio', $datosFormulario); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="fechaFin">
                                    <i class="fas fa-calendar-check"></i> Fecha de Fin <span class="required-indicator">*</span>
                                </label>
                                <input type="date" class="form-input" id="fechaFin" name="fechaFin" required
                                    value="<?php echo mostrarValor('fechaFin', $datosFormulario); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="condiciones">
                                <i class="fas fa-info-circle"></i> Condiciones y Restricciones
                            </label>
                            <textarea class="form-input form-textarea" id="condiciones" name="condiciones" 
                                    placeholder="Ej: Válido solo los fines de semana, No acumulable con otras promociones..." 
                                    maxlength="300"><?php echo mostrarValor('condiciones', $datosFormulario); ?></textarea>
                            <div class="form-text">Opcional - Máximo 300 caracteres</div>
                        </div>

                        <button type="submit" name="crear_promocion" class="btn-primary">
                            <i class="fas fa-rocket"></i>
                            Crear Promoción
                        </button>
                    </form>
                </div>
            </div>
        </div>


    </main>

    <footer>
        <p>footer</p>
    </footer>
    </div>

  </div>

</body>
</html>
















<!-- 
<div>
            <div class="form-header">
                <h1><i class="fas fa-percentage"></i> Crear Nueva Promoción</h1>
                <p>Diseña promociones atractivas para tu local</p>
            </div>

            <div class="form-content">
                <div class="form-section">
                    <h3><i class="fas fa-edit"></i> Información de la Promoción</h3>
                    
                    <!-- Información del local -->
                    <div class="local-info">
                        <h4><i class="fas fa-store-alt"></i> Local Actual</h4>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($local['nombreLocal']); ?></p>
                        <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($local['ubicacionLocal']); ?></p>
                        <p><strong>Rubro:</strong> <?php echo htmlspecialchars($local['rubroLocal']); ?></p>
                        <p><strong>Estado:</strong> <span class="badge">ACTIVO</span></p>
                    </div>

                    <!-- Mostrar mensajes -->
                    <?php if ($mensaje): ?>
                        <div class="mensaje <?php echo $tipoMensaje; ?>">
                            <i class="fas fa-<?php echo $tipoMensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Mostrar botón para crear otra promoción o ver promociones -->
                    <?php if ($promocionCreada): ?>
                        <div style="text-align: center; margin-bottom: 20px;">
                            <a href="/promociones/consultar" class="btn-secondary">
                                <i class="fas fa-eye"></i> Ver Mis Promociones
                            </a>
                            <a href="/promociones/crear" class="btn-secondary">
                                <i class="fas fa-plus"></i> Crear Otra Promoción
                            </a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="promocionForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="nombrePromocion">
                                    <i class="fas fa-tag"></i> Nombre de la Promoción <span class="required-indicator">*</span>
                                </label>
                                <input type="text" class="form-input" id="nombrePromocion" name="nombrePromocion" 
                                    placeholder="Ej: Descuento de Verano" required maxlength="100"
                                    value="<?php echo mostrarValor('nombrePromocion', $datosFormulario); ?>">
                                <div class="form-text">Máximo 100 caracteres</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="tipoPromocion">
                                    <i class="fas fa-list"></i> Tipo de Promoción <span class="required-indicator">*</span>
                                </label>
                                <select class="form-input form-select" id="tipoPromocion" name="tipoPromocion" required>
                                    <option value="">Seleccione un tipo</option>
                                    <option value="DESCUENTO_PORCENTAJE" <?php echo mostrarSelected('tipoPromocion', 'DESCUENTO_PORCENTAJE', $datosFormulario); ?>>💰 Descuento por Porcentaje</option>
                                    <option value="2X1" <?php echo mostrarSelected('tipoPromocion', '2X1', $datosFormulario); ?>>🎁 2x1</option>
                                    <option value="COMBO" <?php echo mostrarSelected('tipoPromocion', 'COMBO', $datosFormulario); ?>>🍽️ Combo Especial</option>
                                    <option value="HAPPY_HOUR" <?php echo mostrarSelected('tipoPromocion', 'HAPPY_HOUR', $datosFormulario); ?>>🍻 Happy Hour</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="descripcion">
                                <i class="fas fa-align-left"></i> Descripción <span class="required-indicator">*</span>
                            </label>
                            <textarea class="form-input form-textarea" id="descripcion" name="descripcion" 
                                    placeholder="Describe los detalles de tu promoción..." required maxlength="500"><?php echo mostrarValor('descripcion', $datosFormulario); ?></textarea>
                            <div class="form-text">Máximo 500 caracteres</div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="descuento">
                                    <i class="fas fa-percent"></i> Descuento (%) <span class="required-indicator">*</span>
                                </label>
                                <input type="number" class="form-input" id="descuento" name="descuento" 
                                    min="1" max="100" step="0.01" placeholder="15" required
                                    value="<?php echo mostrarValor('descuento', $datosFormulario); ?>">
                                <div class="form-text">Entre 1% y 100%</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="fechaInicio">
                                    <i class="fas fa-calendar-alt"></i> Fecha de Inicio <span class="required-indicator">*</span>
                                </label>
                                <input type="date" class="form-input" id="fechaInicio" name="fechaInicio" required
                                    min="<?php echo date('Y-m-d'); ?>"
                                    value="<?php echo mostrarValor('fechaInicio', $datosFormulario); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="fechaFin">
                                    <i class="fas fa-calendar-check"></i> Fecha de Fin <span class="required-indicator">*</span>
                                </label>
                                <input type="date" class="form-input" id="fechaFin" name="fechaFin" required
                                    value="<?php echo mostrarValor('fechaFin', $datosFormulario); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="condiciones">
                                <i class="fas fa-info-circle"></i> Condiciones y Restricciones
                            </label>
                            <textarea class="form-input form-textarea" id="condiciones" name="condiciones" 
                                    placeholder="Ej: Válido solo los fines de semana, No acumulable con otras promociones..." 
                                    maxlength="300"><?php echo mostrarValor('condiciones', $datosFormulario); ?></textarea>
                            <div class="form-text">Opcional - Máximo 300 caracteres</div>
                        </div>

                        <button type="submit" name="crear_promocion" class="btn-primary">
                            <i class="fas fa-rocket"></i>
                            Crear Promoción
                        </button>
                    </form>
                </div>
            </div>
        </div> -->