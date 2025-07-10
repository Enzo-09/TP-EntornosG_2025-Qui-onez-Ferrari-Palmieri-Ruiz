<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class VerifyController {

    public function show() {
        $mail = $_GET['mail'] ?? '';

        if (empty($mail)) {
            Router::redirect('register');
            return;
        }

        $old = $_SESSION['old'] ?? [];
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['old'], $_SESSION['errors']);

        require_once __DIR__ . '/../views/auth/verify.php';
    }

    public function check() {
        include_once __DIR__ . '/../config/config.inc';

        $mail = $_POST['mail'] ?? '';
        $verification_code = $_POST['verification_code'] ?? '';
        $errors = [];

        // Validaciones básicas
        if (empty($mail)) {
            $errors['mail_err'] = "Email requerido.";
        } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $errors['mail_err'] = "Formato de email inválido.";
        }

        if (empty($verification_code)) {
            $errors['code_err'] = "Código de verificación requerido.";
        } elseif (strlen($verification_code) !== 6) {
            $errors['code_err'] = "El código debe tener 6 caracteres.";
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Router::redirect("verify?mail=" . urlencode($mail));
            return;
        }

        // Verificar el código
        $sql = "SELECT id, username, role FROM users WHERE mail = ? AND verification_code = ? AND estado = 0";
        $stmt = mysqli_prepare($link, $sql);
        
        if (!$stmt) {
            $errors['general_err'] = "Error en la base de datos. Intenta nuevamente.";
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            Router::redirect("verify?mail=" . urlencode($mail));
            return;
        }

        mysqli_stmt_bind_param($stmt, "ss", $mail, $verification_code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $update_sql = "UPDATE users SET estado = 1, verification_code = NULL WHERE id = ?";
            $update_stmt = mysqli_prepare($link, $update_sql);
            
            if (!$update_stmt) {
                $errors['general_err'] = "Error al activar la cuenta. Intenta nuevamente.";
            } else {
                mysqli_stmt_bind_param($update_stmt, "i", $row['id']);

                if (mysqli_stmt_execute($update_stmt)) {
                    $_SESSION['success_message'] = "¡Cuenta verificada exitosamente! Ya puedes iniciar sesión.";
                    mysqli_stmt_close($update_stmt);
                    mysqli_stmt_close($stmt);
                    mysqli_close($link);
                    Router::redirect('login');
                    return;
                } else {
                    $errors['general_err'] = "Error al activar la cuenta. Intenta nuevamente.";
                }
                mysqli_stmt_close($update_stmt);
            }
        } else {
            $errors['code_err'] = "Código inválido o la cuenta ya fue verificada.";
        }

        mysqli_stmt_close($stmt);
        mysqli_close($link);

        // Mostrar errores
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $_POST;
        Router::redirect("verify?mail=" . urlencode($mail));
    }

    public function resend() {
        include_once __DIR__ . '/../config/config.inc';

        $mail = $_POST['mail'] ?? '';
        $errors = [];

        if (empty($mail)) {
            $_SESSION['errors'] = ['general_err' => 'Email requerido.'];
            Router::redirect('register');
            return;
        }

        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['errors'] = ['general_err' => 'Formato de email inválido.'];
            Router::redirect("verify?mail=" . urlencode($mail));
            return;
        }

        // Verificar que el mail exista y no esté activado
        $sql = "SELECT id, username FROM users WHERE mail = ? AND estado = 0";
        $stmt = mysqli_prepare($link, $sql);
        
        if (!$stmt) {
            $_SESSION['errors'] = ['general_err' => 'Error en la base de datos. Intenta más tarde.'];
            Router::redirect("verify?mail=" . urlencode($mail));
            return;
        }

        mysqli_stmt_bind_param($stmt, "s", $mail);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $new_verification_code = bin2hex(random_bytes(3));

            // Actualizar código
            $update_sql = "UPDATE users SET verification_code = ? WHERE mail = ?";
            $update_stmt = mysqli_prepare($link, $update_sql);
            
            if (!$update_stmt) {
                $_SESSION['errors'] = ['general_err' => 'Error al generar nuevo código. Intenta más tarde.'];
            } else {
                mysqli_stmt_bind_param($update_stmt, "ss", $new_verification_code, $mail);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);

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
                    $mailSender->addAddress($mail, $row['username']);
                    $mailSender->Subject = 'Nuevo código de verificación';
                    $mailSender->Body = "Hola " . htmlspecialchars($row['username']) . ",<br><br>Tu nuevo código de verificación es: <b>$new_verification_code</b><br><br>Ingresa este código en la página de verificación para activar tu cuenta.";
                    $mailSender->isHTML(true);
                    $mailSender->send();

                    $_SESSION['success_message'] = "Se ha enviado un nuevo código a tu correo.";
                } catch (Exception $e) {
                    $_SESSION['errors'] = ['general_err' => 'Error al enviar el correo. Intenta más tarde.'];
                }
            }
        } else {
            $_SESSION['errors'] = ['general_err' => 'No se encontró una cuenta pendiente de verificación con ese correo.'];
        }

        mysqli_stmt_close($stmt);
        mysqli_close($link);

        Router::redirect("verify?mail=" . urlencode($mail));
    }
}
?>