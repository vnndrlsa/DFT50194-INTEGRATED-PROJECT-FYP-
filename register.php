<?php
session_start();

$error_message = "";
$success_message = "";

// Handle registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = trim($_POST['user_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($user_id) || empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format!";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long!";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } else {
        // Read existing users
        $users_file = 'users.json';
        $users_data = [];

        if (file_exists($users_file)) {
            $users_data = json_decode(file_get_contents($users_file), true);
        }

        // Check if user ID already exists
        if (isset($users_data[$user_id])) {
            $error_message = "User ID already exists! Please choose a different User ID.";
        } else {
            // Check if email already exists
            $email_exists = false;
            foreach ($users_data as $user) {
                if ($user['email'] === $email) {
                    $email_exists = true;
                    break;
                }
            }

            if ($email_exists) {
                $error_message = "Email already registered! Please use a different email.";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Add new user
                $users_data[$user_id] = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'password' => $hashed_password,
                    'registered_at' => date('Y-m-d H:i:s')
                ];

                // Save to file
                if (file_put_contents($users_file, json_encode($users_data, JSON_PRETTY_PRINT))) {
                    header("Location: login.php?registered=success");
                    exit();
                } else {
                    $error_message = "Error saving user data. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - PMU Recognition & Achievement System</title>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: linear-gradient(135deg, #1e3a8a, #160047);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px 0;
}

.register-container {
    background: #fff;
    width: 420px;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.35);
    margin: 20px 0;
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

.form-group small {
    display: block;
    margin-top: 4px;
    color: #666;
    font-size: 12px;
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

.back-link {
    text-align: center;
    margin-top: 15px;
}

.back-link a {
    color: #1e3a8a;
    text-decoration: none;
    font-size: 14px;
}

.back-link a:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<div class="register-container">

    <div class="logo-section">
        <img src="img/Politeknik-Mukah.png" alt="PMU Logo">
        <h1>Create Account</h1>
        <p>Register for PMU Recognition System</p>
    </div>

    <?php if ($error_message): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <!-- REGISTRATION FORM -->
    <form method="POST" action="">
        <div class="form-group">
            <label>User ID</label>
            <input type="text" name="user_id" required placeholder="Create a unique User ID" 
                   value="<?php echo isset($_POST['user_id']) ? htmlspecialchars($_POST['user_id']) : ''; ?>">
            <small>This will be used to login</small>
        </div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required placeholder="Enter your full name"
                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
        </div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required placeholder="Enter your email"
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Create a password">
            <small>Minimum 6 characters</small>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required placeholder="Confirm your password">
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
    </form>

    <!-- DIVIDER -->
    <div class="divider">
        <span>OR</span>
    </div>

    <!-- LOGIN LINK -->
    <a href="login.php" class="btn btn-secondary">
        Already have an account? Sign In
    </a>

</div>

</body>
</html>
