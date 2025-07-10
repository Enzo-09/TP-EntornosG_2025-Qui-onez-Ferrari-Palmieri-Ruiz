<?php
$old = $_SESSION['old'] ?? [];
$errors = $_SESSION['errors'] ?? [];
$success_message = $_SESSION['success_message'] ?? '';
$mail = $_GET['mail'] ?? '';
unset($_SESSION['old'], $_SESSION['errors'], $_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Cuenta</title>
    <link rel="stylesheet" href="public/CSS/style.css">
</head>
<body id="verify">
<div class="wrapper">
    <h2>Verificación de Cuenta</h2>
    
    <?php if (!empty($success_message)): ?>
        <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errors['general_err'])): ?>
        <div class="error"><?php echo htmlspecialchars($errors['general_err']); ?></div>
    <?php endif; ?>
    
    <div class="info-text">
        <p>Hemos enviado un código de verificación a:</p>
        <strong><?php echo htmlspecialchars($mail); ?></strong>
        <p>Revisa tu bandeja de entrada y spam.</p>
    </div>

    <form action="/verify" method="post">
        <input type="hidden" name="mail" value="<?php echo htmlspecialchars($mail); ?>">
        
        <div class="form-group">
            <label>Código de Verificación</label>
            <input type="text" 
                   name="verification_code" 
                   value="<?php echo htmlspecialchars($old['verification_code'] ?? ''); ?>"
                   placeholder="Ingresa el código de 6 caracteres"
                   maxlength="6"
                   required>
            <?php if (!empty($errors['code_err'])): ?>
                <div class="error"><?php echo htmlspecialchars($errors['code_err']); ?></div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <input type="submit" value="Verificar">
        </div>
    </form>

    <div class="resend-section">
        <p>¿No recibiste el código?</p>
        <form action="/verify/resend" method="post" style="display: inline;">
            <input type="hidden" name="mail" value="<?php echo htmlspecialchars($mail); ?>">
            <input type="submit" value="Reenviar código" class="resend-btn">
        </form>
    </div>

    <hr>
    <p>¿Ya tienes cuenta? <a href="/login">Inicia sesión</a>.</p>
    <p>¿Quieres crear otra cuenta? <a href="/register">Regístrate</a>.</p>
</div>
</body>
</html>