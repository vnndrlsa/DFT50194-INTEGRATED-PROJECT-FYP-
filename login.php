<?php
session_start();

$error_message = "";

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = trim($_POST['user_id']);
    $password = $_POST['password'];

    // Read users from JSON file
    $users_file = 'users.json';
    
    if (file_exists($users_file)) {
        $users_data = json_decode(file_get_contents($users_file), true);
        
        // Check if user exists
        if (isset($users_data[$user_id])) {
            // Verify password
            if (password_verify($password, $users_data[$user_id]['password'])) {
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['full_name'] = $users_data[$user_id]['full_name'];
                $_SESSION['email'] = $users_data[$user_id]['email'];
                header("Location: main_dashboard.php");
                exit();
            } else {
                $error_message = "Invalid User ID or password!";
            }
        } else {
            $error_message = "Invalid User ID or password!";
        }
    } else {
        $error_message = "No users registered yet. Please register first.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - PMU Recognition & Achievement System</title>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: linear-gradient(135deg, #1e3a8a, #160047);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-container {
    background: #fff;
    width: 420px;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.35);
}

.logo-section {
    text-align: center;
    margin-bottom: 25px;
}

.logo-section img {
    max-width: 140px;
    margin-bottom: 12px;
}

.logo-section h1 {
    font-size: 22px;
    color: #285ae1;
}

.logo-section p {
    font-size: 14px;
    color: #666;
}

.error-message {
    background: #ffebee;
    color: #c62828;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 14px;
}

.success-message {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
    font-size: 14px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 2px solid #000dff;
    font-size: 14px;
}

.form-group input:focus {
    outline: none;
    border-color: #285ae1;
}

.btn {
    width: 100%;
    padding: 14px;
    border-radius: 8px;
    border: none;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, #1e3a8a, #160047);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(30, 58, 138, 0.4);
}

.btn-secondary {
    margin-top: 12px;
    background: white;
    color: #1e3a8a;
    border: 2px solid #1e3a8a;
    text-decoration: none;
    display: block;
    text-align: center;
}

.btn-secondary:hover {
    background: #eff6ff;
}

.divider {
    text-align: center;
    margin: 20px 0;
    position: relative;
    line-height: 1;
}

.divider::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 1px;
    background: #ddd;
    top: 50%;
    left: 0;
}

.divider span {
    background: #fff;
    padding: 0 12px;
    position: relative;
    z-index: 1;
    font-size: 13px;
    color: #666;
}
</style>
</head>

<body>

<div class="login-container">

    <div class="logo-section">
        <img src="img/Politeknik-Mukah.png" alt="PMU Logo">
        <h1>PMU Recognition & Achievement System</h1>
        <p>Recognition & Achievement Portal</p>
    </div>

    <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
        <div class="success-message">Registration successful! Please login with your credentials.</div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- LOGIN FORM -->
    <form method="POST" action="">
        <div class="form-group">
            <label>User ID</label>
            <input type="text" name="user_id" required placeholder="Enter your User ID">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Enter your password">
        </div>

        <button type="submit" class="btn btn-primary">Sign In</button>
    </form>

    <!-- DIVIDER -->
    <div class="divider">
        <span>OR</span>
    </div>

    <!-- REGISTER BUTTON -->
    <a href="register.php" class="btn btn-secondary">
        Sign Up / Register
    </a>

</div>

</body>
</html>