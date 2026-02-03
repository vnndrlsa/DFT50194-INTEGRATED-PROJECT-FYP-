<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Admin password - ONLY ADMIN AND DEVELOPER KNOW THIS
define('ADMIN_PASSWORD', 'PMU@dmin2026'); // Change this to your secret password

$error_message = "";

// Handle admin password verification
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_password'])) {
    $entered_password = $_POST['admin_password'];
    
    if ($entered_password === ADMIN_PASSWORD) {
        // Password correct - grant admin access
        $_SESSION['admin_access'] = true;
        header("Location: dashboard_endorsement.php");
        exit();
    } else {
        $error_message = "Incorrect password! Access denied.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PMURAS - Main Dashboard</title>

<style>
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
}

body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: linear-gradient(135deg, #1a0033 0%, #0f2557 50%, #1a4d8f 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

/* Animated background circles */
body::before {
    content: '';
    position: absolute;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(139, 92, 246, 0.3) 0%, transparent 70%);
    border-radius: 50%;
    top: -100px;
    right: -100px;
    animation: float 8s ease-in-out infinite;
}

body::after {
    content: '';
    position: absolute;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.3) 0%, transparent 70%);
    border-radius: 50%;
    bottom: -100px;
    left: -100px;
    animation: float 10s ease-in-out infinite reverse;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) scale(1); }
    50% { transform: translateY(-30px) scale(1.1); }
}

/* Logout button */
.logout-btn {
    position: absolute;
    top: 20px;
    right: 20px;
    padding: 10px 25px;
    background: rgba(220, 38, 38, 0.9);
    color: white;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    z-index: 100;
    backdrop-filter: blur(10px);
}

.logout-btn:hover {
    background: rgba(185, 28, 28, 1);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(220, 38, 38, 0.4);
}

/* User info */
.user-info {
    position: absolute;
    top: 20px;
    left: 20px;
    color: white;
    z-index: 100;
    background: rgba(255, 255, 255, 0.1);
    padding: 10px 20px;
    border-radius: 25px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    max-width: 1200px;
    width: 100%;
    z-index: 10;
    position: relative;
}

.card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 30px;
    padding: 50px 40px;
    cursor: pointer;
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
    min-height: 400px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transform: rotate(45deg);
    transition: all 0.6s;
}

.card:hover::before {
    left: 100%;
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(139, 92, 246, 0.4);
    border-color: rgba(139, 92, 246, 0.6);
    background: rgba(255, 255, 255, 0.08);
}

.card-content {
    position: relative;
    z-index: 2;
}

