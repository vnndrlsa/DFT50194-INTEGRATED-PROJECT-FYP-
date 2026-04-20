<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = mysqli_real_escape_string($conn, $_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = '❌ Passwords do not match!';
        header("Location: reset_password.php?token=$token");
        exit();
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        $_SESSION['error_message'] = '❌ Password must be at least 6 characters!';
        header("Location: reset_password.php?token=$token");
        exit();
    }
    
    // Verify token is still valid
    $sql = "SELECT * FROM users WHERE reset_token = '$token' AND reset_token_expiry > NOW()";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 0) {
        $_SESSION['error_message'] = '❌ This reset link has expired. Please request a new one.';
        header("Location: forgot_password.php");
        exit();
    }
    
    $user = mysqli_fetch_assoc($result);
    
    // Hash password
    $hashed_password = md5($password);
    
    // Update password and clear reset token
    $user_id = $user['user_id'];
    $update_sql = "UPDATE users SET 
                   password = '$hashed_password',
                   reset_token = NULL,
                   reset_token_expiry = NULL
                   WHERE user_id = $user_id";
    
    if (mysqli_query($conn, $update_sql)) {
        // ✅ Simpan nama user untuk dipapar dalam success popup
        $_SESSION['reset_user_name'] = $user['full_name'];
        header("Location: reset_password.php?token=" . $token . "&success=1");
        exit();
    } else {
        $_SESSION['error_message'] = '❌ Failed to update password. Please try again.';
        header("Location: reset_password.php?token=$token");
        exit();
    }
}
?>