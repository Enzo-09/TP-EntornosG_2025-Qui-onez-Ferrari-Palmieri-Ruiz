<?php

$link = mysqli_connect("localhost", "root", "", "users");
if (!$link) {
    die("Problemas de conexión a la base de datos: " . mysqli_connect_error());
}

$mensaje_confirmacion = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $codLocal = $_POST['codLocal'];
    $accion = $_POST['accion'];

    if ($accion == 'aprobar') {
        $estado = 'ACTIVO';
    } elseif ($accion == 'rechazar') {
        $estado = 'RECHAZADO';
    } else {
        $mensaje_confirmacion = "Acción no válida.";
    }

    if (isset($estado)) {
        $stmt = mysqli_prepare($link, "UPDATE locales SET estado = ? WHERE codLocal = ?");
        mysqli_stmt_bind_param($stmt, "si", $estado, $codLocal);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $mensaje_confirmacion = "Solicitud procesada correctamente.";
        } else {
            $mensaje_confirmacion = "Error al procesar la solicitud.";
        }

        mysqli_stmt_close($stmt);
    }
}

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
        <a href="/admin/aprobaciones" class="text-decoration-none text-dark">Aprobaciones</a>
        <a href="/admin/consultas-soporte" class="text-decoration-none text-dark">Consultas Soporte</a>
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
       <h1>Solicitudes de Locales</h1>
       <hr>
       <div class="container-locales">
            <?php
            $link = mysqli_connect("localhost", "root", "", "users");

            if (!$link) {
                die("Problemas de conexión a la base de datos: " . mysqli_connect_error());
            }

            $sql = "SELECT codLocal, nombreLocal, rubroLocal, ubicacionLocal FROM locales WHERE estado = 'EN EVALUACION'";
            $result = mysqli_query($link, $sql);

            if (!$result) {
                echo "Error al ejecutar la consulta: " . mysqli_error($link);
                exit;
            }

            $rows = mysqli_num_rows($result);

            if ($rows > 0) {
                echo "<p>Locales pendientes de evaluación: $rows</p>";
                while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <div class="tarjeta-local">
                        <div class="info-local">
                            <h5><?php echo htmlspecialchars($row["nombreLocal"]); ?></h5>
                            <p><strong>Código:</strong> <?php echo htmlspecialchars($row["codLocal"]); ?></p>
                            <p><strong>Rubro:</strong> <?php echo htmlspecialchars($row["rubroLocal"]); ?></p>
                            <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($row["ubicacionLocal"]); ?></p>
                        </div>
                        <div class="acciones-local">
                            <form method="post">
                                <input type="hidden" name="codLocal" value="<?php echo htmlspecialchars($row["codLocal"]); ?>">
                                <button type="submit" name="accion" value="aprobar" class="btn-aprobar">Aprobar</button>
                                <button type="submit" name="accion" value="rechazar" class="btn-rechazar">Rechazar</button>
                            </form>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p>No hay locales pendientes de evaluación.</p>";
            }

            mysqli_close($link);
            ?>
       </div>
   
       <div>
          <?php if ($mensaje_confirmacion != ""): ?>
              <div class="alert alert-info">
                  <?php echo htmlspecialchars($mensaje_confirmacion); ?>
              </div>
          <?php endif; ?>
      </div>

    </main>

    <footer>
        <p>footer</p>
    </footer>
    </div>

  </div>

</body>
</html>