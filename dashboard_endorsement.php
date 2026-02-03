<?php
session_start();

// Check if user is logged in and has admin access
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if user has entered correct admin password
if (!isset($_SESSION['admin_access']) || $_SESSION['admin_access'] !== true) {
    // Redirect back to main dashboard if no admin access
    header("Location: dashboard_main.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Load submissions
$submissions_file = 'submissions.json';
$submissions = [];

if (file_exists($submissions_file)) {
    $submissions = json_decode(file_get_contents($submissions_file), true);
    // Sort by newest first
    $submissions = array_reverse($submissions);
}

// Handle approval/rejection
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve']) || isset($_POST['reject'])) {
        $submission_id = $_POST['submission_id'];
        $action = isset($_POST['approve']) ? 'approved' : 'rejected';
        
        // Update submission status
        foreach ($submissions as &$sub) {
            if ($sub['id'] == $submission_id) {
                $sub['status'] = $action;
                $sub['reviewed_by'] = $full_name;
                $sub['reviewed_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        
        // Save updated submissions
        file_put_contents($submissions_file, json_encode(array_reverse($submissions), JSON_PRETTY_PRINT));
        $message = "Submission has been " . $action . "!";
        
        // Reload submissions
        $submissions = json_decode(file_get_contents($submissions_file), true);
        $submissions = array_reverse($submissions);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PMURAS - Admin Endorsement</title>

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
    padding: 20px;
}

.header {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 2px solid rgba(139, 92, 246, 0.3);
    border-radius: 20px;
    padding: 25px 40px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h1 {
    color: white;
    font-size: 28px;
}

.header .admin-badge {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 10px 25px;
    border-radius: 25px;
    font-weight: 600;
}

.nav-links {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.nav-links a {
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    transition: all 0.3s;
}

.nav-links a:hover {
    background: rgba(139, 92, 246, 0.3);
}

.message {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 15px 20px;
    border-radius: 15px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}

.submissions-container {
    display: grid;
    gap: 20px;
}

.submission-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 2px solid rgba(139, 92, 246, 0.3);
    border-radius: 20px;
    padding: 30px;
    transition: all 0.3s;
}

.submission-card:hover {
    border-color: rgba(139, 92, 246, 0.6);
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
}

.submission-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid rgba(139, 92, 246, 0.2);
}

.submission-id {
    color: #8b5cf6;
    font-weight: 700;
    font-size: 18px;
}

.status-badge {
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
}

.status-pending {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.status-approved {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.status-rejected {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.submission-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.detail-item {
    color: white;
}

.detail-label {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 5px;
}

.detail-value {
    font-size: 16px;
    font-weight: 600;
}

.submission-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 15px;
}

.btn-approve {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-reject {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.btn-view {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.no-submissions {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border: 2px solid rgba(139, 92, 246, 0.3);
    border-radius: 20px;
    padding: 60px;
    text-align: center;
    color: white;
}

.no-submissions h2 {
    font-size: 24px;
    margin-bottom: 10px;
}

.no-submissions p {
    color: rgba(255, 255, 255, 0.7);
}

@media (max-width: 768px) {
    .header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .submission-details {
        grid-template-columns: 1fr;
    }
    
    .submission-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}
</style>
</head>

<body>

<!-- Header -->
<div class="header">
    <h1>🛡️ Admin Endorsement Panel</h1>
    <div class="admin-badge">ADMIN: <?php echo htmlspecialchars($full_name); ?></div>
</div>

<!-- Navigation -->
<div class="nav-links">
    <a href="dashboard_main.php">← Back to Main Dashboard</a>
    <a href="logout_admin.php" style="background: rgba(239, 68, 68, 0.3);">🔒 Exit Admin Mode</a>
    <a href="logout.php">Logout</a>
</div>

<?php if ($message): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Submissions -->
<div class="submissions-container">
    <?php if (empty($submissions)): ?>
        <div class="no-submissions">
            <h2>No Submissions Yet</h2>
            <p>There are currently no recognition or achievement submissions to review.</p>
        </div>
    <?php else: ?>
        <?php foreach ($submissions as $sub): ?>
            <div class="submission-card">
                <div class="submission-header">
                    <div class="submission-id">ID: #<?php echo htmlspecialchars($sub['id']); ?></div>
                    <div class="status-badge status-<?php echo isset($sub['status']) ? htmlspecialchars($sub['status']) : 'pending'; ?>">
                        <?php echo isset($sub['status']) ? htmlspecialchars($sub['status']) : 'pending'; ?>
                    </div>
                </div>
                
                <div class="submission-details">
                    <div class="detail-item">
                        <div class="detail-label">Submitted By</div>
                        <div class="detail-value"><?php echo htmlspecialchars($sub['submitted_by']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($sub['name']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Department</div>
                        <div class="detail-value"><?php echo htmlspecialchars($sub['department']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Category</div>
                        <div class="detail-value"><?php echo htmlspecialchars($sub['category']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Program Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($sub['program_name']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Recognition Type</div>
                        <div class="detail-value"><?php echo htmlspecialchars($sub['recognition_type']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Date</div>
                        <div class="detail-value"><?php echo htmlspecialchars($sub['date']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Level</div>
                        <div class="detail-value"><?php echo htmlspecialchars($sub['level']); ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Submitted At</div>
                        <div class="detail-value"><?php echo htmlspecialchars($sub['submitted_at']); ?></div>
                    </div>
                </div>
                
                <?php if (isset($sub['status']) && $sub['status'] !== 'pending'): ?>
                    <div class="detail-item" style="margin-top: 15px; padding-top: 15px; border-top: 2px solid rgba(139, 92, 246, 0.2);">
                        <div class="detail-label">Reviewed By</div>
                        <div class="detail-value"><?php echo htmlspecialchars($sub['reviewed_by']); ?> on <?php echo htmlspecialchars($sub['reviewed_at']); ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="submission-actions">
                    <?php if (!empty($sub['document']) && file_exists($sub['document'])): ?>
                        <button class="btn btn-view" onclick="window.open('view_pdf.php?file=<?php echo urlencode($sub['document']); ?>', '_blank')">📄 View Document</button>
                    <?php endif; ?>
                    
                    <?php if (!isset($sub['status']) || $sub['status'] === 'pending'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($sub['id']); ?>">
                            <button type="submit" name="approve" class="btn btn-approve" onclick="return confirm('Approve this submission?')">✓ Approve</button>
                        </form>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($sub['id']); ?>">
                            <button type="submit" name="reject" class="btn btn-reject" onclick="return confirm('Reject this submission?')">✗ Reject</button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-approve" disabled>Already Reviewed</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>