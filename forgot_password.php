<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMURAS - Forgot Password</title>
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
        
        .forgot-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
        }
        
        .forgot-box h2 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }
        
        .forgot-box p {
            color: #666;
            margin-bottom: 25px;
            text-align: center;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
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
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>

    <div class="forgot-box">

        <!-- LOGO dalam card — gantikan "assets/logo.png" dengan path logo anda -->
        <div class="logo-inside">
            <img src="img/Politeknik-Mukah.png"
                 alt="Politeknik Malaysia Logo"
                 onerror="this.style.display='none';">
        </div>

        <h2>🔒 Forgot Password?</h2>
        <p>No worries! Enter your email address and we'll send you a link to reset your password.</p>
        
        <?php
        // Tunjuk SATU mesej sahaja — success lebih utama dari error
        if (isset($_SESSION['success_message'])):
            $msg = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            unset($_SESSION['error_message']); // buang error jika ada sekali
        ?>
        <div class="success-msg">
            <?php echo $msg; ?>
        </div>

        <?php elseif (isset($_SESSION['error_message'])):
            $msg = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        ?>
        <div class="error-msg">
            <?php echo $msg; ?>
        </div>
        <?php endif; ?>
        
        <form action="send_reset_link.php" method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <button type="submit" class="btn-submit">📧 Send Reset Link</button>
        </form>
        
        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>