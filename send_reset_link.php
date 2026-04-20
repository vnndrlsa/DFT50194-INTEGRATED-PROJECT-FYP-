<?php
session_start();
require 'config.php';

// ✅ IMPORTANT: Include PHPMailer files
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists in database
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Generate unique reset token
        $token = bin2hex(random_bytes(32)); // 64 character random string
        
        // Save token to database
        $user_id = $user['user_id'];

        // Set token expiry to 30 minutes from now
        $update_sql = "UPDATE users SET 
                       reset_token = '$token', 
                       reset_token_expiry = DATE_ADD(NOW(), INTERVAL 30 MINUTE) 
                       WHERE user_id = $user_id";
        mysqli_query($conn, $update_sql);
        
        // ✅ SEND EMAIL USING PHPMAILER
        $mail = new PHPMailer(true);
        
        try {
            // ==========================================
            // SMTP CONFIGURATION
            // ==========================================
            $mail->isSMTP();
            $mail->SMTPDebug  = SMTP::DEBUG_OFF;
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sugayngi@gmail.com';        // ⚠️ CHANGE THIS to your Gmail
            $mail->Password   = 'fyrm gjpr mvuf nprn';       // ⚠️ CHANGE THIS to your App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // ==========================================
            // EMAIL CONTENT
            // ==========================================
            $mail->setFrom('sugayngi@gmail.com', 'PMURAS System');
            $mail->addAddress($email, $user['full_name']);
            
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - PMURAS System';
            
            // Reset link
            $reset_link = "http://localhost/pmuras/reset_password.php?token=$token";
            
            // Email body (HTML)
            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                    .button { display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
                    .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🔒 Password Reset Request</h1>
                    </div>
                    <div class='content'>
                        <p>Hello <strong>{$user['full_name']}</strong>,</p>
                        <p>We received a request to reset your password for your PMURAS account.</p>
                        <p>Click the button below to reset your password:</p>
                        <p style='text-align: center;'>
                            <a href='$reset_link' class='button'>Reset My Password</a>
                        </p>
                        <p><strong>Important:</strong></p>
                        <ul>
                            <li>This link is valid for <strong>30 Minutes</strong> only</li>
                            <li>If you didn't request this, please ignore this email</li>
                            <li>Your password won't change unless you click the link above</li>
                        </ul>
                        <p>If the button doesn't work, copy and paste this link into your browser:</p>
                        <p style='word-break: break-all; color: #667eea;'>$reset_link</p>
                        <div class='footer'>
                            <p>This is an automated email from PMURAS System - Politeknik Mukah</p>
                            <p>Please do not reply to this email</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Plain text version
            $mail->AltBody = "Hello {$user['full_name']},\n\n"
                           . "We received a request to reset your password.\n\n"
                           . "Click this link to reset your password:\n$reset_link\n\n"
                           . "This link is valid for 2 hours only.\n\n"
                           . "If you didn't request this, please ignore this email.\n\n"
                           . "PMURAS System - Politeknik Mukah";
            
            // ✅ SEND EMAIL
            $mail->send();
            
            $_SESSION['success_message'] = '✅ Reset link sent! Please check your email inbox (and spam folder).';
            header("Location: forgot_password.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = "❌ Failed to send email. Error: {$mail->ErrorInfo}";
            header("Location: forgot_password.php");
            exit();
        }
        
    } else {
        // Email not found - don't tell user for security reasons
        $_SESSION['success_message'] = '✅ If that email exists, we sent a reset link to it.';
        header("Location: forgot_password.php");
        exit();
    }
}
?>