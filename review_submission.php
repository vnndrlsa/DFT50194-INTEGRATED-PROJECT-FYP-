<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Load submissions
$submissions_file = 'submissions.json';
$submissions = [];
$user_submissions = [];

if (file_exists($submissions_file)) {
    $all_submissions = json_decode(file_get_contents($submissions_file), true);
    
    // Filter submissions for current user only
    foreach ($all_submissions as $sub) {
        if ($sub['user_id'] === $user_id) {
            $user_submissions[] = $sub;
        }
    }
    
    // Sort by newest first
    $user_submissions = array_reverse($user_submissions);
}

// Handle delete submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_submission'])) {
    $submission_id = $_POST['submission_id'];
    
    // Load all submissions
    $all_submissions = json_decode(file_get_contents($submissions_file), true);
    
    // Find and remove the submission
    foreach ($all_submissions as $key => $sub) {
        if ($sub['id'] == $submission_id && $sub['user_id'] === $user_id) {
            // Delete the document file if exists
            if (!empty($sub['document']) && file_exists($sub['document'])) {
                unlink($sub['document']);
            }
            unset($all_submissions[$key]);
            break;
        }
    }
    
    // Re-index array and save
    $all_submissions = array_values($all_submissions);
    file_put_contents($submissions_file, json_encode($all_submissions, JSON_PRETTY_PRINT));
    
    // Refresh the page
    header("Location: review_submission.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PMURAS - Review Submission</title>

<style>
* { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
}

body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: linear-gradient(135deg, #4a0080 0%, #1a0033 50%, #0a0015 100%);
    min-height: 100vh;
    padding: 20px;
    position: relative;
}

/* Network background pattern */
body::before {
    content: '';
    position: fixed;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    background-image: 
        linear-gradient(rgba(139, 92, 246, 0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(139, 92, 246, 0.1) 1px, transparent 1px);
    background-size: 50px 50px;
    pointer-events: none;
    opacity: 0.3;
}

.container {
    max-width: 1400px;
    margin: 0 auto;
    position: relative;
    z-index: 10;
}

/* Header */
.page-header {
    background: rgba(139, 92, 246, 0.1);
    backdrop-filter: blur(20px);
    border: 2px solid rgba(139, 92, 246, 0.3);
    border-radius: 20px;
    padding: 25px 40px;
    margin-bottom: 30px;
}

.page-header h1 {
    color: white;
    font-size: 32px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 2px;
}

/* Navigation */
.nav-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.nav-links {
    display: flex;
    gap: 15px;
}

.nav-links a {
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    transition: all 0.3s;
    font-size: 14px;
    font-weight: 600;
}

.nav-links a:hover {
    background: rgba(139, 92, 246, 0.3);
    transform: translateY(-2px);
}

/* Submission Card */
.submission-box {
    background: rgba(30, 0, 60, 0.7);
    backdrop-filter: blur(20px);
    border: 2px solid rgba(139, 92, 246, 0.4);
    border-radius: 25px;
    padding: 0;
    overflow: hidden;
}

.submission-title {
    background: rgba(139, 92, 246, 0.2);
    padding: 25px 40px;
    border-bottom: 2px solid rgba(139, 92, 246, 0.3);
}

.submission-title h2 {
    color: white;
    font-size: 28px;
    text-align: center;
    font-weight: 700;
    text-transform: uppercase;
}

/* Table */
.table-container {
    overflow-x: auto;
    padding: 30px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead tr {
    background: rgba(139, 92, 246, 0.2);
    border: 2px solid rgba(139, 92, 246, 0.4);
}

th {
    padding: 18px 20px;
    text-align: left;
    color: white;
    font-weight: 700;
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
    white-space: nowrap;
}

tbody tr {
    border-bottom: 1px solid rgba(139, 92, 246, 0.2);
    transition: all 0.3s;
}

tbody tr:hover {
    background: rgba(139, 92, 246, 0.1);
}

td {
    padding: 20px;
    color: white;
    font-size: 14px;
    vertical-align: middle;
}

.bil-col {
    font-weight: 700;
    color: #8b5cf6;
    font-size: 16px;
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 8px 18px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
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

/* Action buttons */
.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-action {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.btn-view {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.btn-delete {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 80px 40px;
    color: white;
}

.empty-state-icon {
    font-size: 80px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 24px;
    margin-bottom: 10px;
    color: rgba(255, 255, 255, 0.9);
}

.empty-state p {
    font-size: 16px;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 30px;
}

.btn-new-submission {
    display: inline-block;
    padding: 15px 35px;
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 700;
    transition: all 0.3s;
    text-transform: uppercase;
}

.btn-new-submission:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(139, 92, 246, 0.5);
}

/* Responsive */
@media (max-width: 768px) {
    .page-header h1 {
        font-size: 24px;
    }
    
    .nav-bar {
        flex-direction: column;
        gap: 15px;
    }
    
    .nav-links {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .table-container {
        padding: 15px;
    }
    
    th, td {
        padding: 12px 10px;
        font-size: 13px;
    }
    
    .submission-title h2 {
        font-size: 20px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-action {
        width: 100%;
    }
}
</style>
</head>

<body>

<div class="container">
    
    <!-- Header -->
    <div class="page-header">
        <h1>📊 USER - STAFF INTERFACE</h1>
    </div>
    
    <!-- Navigation -->
    <div class="nav-bar">
        <div class="nav-links">
            <a href="dashboard_main.php">Main Page</a>
            <a href="dashboard_recognition.php">New Submission</a>
        </div>
        <div class="nav-links">
            <a href="logout.php" style="background: rgba(239, 68, 68, 0.3);">Logout</a>
        </div>
    </div>
    
    <!-- Submission Box -->
    <div class="submission-box">
        <div class="submission-title">
            <h2>SUBMISSION</h2>
        </div>
        
        <div class="table-container">
            <?php if (empty($user_submissions)): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>No Submissions Yet</h3>
                    <p>You haven't submitted any recognition or achievement yet.</p>
                    <a href="dashboard_recognition.php" class="btn-new-submission">Create New Submission</a>
                </div>
            <?php else: ?>
                <!-- Table -->
                <table>
                    <thead>
                        <tr>
                            <th>Bil.</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Program</th>
                            <th>Level</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $bil = 1; ?>
                        <?php foreach ($user_submissions as $sub): ?>
                            <tr>
                                <td class="bil-col"><?php echo $bil++; ?>.</td>
                                <td><?php echo htmlspecialchars($sub['name']); ?></td>
                                <td><?php echo htmlspecialchars($sub['department']); ?></td>
                                <td><?php echo htmlspecialchars($sub['program_name']); ?></td>
                                <td><?php echo htmlspecialchars($sub['level']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($sub['date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo isset($sub['status']) ? htmlspecialchars($sub['status']) : 'pending'; ?>">
                                        <?php echo isset($sub['status']) ? htmlspecialchars($sub['status']) : 'Pending'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (!empty($sub['document']) && file_exists($sub['document'])): ?>
                                            <button class="btn-action btn-view" onclick="window.open('view_pdf.php?file=<?php echo urlencode($sub['document']); ?>', '_blank')">
                                                📄 View
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if (!isset($sub['status']) || $sub['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this submission?');">
                                                <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($sub['id']); ?>">
                                                <button type="submit" name="delete_submission" class="btn-action btn-delete">
                                                    🗑️ Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
</div>

</body>
</html>