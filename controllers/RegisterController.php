<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // PHPMailer

class RegisterController {
    public function showRegister() {
        require_once __DIR__ . '/../views/auth/register.php';
    }

    public function processRegister() {
        include_once __DIR__ . '/../config/config.inc';
        
        $username = $_POST["username"] ?? '';
        $mail = $_POST["mail"] ?? '';
        $password = $_POST["password"] ?? '';
        $confirm_password = $_POST["confirm_password"] ?? '';
        $role = $_POST["role"] ?? 'cliente';
        $estado = ($role === 'dueno') ? 0 : 1;
        $errors = [];

        // Validaciones
        if (empty(trim($username))) {
            $errors['username_err'] = "Por favor ingresa un nombre de usuario.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username_err'] = "Solo letras, números y guiones bajos.";
        } else {
            $stmt = mysqli_prepare($link, "SELECT id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors['username_err'] = "Este usuario ya existe.";
            }
            mysqli_stmt_close($stmt);
        }

        if (empty($mail) || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $errors['mail_err'] = "Correo inválido.";
        } else {
            $stmt = mysqli_prepare($link, "SELECT id FROM users WHERE mail = ?");
            mysqli_stmt_bind_param($stmt, "s", $mail);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors['mail_err'] = "Este correo ya está registrado.";
            }
            mysqli_stmt_close($stmt);
        }

        if (strlen($password) < 6) {
            $errors['password_err'] = "La contraseña debe tener al menos 6 caracteres.";
        }

        if ($password !== $confirm_password) {
            $errors['confirm_password_err'] = "Las contraseñas no coinciden.";
        }

        // Si hay errores
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Router::redirect('register');
            return;
        }

        $verification_code = bin2hex(random_bytes(3)); // 6 caracteres

        $sql = "INSERT INTO users (username, password, role, estado, mail, verification_code)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $sql);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        mysqli_stmt_bind_param($stmt, "sssiss", $username, $hashed_password, $role, $estado, $mail, $verification_code);

        if (mysqli_stmt_execute($stmt)) {
            // Enviar correo
            $mailSender = new PHPMailer(true);
            try {
                $mailSender->isSMTP();
                $mailSender->Host = 'smtp.gmail.com';
                $mailSender->SMTPAuth = true;
                $mailSender->Username = 'yonosoyquinio@gmail.com'; 
                $mailSender->Password = 'dlul hwet lxhg wffy'; 
                $mailSender->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mailSender->Port = 587;

                $mailSender->setFrom('yonosoyquinio@gmail.com', 'Verificación Colibrí');
                $mailSender->addAddress($mail, $username);
                $mailSender->Subject = 'Tu código de verificación';
                $mailSender->Body = "Tu código de verificación es: <b>$verification_code</b>";
                $mailSender->isHTML(true);
                $mailSender->send();

                Router::redirect("verify.php?mail=" . urlencode($mail));
            } catch (Exception $e) {
                echo "Error al enviar correo: {$mailSender->ErrorInfo}";
            }
        } else {
            echo "Algo salió mal. Intenta más tarde.";
        }

        mysqli_stmt_close($stmt);
        mysqli_close($link);
    }
}
