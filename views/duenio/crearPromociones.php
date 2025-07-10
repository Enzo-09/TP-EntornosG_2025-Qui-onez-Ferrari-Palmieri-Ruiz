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
            'ACTIVA',
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
  <style>
    /* Estilo del contenedor principal del formulario */
.form-header {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    color: white;
    padding: 30px;
    text-align: center;
    border-radius: 15px 15px 0 0;
    position: relative;
    overflow: hidden;
}

.form-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.form-header h1 {
    font-size: 2.2rem;
    margin-bottom: 8px;
    position: relative;
    z-index: 1;
    font-weight: 700;
}

.form-header h1 i {
    margin-right: 15px;
    color: #ffd700;
    animation: rotate 2s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.form-header p {
    font-size: 1.1rem;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

.form-content {
    padding: 40px;
    background: #f8f9fa;
}

.form-section {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border: 1px solid #e9ecef;
}

.form-section h3 {
    color: #2c3e50;
    font-size: 1.5rem;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 3px solid #3498db;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section h3 i {
    color: #3498db;
    font-size: 1.3rem;
}

/* Información del local */
.local-info {
    background: linear-gradient(135deg, #e8f5e8 0%, #d4f1d4 100%);
    border: 2px solid #27ae60;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    position: relative;
    overflow: hidden;
}

.local-info::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #27ae60, #2ecc71, #27ae60);
}

.local-info h4 {
    color: #27ae60;
    font-size: 1.2rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.local-info p {
    margin: 8px 0;
    color: #2c3e50;
    font-size: 0.95rem;
}

.local-info strong {
    color: #27ae60;
    font-weight: 600;
}

.badge {
    background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(39, 174, 96, 0.3);
}

/* Mensajes */
.mensaje {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.mensaje.success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 2px solid #28a745;
    color: #155724;
}

.mensaje.error {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    border: 2px solid #dc3545;
    color: #721c24;
}

.mensaje i {
    font-size: 1.2rem;
}

/* Botones secundarios */
.btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    margin: 0 8px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
}

/* Formulario */
#promocionForm {
    margin-top: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-label i {
    color: #3498db;
    font-size: 0.9rem;
}

.required-indicator {
    color: #e74c3c;
    font-weight: 700;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
}

.form-input:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    transform: translateY(-1px);
}

.form-input:hover {
    border-color: #bdc3c7;
}

.form-select {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px;
    padding-right: 40px;
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
}

.form-text {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.form-text::before {
    content: "ℹ️";
    font-size: 0.8rem;
}

/* Botón principal */
.btn-primary {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    padding: 15px 30px;
    border: none;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
    background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-primary i {
    font-size: 1.2rem;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-3px); }
    60% { transform: translateY(-2px); }
}

/* Efectos especiales para inputs */
.form-input[type="number"]::-webkit-outer-spin-button,
.form-input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.form-input[type="number"] {
    -moz-appearance: textfield;
}

/* Responsive */
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-header h1 {
        font-size: 1.8rem;
    }
    
    .form-content {
        padding: 20px;
    }
    
    .form-section {
        padding: 20px;
    }
    
    .btn-secondary {
        margin: 5px;
        width: 100%;
        justify-content: center;
    }
}

/* Animaciones adicionales */
.form-group {
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
}

.form-group:nth-child(1) { animation-delay: 0.1s; }
.form-group:nth-child(2) { animation-delay: 0.2s; }
.form-group:nth-child(3) { animation-delay: 0.3s; }
.form-group:nth-child(4) { animation-delay: 0.4s; }
.form-group:nth-child(5) { animation-delay: 0.5s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
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