.card h2 {
    font-size: 42px;
    color: white;
    margin-bottom: 15px;
    font-weight: 700;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.card h2 span {
    display: block;
    background: linear-gradient(135deg, #a855f7, #ec4899);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.card p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 16px;
    line-height: 1.6;
    margin-bottom: 20px;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.card-title-main {
    font-size: 38px;
    font-weight: 700;
    margin-bottom: 30px;
}

.enter-btn {
    display: inline-flex;
    align-items: center;
    gap: 15px;
    padding: 15px 35px;
    background: linear-gradient(135deg, #8b5cf6, #3b82f6);
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    margin-top: auto;
    align-self: flex-start;
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
}

.enter-btn:hover {
    transform: translateX(10px);
    box-shadow: 0 15px 40px rgba(139, 92, 246, 0.6);
}

.enter-btn .arrow {
    font-size: 24px;
    transition: transform 0.3s;
}

.enter-btn:hover .arrow {
    transform: translateX(5px);
}

.admin-badge {
    display: inline-block;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    color: white;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Responsive */
@media (max-width: 768px) {
    .cards-container {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .card h2 {
        font-size: 32px;
    }
    
    .user-info {
        position: static;
        margin-bottom: 20px;
        text-align: center;
    }
    
    .logout-btn {
        position: static;
        display: block;
        width: fit-content;
        margin: 0 auto 20px;
    }
    
    body {
        display: block;
        padding: 20px;
    }
}

/* Password Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
}

.modal.show {
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: linear-gradient(135deg, #1e3a8a 0%, #160047 100%);
    border: 2px solid rgba(139, 92, 246, 0.5);
    border-radius: 25px;
    padding: 40px;
    max-width: 450px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    text-align: center;
    margin-bottom: 30px;
}

.modal-header h2 {
    color: white;
    font-size: 26px;
    margin-bottom: 10px;
}

.modal-header p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 14px;
}

.lock-icon {
    font-size: 60px;
    margin-bottom: 20px;
}

.modal-body {
    margin-bottom: 25px;
}

.modal-body input {
    width: 100%;
    padding: 15px 20px;
    border-radius: 12px;
    border: 2px solid rgba(139, 92, 246, 0.5);
    background: rgba(255, 255, 255, 0.95);
    font-size: 16px;
    transition: all 0.3s;
}

.modal-body input:focus {
    outline: none;
    border-color: #8b5cf6;
    background: white;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.2);
}

.modal-footer {
    display: flex;
    gap: 15px;
}

.modal-btn {
    flex: 1;
    padding: 15px 25px;
    border-radius: 12px;
    border: none;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    text-transform: uppercase;
}

.btn-submit-password {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-cancel {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.modal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.error-modal {
    background: rgba(239, 68, 68, 0.2);
    border: 2px solid #ef4444;
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}
</style>
</head>

<body>

<!-- User Info -->
<div class="user-info">
    <strong>👤 <?php echo htmlspecialchars($full_name); ?></strong>
</div>

<!-- Logout Button -->
<a href="logout.php" class="logout-btn">Logout</a>

<!-- Cards Container -->
<div class="cards-container">
    
    <!-- PMURAS RECOGNITION Card -->
    <div class="card" onclick="window.location.href='dashboard_recognition.php'">
        <div class="card-content">
            <h2>
                PMURAS<br>
                <span>RECOGNITION</span>
            </h2>
            <p>Menyimpan dan memaparkan penghargaan rasmi atau pujian yang diterima daripada pihak lain.</p>
            
            <div style="margin-top: 30px;">
                <h2 class="card-title-main">
                    PMURAS<br>
                    <span>ACHIEVEMENT</span>
                </h2>
                <p>Menyimpan dan memaparkan kejayaan individu atau organisasi hasil usaha mereka.</p>
            </div>
        </div>
        
        <a href="dashboard_recognition.php" class="enter-btn">
            <span class="arrow">»</span>
            <span>ENTER</span>
        </a>
    </div>
    
    <!-- PMURAS ENDORSEMENT Card -->
    <div class="card" onclick="showPasswordModal()">
        <div class="card-content">
            <h2>
                PMURAS<br>
                <span>ENDORSEMENT</span>
            </h2>
            <div class="admin-badge">🔒 PASSWORD PROTECTED</div>
            <p>Administrator access required. Enter the admin password to access the endorsement panel.</p>
        </div>
        
        <button type="button" class="enter-btn" onclick="showPasswordModal(); event.stopPropagation();">
            <span class="arrow">»</span>
            <span>ENTER</span>
        </button>
    </div>

</div>

<!-- Password Modal -->
<div id="passwordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="lock-icon">🔐</div>
            <h2>Admin Access Required</h2>
            <p>Please enter the administrator password</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="error-modal"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="modal-body">
                <input type="password" name="admin_password" id="admin_password" 
                       placeholder="Enter admin password" required autofocus>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="modal-btn btn-cancel" onclick="closePasswordModal()">Cancel</button>
                <button type="submit" class="modal-btn btn-submit-password">Submit</button>
            </div>
        </form>
    </div>
</div>

<script>
function showPasswordModal() {
    document.getElementById('passwordModal').classList.add('show');
    document.getElementById('admin_password').focus();
}

function closePasswordModal() {
    document.getElementById('passwordModal').classList.remove('show');
    document.getElementById('admin_password').value = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('passwordModal');
    if (event.target === modal) {
        closePasswordModal();
    }
}

// Show modal if there's an error message
<?php if ($error_message): ?>
    showPasswordModal();
<?php endif; ?>
</script>

</body>
</html>