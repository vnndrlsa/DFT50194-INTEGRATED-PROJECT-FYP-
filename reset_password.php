<?php
session_start();
require 'config.php';

// Get token from URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
$success = isset($_GET['success']) && $_GET['success'] == '1';

// Jika success=1, skip token verification — just show the success popup
if ($success) {
    // Ambil nama user dari session yang disimpan oleh process_reset.php
    $user = ['full_name' => isset($_SESSION['reset_user_name']) ? $_SESSION['reset_user_name'] : ''];
    unset($_SESSION['reset_user_name']);
} else {
    // Verify token seperti biasa
    if (empty($token)) {
        $_SESSION['error_message'] = '❌ Invalid reset link.';
        header("Location: login.php");
        exit();
    }

    $sql = "SELECT * FROM users WHERE reset_token = '$token' AND reset_token_expiry > NOW()";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 0) {
        $_SESSION['error_message'] = '❌ This reset link is invalid or has expired. Please request a new one.';
        header("Location: forgot_password.php");
        exit();
    }

    $user = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMURAS - Reset Password</title>
    <!-- Font Awesome for professional eye icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1f0021f0 0%, #d3321d 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* ── LOGO inside card ── */
        .logo-inside {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo-inside img {
            height: 80px;
            width: auto;
        }

        /* ── CARD ── */
        .reset-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
        }

        .reset-box h2 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }

        .reset-box p {
            color: #666;
            margin-bottom: 25px;
            text-align: center;
        }

        .user-info {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .user-info strong {
            color: #667eea;
        }

        /* ── FORM ── */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        /* Password wrapper for eye icon */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            width: 100%;
            padding: 12px 44px 12px 12px; /* right padding for icon */
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s;
        }

        .password-wrapper input:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Eye toggle button */
        .eye-toggle {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            color: #aaa;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s, transform 0.15s;
            user-select: none;
        }

        .eye-toggle:hover {
            color: #667eea;
            transform: scale(1.15);
        }

        .eye-toggle:active {
            transform: scale(0.95);
        }

        /* Icon states via Font Awesome classes */
        .eye-toggle .eye-icon::before        { content: "\f06e"; } /* fa-eye */
        .eye-toggle .eye-slash-icon::before  { content: "\f070"; } /* fa-eye-slash */

        .eye-icon,
        .eye-slash-icon {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-style: normal;
        }

        /* ── SUBMIT BUTTON ── */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
        }

        .btn-submit:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* ── MESSAGES ── */
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        /* SUCCESS overlay */
        .success-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 999;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .success-overlay.show {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .success-card {
            background: white;
            border-radius: 16px;
            padding: 40px 36px;
            max-width: 380px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            animation: popUp 0.35s cubic-bezier(.175,.885,.32,1.275);
        }

        @keyframes popUp {
            from { transform: scale(0.7); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .success-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .success-icon svg {
            width: 36px;
            height: 36px;
            color: white;
            stroke: white;
        }

        .success-card h3 {
            color: #2d7a4f;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .success-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .btn-login {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            color: #1a5c38;
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none;
            font-size: 15px;
            transition: opacity 0.2s;
        }

        .btn-login:hover {
            opacity: 0.88;
        }

        small {
            color: #666;
        }
    </style>
</head>
<body>

    <!-- ── FORM CARD ── -->
    <div class="reset-box">

        <!-- LOGO dalam card — gantikan "assets/logo.png" dengan path logo anda -->
        <div class="logo-inside">
            <img src="img/Politeknik-Mukah.png"
                 alt="Politeknik Malaysia Logo"
                 onerror="this.style.display='none';">
        </div>

        <h2>🔑 Create New Password</h2>
        <p>Enter your new password below</p>

        <div class="user-info">
            Resetting password for: <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
        </div>

        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-msg">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>

        <form action="process_reset.php" method="POST" onsubmit="return validatePassword()">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <!-- New Password -->
            <div class="form-group">
                <label>New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password"
                           placeholder="Enter new password" required minlength="6">
                    <button type="button" class="eye-toggle" onclick="togglePassword('password')" aria-label="Toggle password visibility">
                        <i id="eyeIcon-password" class="eye-icon"></i>
                    </button>
                </div>
                <small>Minimum 6 characters</small>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password"
                           placeholder="Confirm new password" required>
                    <button type="button" class="eye-toggle" onclick="togglePassword('confirm_password')" aria-label="Toggle confirm password visibility">
                        <i id="eyeIcon-confirm_password" class="eye-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">✓ Reset Password</button>
        </form>
    </div>

    <!-- ── SUCCESS OVERLAY ── -->
    <div class="success-overlay" id="successOverlay">
        <div class="success-card">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                     viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
            </div>
            <h3>Password Reset Successful!</h3>
            <p>Your password has been updated successfully.<br>You can now log in with your new password.</p>
            <a href="login.php" class="btn-login">Go to Login →</a>
        </div>
    </div>

    <!-- Success overlay will only show after process_reset.php redirects back with ?success=1 -->
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('successOverlay').classList.add('show');
        });
    </script>
    <?php endif; ?>

    <script>
    /* ── Eye icon toggle — class-based (professional) ── */
    function togglePassword(fieldId) {
        const passwordInput = document.getElementById(fieldId);
        const eyeIcon       = document.getElementById('eyeIcon-' + fieldId);

        if (passwordInput.type === 'password') {
            passwordInput.type  = 'text';
            eyeIcon.className   = 'eye-slash-icon';
        } else {
            passwordInput.type  = 'password';
            eyeIcon.className   = 'eye-icon';
        }
    }

    /* ── Form validation ── */
    function validatePassword() {
        const password = document.getElementById('password').value;
        const confirm  = document.getElementById('confirm_password').value;

        if (password !== confirm) {
            alert('❌ Passwords do not match!');
            return false;
        }

        if (password.length < 6) {
            alert('❌ Password must be at least 6 characters long!');
            return false;
        }

        return true;
    }
    </script>
</body>
</html>