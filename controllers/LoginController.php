<?php
require_once __DIR__ . '/../controllers/BaseController.php';

class LoginController extends BaseController {

    public function showLogin() {
        include __DIR__ . '/../views/auth/login.php';
    }

    public function processLogin() {
        include_once __DIR__ . '/../config/config.inc';

        $username = $password = "";
        $errors = [];

        // Validaciones
        if (empty(trim($_POST["username"]))) {
            $errors['username_err'] = "Por favor ingresa tu nombre de usuario.";
        } else {
            $username = trim($_POST["username"]);
        }

        if (empty(trim($_POST["password"]))) {
            $errors['password_err'] = "Por favor ingresa tu contraseña.";
        } else {
            $password = trim($_POST["password"]);
        }

        if (empty($errors)) {
            $sql = "SELECT id, username, password, role, estado FROM users WHERE username = ?";

            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $username);
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role, $estado);
                        if (mysqli_stmt_fetch($stmt)) {
                            if (password_verify($password, $hashed_password)) {
                                if ($role === 'dueno' && $estado == 0) {
                                    $errors['login_err'] = "Tu cuenta aún no fue aprobada.";
                                } else {
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["id"] = $id;
                                    $_SESSION["username"] = $username;
                                    $_SESSION["role"] = $role;

                                    switch ($role) {
                                        case 'admin': Router::redirect('admin/dashboard'); break;
                                        case 'dueno': Router::redirect('duenio/dashboard'); break;
                                        case 'usuario': Router::redirect('home_usuario'); break;
                                        default: Router::redirect('home');
                                    }
                                    exit;
                                }
                            } else {
                                $errors['login_err'] = "Usuario o contraseña incorrectos.";
                            }
                        }
                    } else {
                        $errors['login_err'] = "Usuario o contraseña incorrectos.";
                    }
                } else {
                    $errors['login_err'] = "Error al ejecutar la consulta.";
                }
                mysqli_stmt_close($stmt);
            }
        }

        mysqli_close($link);

        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $_POST;

        Router::redirect('login');
    }
}
