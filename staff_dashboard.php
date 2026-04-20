<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: login.php");
    exit();
}

// ✅ SECURITY: Prevent browser cache dashboard
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

include 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMURAS - Staff Dashboard</title>
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
        }
        
        .container {
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: white;
            font-size: 32px;
        }
        
        .header-links {
            color: white;
        }
        
        .header-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        
        .welcome-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .welcome-box h2 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .dashboard-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            /* ✅ ADDED: Equal height cards */
            display: flex;
            flex-direction: column;
            min-height: 380px;
        }
        
        .dashboard-card h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        .dashboard-card h4 {
            color: #333;
            margin-top: 15px;
            margin-bottom: 8px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .dashboard-card p {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
            font-size: 14px;
        }

        .view-submissions-list {
            list-style: none;
            margin: 15px 0;
        }
        
        .view-submissions-list  li {
            padding: 12px 0;
            padding-left: 25px;
            font-size: 15px;
            color: #555;
            position: relative;
            border-bottom: 1px solid #e8e8e8;
        }
        
        .view-submissions-list  li:last-child {
            border-bottom: none;
        }
        
        .view-submissions-list li::before {
            content: "▸";
            position: absolute;
            left: 5px;
            color: #667eea;
            font-weight: bold;
            font-size: 16px;
        }
        
        /* ✅ ADDED: Content wrapper */
        .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .btn-enter {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            text-align: center;
            margin-top: auto; /* ✅ Push button to bottom */
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-enter:hover {
            background: #871b23;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

       /* ── LOGO ── */
        .logo-header {
            display: flex;
            position: center;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: transparent;
            padding: 10px;
        }

        /* ✏️ Tukar height ikut saiz logo anda */
        .logo-img {
            height: 70px;
            width: auto;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4));
    }   
        
    </style>
</head>
<body>

<!-- ── LOGO HEADER ── -->
    <div class="logo-header">
        <img src="img/Politeknik-Mukah.png"
             alt="Politeknik Malaysia Logo"
             class="logo-img"
             onerror="this.style.display='none';">
    </div>

    
    <div class="container">
        <div class="header">
            <h1>STAFF DASHBOARD</h1>
            <div class="header-links">
                <a href="logout.php">Logout</a>
            </div>
        </div>
        
        <div class="welcome-box">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
            <p>Department: <?php echo htmlspecialchars($_SESSION['department']); ?></p>
            <p>Staff ID: <?php echo htmlspecialchars($_SESSION['staff_id']); ?></p>
        </div>
        
        <div class="dashboard-grid">
            <!-- PMURAS Submission -->
            <div class="dashboard-card">
                <h2>PMURAS<br>RECOGNITION & ACHIEVEMENT</h2>
                <div class="card-content">
                   <div style="overflow-y: auto;">
                        <h3>RECOGNITION</h3>
                        <p>Formal acknowledgement or appreciation given to staff or institution for their contribution, service, or excellence.</p>
                        <p style="font-size: 13px; color: #3d3b3b; margin-top: 5px; line-height: 1.4;">
                            <strong>Examples:</strong> Certificate of Appreciation, Panel/Expert Recognition, Official Acknowledgment from Institutions
                        </p>
                        <p><strong>Purpose in the system:</strong> To record institutional or professional recognition received by staff or institution.</p>
                        
                        <h3 style="margin-top: 18px;">ACHIEVEMENT</h3>
                        <p>Success or accomplishment obtained through competitions, innovation, research, or professional activities.</p>
                        <p style="font-size: 13px; color: #3d3b3b; margin-top: 5px; line-height: 1.4;">
                            <strong>Examples:</strong> Gold/Silver/Bronze Medals, Innovation Awards, Research Awards, Publications, Competition Wins
                        </p>
                        <p><strong>Purpose in the system:</strong> To record measurable accomplishments or competitive success achieved by staff or institution.</p>
                        
                        <h3 style="margin-top: 18px;">OTHERS</h3>
                        <p>Professional contributions that support staff development and institutional reputation.</p>
                        <p style="font-size: 13px; color: #3d3b3b; margin-top: 5px; line-height: 1.4;">
                            <strong>Examples:</strong> Keynote Speaker, Panel Evaluator/Judge, Professional Certification, International Event Participation
                        </p>
                        <p><strong>Purpose in the system:</strong> To capture additional professional contributions that support staff development and institutional reputation.</p>
                    </div>
                    <a href="recognition_achievement.php" class="btn-enter" style="margin-top: 15px;">≫ Add New Submission</a>
                </div>
            </div>
            
            <!-- View Submissions -->
            <div class="dashboard-card">
                <h2>VIEW<br>SUBMISSIONS</h2>
                <div class="card-content">
                    <ul class="view-submissions-list">
                        <li>Allows users to view all recognitions or achievements.</li>
                        <li>Allows users to track the status of their submissions, view feedback, and ensure records are complete.</li>
                    </ul>
                    <a href="view_my_submissions.php" class="btn-enter">≫ View My Submissions</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>