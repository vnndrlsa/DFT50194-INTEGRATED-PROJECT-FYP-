<?php
session_start();

// Remove admin access only, keep user logged in
if (isset($_SESSION['admin_access'])) {
    unset($_SESSION['admin_access']);
}

// Redirect back to main dashboard
header("Location: main_dashboard.php");
exit();
?>