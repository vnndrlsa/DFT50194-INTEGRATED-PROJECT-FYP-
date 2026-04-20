<?php
session_start();
include 'config.php';

// ✅ SECURITY: Prevent browser caching login page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

// ✅ SECURITY: Kalau dah login, redirect terus ke dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: mainAdmin_dashboard.php");
    } else {
        header("Location: staff_dashboard.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = mysqli_real_escape_string($conn, $_POST['staff_id']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $password_hash = md5($password);
    
    $sql = "SELECT * FROM users WHERE staff_id = '$staff_id' AND password = '$password_hash' AND status = 'active'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['staff_id'] = $user['staff_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department'] = $user['department'];
        
        if ($user['role'] == 'admin') {
            header("Location: mainAdmin_dashboard.php");
        } else {
            header("Location: staff_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid Staff ID or Password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMURAS - Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1f0021f0 0%, #d3321d 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #667eea; font-size: 24px; margin-bottom: 5px; }
        .logo p { color: #867e7e; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d3d2d2;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .password-wrapper { position: relative; }
        .password-wrapper input { padding-right: 45px; }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 18px;
            user-select: none;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-password:hover { color: #667eea; }
        .eye-icon {
            width: 20px;
            height: 20px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Cpath d='M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'/%3E%3Ccircle cx='12' cy='12' r='3'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }
        .eye-slash-icon {
            width: 20px;
            height: 20px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Cpath d='M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24'/%3E%3Cline x1='1' y1='1' x2='23' y2='23'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #4264ff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-login:hover { background: #871b23; }
        .divider { text-align: center; margin: 20px 0; color: #999; }
        .signup-link { text-align: center; margin-top: 20px; }
        .signup-link a { color: #667eea; text-decoration: none; font-weight: 500; }
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="img/Politeknik-Mukah.png" alt="Politeknik Mukah Logo" style="width:150px; height:auto;">
            <h1>PMU Recognition & Achievement System</h1>
            <p>PMU Recognition & Achievement Portal</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- ✅ SECURITY: autocomplete="off" -->
        <form method="POST" action="" autocomplete="off">
            <div class="form-group">
                <label for="staff_id">Staff ID</label>
                <!-- ✅ SECURITY: autocomplete="off" -->
                <input type="text" id="staff_id" name="staff_id" autocomplete="off" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <!-- ✅ SECURITY: autocomplete="new-password" -->
                    <input type="password" id="password" name="password" autocomplete="new-password" required>
                    <div class="toggle-password" onclick="togglePassword()">
                        <div class="eye-icon" id="eyeIcon"></div>
                    </div>
                </div>
            </div>
            
            <div style="text-align: right; margin-top: 10px;">
                <a href="forgot_password.php" style="color: #667eea; text-decoration: none; font-size: 14px;">
                    Forgot Password?
                </a>
            </div>
            
            <button type="submit" class="btn-login">Sign In</button>
        </form>
        
        <div class="divider">OR</div>
        
        <div class="signup-link">
            <a href="register.php">Sign Up / Register</a>
        </div>
    </div>
    
    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.className = 'eye-slash-icon';
        } else {
            passwordInput.type = 'password';
            eyeIcon.className = 'eye-icon';
        }
    }

    // ✅ SECURITY: Kosongkan form bila tekan arrow back
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            document.getElementById('staff_id').value = '';
            document.getElementById('password').value = '';
        }
        document.getElementById('password').value = '';
    });
    </script>
</body>
</html>