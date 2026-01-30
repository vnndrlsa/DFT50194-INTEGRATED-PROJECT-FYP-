<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
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
    <div class="card">
        <div class="card-content">
            <h2>
                PMURAS<br>
                <span>ENDORSEMENT</span>
            </h2>
            <div class="admin-badge">ADMINISTRATOR ACCESS ONLY</div>
            <p style="opacity: 0.7; font-style: italic;">This section is restricted to administrators only. Please contact your system administrator for access.</p>
        </div>
        
        <button class="enter-btn" style="opacity: 0.5; cursor: not-allowed;" disabled>
            <span class="arrow">»</span>
            <span>ENTER</span>
        </button>
    </div>

</div>

</body>
</html>