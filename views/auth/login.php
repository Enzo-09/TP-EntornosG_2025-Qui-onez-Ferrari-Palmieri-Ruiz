<?php
$old = $_SESSION['old'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old'], $_SESSION['errors']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="/public/CSS/style.css">
</head>
<body id="login">

<main>
    <div class="title"><h1>Iniciar Sesión</h1></div>
    <div class="container">
        <form action="/login" method="post">
            <label>Nombre de Usuario</label><br>
            <input type="text" name="username" value="<?= htmlspecialchars($old['username'] ?? '') ?>"><br>
            <span class="error"><?= $errors['username_err'] ?? '' ?></span><br>

            <label>Contraseña</label><br>
            <input type="password" name="password"><br>
            <span class="error"><?= $errors['password_err'] ?? '' ?></span><br><br>

            <input type="submit" value="Ingresar" class="login-btn">
        </form>

        <?php if (!empty($errors['login_err'])): ?>
            <p style="color: red;"><?= $errors['login_err'] ?></p>
        <?php endif; ?>

        <hr>
        <p class="register-text">¿No tienes una cuenta? <a href="/register">Regístrate aquí</a>.</p>
    </div>
</main>

</body>
</html>
