<?php
    require_once '../connection.php';

    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize input
        $username = trim($_POST['username']);
        $admin_password = $_POST['admin_password'];

        // Primero buscar en admins
        $stmt = $mysqli->prepare("SELECT admin_id, admin_password FROM admins WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($admin_id, $hashed_password);
            $stmt->fetch();
            if (password_verify($admin_password, $hashed_password)) {
                $_SESSION['admin_id'] = $admin_id;
                header("Location: ../dashboard/index.php");
                exit;
            } else {
                $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
            }
        } else {
            // Si no es admin, buscar en users
            $stmt->close();
            $stmt = $mysqli->prepare("SELECT user_id, password, role FROM users WHERE username = ?");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $hashed_password, $role);
                $stmt->fetch();
                if (password_verify($admin_password, $hashed_password)) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_role'] = $role;
                    // Redirigir según rol
                    if ($role === 'admin') {
                        header("Location: ../dashboard/index.php");
                    } else {
                        header("Location: ../dashboard/index.php"); // Puedes cambiar esto por un dashboard de usuario si lo deseas
                    }
                    exit;
                } else {
                    $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
                }
            } else {
                $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
            }
        }
        $stmt->close();
        $mysqli->close();
    } else {
        $_SESSION['login_error'] = 'Por favor, envía el formulario correctamente.';
    }

    // Redirect back to login on failure
    header("Location: login.php");
    exit;
?>