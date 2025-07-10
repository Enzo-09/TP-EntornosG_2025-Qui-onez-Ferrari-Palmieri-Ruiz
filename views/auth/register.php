<?php
$old = $_SESSION['old'] ?? [];
$errors = $_SESSION['errors'] ?? [];

// Obtener valores previos o vacíos si no existen
$username = $old['username'] ?? '';
$role = $old['role'] ?? 'cliente';
$mail = $old['mail'] ?? '';
$password = $old['password'] ?? '';
$confirm_password = $old['confirm_password'] ?? '';

// Obtener errores específicos
$username_err = $errors['username_err'] ?? '';
$mail_err = $errors['mail_err'] ?? '';
$password_err = $errors['password_err'] ?? '';
$confirm_password_err = $errors['confirm_password_err'] ?? '';

// Limpiar sesiones después de obtener los valores
unset($_SESSION['old'], $_SESSION['errors']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="public/CSS/style.css">
</head>
<body id="register">
<div class="wrapper">
    <h2>Registro</h2>
    
    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errors['general_err'])): ?>
        <div class="error"><?php echo $errors['general_err']; ?></div>
    <?php endif; ?>
    
    <form action="/register" method="post">

        <div class="form-group">
            <label>Nombre de Usuario</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            <?php if (!empty($username_err)): ?>
                <div class='error'><?php echo $username_err; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Tipo de Usuario</label>
            <select name="role">
                <option value="cliente" <?php if ($role == "cliente") echo "selected"; ?>>Cliente</option>
                <option value="dueno" <?php if ($role == "dueno") echo "selected"; ?>>Dueño</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Correo Electrónico</label>
            <input type="email" name="mail" value="<?php echo htmlspecialchars($mail); ?>" required>
            <?php if (!empty($mail_err)): ?>
                <div class='error'><?php echo $mail_err; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" value="<?php echo htmlspecialchars($password); ?>" required>
            <?php if (!empty($password_err)): ?>
                <div class='error'><?php echo $password_err; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Confirmar Contraseña</label>
            <input type="password" name="confirm_password" value="<?php echo htmlspecialchars($confirm_password); ?>" required>
            <?php if (!empty($confirm_password_err)): ?>
                <div class='error'><?php echo $confirm_password_err; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <input type="submit" value="Registrar">
            <input type="reset" value="Limpiar">
        </div>
        <hr>
        <p>¿Ya tienes cuenta? <a href="/login">Inicia sesión</a>.</p>
    </form>
</div>
</body>
</html>