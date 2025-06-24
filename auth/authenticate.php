<?php
    require_once '../connection.php';

    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize input
        $username = trim($_POST['username']);
        $admin_password = $_POST['admin_password'];

        // Prepare statement to avoid SQL injection
        $stmt = $mysqli->prepare("SELECT admin_id, admin_password FROM admins WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        // If user found
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($admin_id, $hashed_password);
            $stmt->fetch();

            // Verify password
            if (password_verify($admin_password, $hashed_password)) {
                $_SESSION['admin_id'] = $admin_id;
                header("Location: ../dashboard/index.php");
                exit;
            } else {
                $_SESSION['login_error'] = 'Invalid username or password.';
            }
        } else {
            $_SESSION['login_error'] = 'Invalid username or password.';
        }

        $stmt->close();
        $mysqli->close();
    } else {
        $_SESSION['login_error'] = 'Please submit the form properly.';
    }

    // Redirect back to login on failure
    header("Location: login.php");
    exit;
?>