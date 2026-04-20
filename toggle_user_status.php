<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'config.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $user_id = intval($_GET['id']);
    $status  = $_GET['status'];

    if ($status == 'active' || $status == 'deactive') {

        if ($status == 'active') {
            // ✅ Bila activate — set is_new_registration = 0 (bukan lagi pendaftaran baru)
            $sql = "UPDATE users SET status = 'active', is_new_registration = 0 WHERE user_id = $user_id";
        } else {
            // Bila deactivate user lama — is_new_registration kekal 0
            $sql = "UPDATE users SET status = 'deactive' WHERE user_id = $user_id";
        }

        if (mysqli_query($conn, $sql)) {
            $_SESSION['message'] = "User status updated successfully!";
        } else {
            $_SESSION['error'] = "Update failed: " . mysqli_error($conn);
        }
    }

    header("Location: admin_interface.php?page=user_management");
    exit();
}
?>