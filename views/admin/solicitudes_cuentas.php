<?php

$link = mysqli_connect("localhost", "root", "", "users");
if (!$link) {
    die("Problemas de conexión a la base de datos: " . mysqli_connect_error());
}

$mensaje_confirmacion = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $accion = $_POST['accion'];

    if ($accion == 'aprobar') {
        $estado = 1;
    } elseif ($accion == 'rechazar') {
        $estado = 2;
    } else {
        $mensaje_confirmacion = "Acción no válida.";
    }

    if (isset($estado)) {
        $stmt = mysqli_prepare($link, "UPDATE users SET estado = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $estado, $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $mensaje_confirmacion = "Solicitud procesada correctamente.";
        } else {
            $mensaje_confirmacion = "Error al procesar la solicitud.";
        }

        mysqli_stmt_close($stmt);
    }
}

// Aquí sigue tu consulta para mostrar solicitudes pendientes...
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
       <h1>Solicitudes de Cuentas de Dueños</h1>
       <hr>
       <div class="tarjeta-usuarios">
            <?php
            $link = mysqli_connect("localhost", "root", "", "users");

            if (!$link) {
                die("Problemas de conexión a la base de datos: " . mysqli_connect_error());
            }

            $sql = "SELECT id, username FROM users WHERE role = 'dueno' AND estado = 0";
            $result = mysqli_query($link, $sql);

            if (!$result) {
                echo "Error al ejecutar la consulta: " . mysqli_error($link);
                exit;
            }

            $rows = mysqli_num_rows($result);

            if ($rows > 0) {
                echo "<p>Solicitudes pendientes: $rows</p>";
                while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <div class="tarjeta-solicitud">
                        <p>Usuario: <?php echo htmlspecialchars($row["username"]); ?></p>
                        <form method="post">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row["id"]); ?>">
                            <button type="submit" name="accion" value="aprobar">Aprobar</button>
                            <button type="submit" name="accion" value="rechazar">Rechazar</button>
                        </form>

                    </div>
                    <?php
                }
            } else {
                echo "<p>No hay solicitudes pendientes.</p>";
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

