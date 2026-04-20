<?php
session_start();

// ✅ SECURITY: Buang semua session data
$_SESSION = array();

// ✅ SECURITY: Buang session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// ✅ SECURITY: No-cache header sebelum redirect
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Location: login.php");
exit();
?>