<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT s.*, u.full_name, u.email, u.staff_id 
        FROM submissions s 
        JOIN users u ON s.user_id = u.user_id 
        WHERE s.submission_id = $submission_id";
$result = mysqli_query($conn, $sql);
$submission = mysqli_fetch_assoc($result);

if (!$submission) {
    header("Location: admin_interface.php");
    exit();
}

// ✅ FIX: Papar 'Others' jika category kosong atau null
$category_display = (!empty($submission['category'])) 
    ? ucfirst(htmlspecialchars($submission['category'])) 
    : 'Others';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submission</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 30px;
        }
        
        .detail-row {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row label {
            font-weight: bold;
            color: #333;
            display: block;
            margin-bottom: 5px;
        }
        
        .detail-row p {
            color: #666;
            font-size: 16px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .status-pending  { background: #ff9800; color: white; }
        .status-approved { background: #4CAF50; color: white; }
        .status-rejected { background: #f44336; color: white; }
        
        .btn-back {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        
        .action-buttons {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            font-size: 16px;
        }
        
        .btn-approve { background: #4CAF50; color: white; }
        .btn-reject  { background: #f44336; color: white; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        .modal-content h3 { color: #f44336; margin-bottom: 20px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        
        .modal-content textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
        }

        /* LOGO */
        .logo-header {
            display: flex;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: transparent;
            padding: 10px;
        }
        .logo-img {
            height: 70px;
            width: auto;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4));
        }   
    </style>
</head>
<body>

<!-- LOGO HEADER -->
<div class="logo-header">
    <img src="img/Politeknik-Mukah.png"
         alt="Politeknik Malaysia Logo"
         class="logo-img"
         onerror="this.style.display='none';">
</div>
    
<div class="container">

    <?php if (isset($_SESSION['success_message'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #28a745; font-weight: 600;">
        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #dc3545; font-weight: 600;">
        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    </div>
    <?php endif; ?>
    
    <h1>Submission Details</h1>
    
    <div class="detail-row">
        <label>Staff ID:</label>
        <p><?php echo htmlspecialchars($submission['staff_id']); ?></p>
    </div>
    
    <div class="detail-row">
        <label>Name:</label>
        <p><?php echo htmlspecialchars($submission['full_name']); ?></p>
    </div>
    
    <div class="detail-row">
        <label>Email:</label>
        <p><?php echo htmlspecialchars($submission['email']); ?></p>
    </div>
    
    <div class="detail-row">
        <label>Department:</label>
        <p><?php echo htmlspecialchars($submission['department']); ?></p>
    </div>
    
    <div class="detail-row">
        <label>Category:</label>
        <!-- ✅ FIX: Papar 'Others' jika kosong -->
        <p><?php echo $category_display; ?></p>
    </div>
    
    <div class="detail-row">
        <label>Program Name:</label>
        <p><?php echo htmlspecialchars($submission['program_name']); ?></p>
    </div>
    
    <div class="detail-row">
        <label>Type of Category:</label>
        <p><?php echo htmlspecialchars($submission['type_of_category']); ?></p>
    </div>
    
    <div class="detail-row">
        <label>Date:</label>
        <p><?php echo !empty($submission['date']) ? date('d/m/Y', strtotime($submission['date'])) : '-'; ?></p>
    </div>
    
    <div class="detail-row">
        <label>Level:</label>
        <p><?php echo htmlspecialchars($submission['level']); ?></p>
    </div>
    
    <div class="detail-row">
        <label>Status:</label>
        <p><span class="status-badge status-<?php echo $submission['status']; ?>">
            <?php echo ucfirst($submission['status']); ?>
        </span></p>
    </div>
    
    <?php if ($submission['status'] == 'rejected' && $submission['rejection_comment']): ?>
    <div class="detail-row" style="background: #fee; padding: 15px; border-radius: 8px; border-left: 4px solid #f44336;">
        <label style="color: #c33;">Rejection Reason:</label>
        <p style="color: #c33; margin-top: 10px;"><?php echo nl2br(htmlspecialchars($submission['rejection_comment'])); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if ($submission['document_name']): ?>
    <div class="detail-row">
        <label>Document:</label>
        <p><a href="download_document.php?id=<?php echo $submission_id; ?>" target="_blank">View Document (<?php echo htmlspecialchars($submission['document_name']); ?>)</a></p>
    </div>
    <?php endif; ?>
    
    <div class="detail-row">
        <label>Submitted At:</label>
        <p><?php echo date('d/m/Y H:i:s', strtotime($submission['submitted_at'])); ?></p>
    </div>
    
    <?php if ($_SESSION['role'] == 'admin' && $submission['status'] == 'pending'): ?>
    <div class="action-buttons">
        <form method="POST" action="approve_submission.php" style="display: inline;">
            <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="from_page" value="view">
            <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this submission?')">Approve</button>
        </form>
        <button type="button" class="btn btn-reject" onclick="showRejectModal()">Reject</button>
    </div>
    <?php endif; ?>
    
    <a href="javascript:history.back()" class="btn-back">← Back</a>
</div>
    
<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeRejectModal()">&times;</span>
        <h3>Reject Submission</h3>
        <form method="POST" action="approve_submission.php">
            <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="from_page" value="view">
            <label>Reason for Rejection: *</label><br><br>
            <textarea name="rejection_comment" required placeholder="Please provide a clear reason..."></textarea>
            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-reject">Submit Rejection</button>
                <button type="button" class="btn btn-back" onclick="closeRejectModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>
    
<script>
function showRejectModal() {
    document.getElementById('rejectModal').style.display = 'block';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('rejectModal');
    if (event.target == modal) closeRejectModal();
}
</script>
</body>
</html>