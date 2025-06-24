<?php
    require_once '../connection.php';

    // Block access if not logged in
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
?>