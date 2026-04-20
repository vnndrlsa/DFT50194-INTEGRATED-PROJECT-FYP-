<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'config.php';

$delete_user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Safety checks
if ($delete_user_id == 0) {
    header("Location: admin_interface.php?page=user_management");
    exit();
}

// Prevent admin from deleting themselves
if ($delete_user_id == $_SESSION['user_id']) {
    header("Location: admin_interface.php?page=user_management&error=cannot_delete_self");
    exit();
}

// Prevent deleting other admins
$check_sql = "SELECT role FROM users WHERE user_id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $delete_user_id);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_bind_result($check_stmt, $user_role);
mysqli_stmt_fetch($check_stmt);
mysqli_stmt_close($check_stmt);

if ($user_role == 'admin') {
    header("Location: admin_interface.php?page=user_management&error=cannot_delete_admin");
    exit();
}

// Delete user's submissions first (foreign key)
$del_submissions = "DELETE FROM submissions WHERE user_id = ?";
$sub_stmt = mysqli_prepare($conn, $del_submissions);
mysqli_stmt_bind_param($sub_stmt, "i", $delete_user_id);
mysqli_stmt_execute($sub_stmt);
mysqli_stmt_close($sub_stmt);

// Delete the user
$del_sql = "DELETE FROM users WHERE user_id = ?";
$del_stmt = mysqli_prepare($conn, $del_sql);
mysqli_stmt_bind_param($del_stmt, "i", $delete_user_id);

if (mysqli_stmt_execute($del_stmt)) {
    header("Location: admin_interface.php?page=user_management&success=user_deleted");
} else {
    header("Location: admin_interface.php?page=user_management&error=delete_failed");
}

mysqli_stmt_close($del_stmt);
mysqli_close($conn);
exit();
?>