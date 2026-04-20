<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';
$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] == 'admin');

// Handle resubmitted success message
if (isset($_GET['resubmitted']) && $_GET['resubmitted'] == 1) {
    $_SESSION['success_message'] = "✅ Submission resubmitted successfully! It's now pending admin review.";
}

// ✅ Pagination setup
$per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMURAS - My Submissions</title>
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
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
        
        .content-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .content-box h2 {
            color: #061554;
            margin-bottom: 20px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 10px 20px;
            border: 2px solid #bc1b68;
            background: white;
            color: #bc1b68;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .filter-tab:hover,
        .filter-tab.active {
            background: #bc1b68;
            color: white;
        }
        
        .submission-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .submission-table th,
        .submission-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .submission-table th {
            background: #bc1b68;
            color: white;
            font-weight: 500;
        }
        
        .submission-table tr:hover {
            background: #f5f5f5;
        }
        
        .status-pending {
            background: #00b7e0;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .status-approved {
            background: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .status-rejected {
            background: #f44336;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }

        .status-saved {
            background: #ff9800;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-view {
            background: #2196F3;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
            margin: 2px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        @keyframes popIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        /* ── Rejection Modal ── */
        #rejectionModal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        #rejectionModal.show {
            display: flex;
        }

        #rejectionModal .modal-inner {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: popIn 0.3s ease;
        }

        /* ✅ PAGINATION */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .pagination a {
            background: white;
            color: #bc1b68;
            border: 2px solid #bc1b68;
        }

        .pagination a:hover {
            background: #bc1b68;
            color: white;
        }

        .pagination span.current {
            background: #bc1b68;
            color: white;
            border: 2px solid #bc1b68;
        }

        .pagination span.dots {
            color: #999;
            border: none;
            background: none;
        }

        .pagination-info {
            text-align: center;
            color: #888;
            font-size: 13px;
            margin-top: 8px;
        }
        
        /* ✅ MOBILE RESPONSIVE */
        @media (max-width: 768px) {
            body { padding: 10px; }
            
            .header {
                flex-direction: column;
                gap: 15px;
                margin-bottom: 15px;
            }
            
            .header h1 {
                font-size: 24px;
                text-align: center;
            }
            
            .header-links {
                display: flex;
                gap: 15px;
                justify-content: center;
            }
            
            .header-links a {
                margin-left: 0;
                font-size: 14px;
            }
            
            .content-box {
                padding: 15px;
                border-radius: 10px;
            }
            
            .content-box h2 {
                font-size: 18px;
                margin-top: 15px !important;
            }
            
            .content-box > div[style*="display: flex"] {
                flex-direction: column !important;
                gap: 15px !important;
            }
            
            .filter-tabs {
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
            }
            
            .filter-tab {
                padding: 8px 15px;
                font-size: 13px;
            }
            
            .content-box a[href="recognition_achievement.php"] {
                width: 100% !important;
                text-align: center !important;
                padding: 12px !important;
            }
            
            .submission-table { border: 0; }
            .submission-table thead { display: none; }
            .submission-table tbody { display: block; }
            
            .submission-table tr {
                display: block;
                margin-bottom: 20px;
                border: 2px solid #bc1b68;
                border-radius: 10px;
                padding: 15px;
                background: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .submission-table td {
                display: block;
                text-align: left;
                padding: 8px 0;
                border: none;
                position: relative;
                padding-left: 50%;
            }
            
            .submission-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 0;
                width: 45%;
                font-weight: bold;
                color: #bc1b68;
            }
            
            .submission-table td:first-child {
                font-size: 20px;
                font-weight: bold;
                color: #bc1b68;
                text-align: center;
                padding-left: 0;
                margin-bottom: 10px;
                border-bottom: 2px solid #f0f0f0;
                padding-bottom: 10px;
            }
            
            .submission-table td:first-child::before {
                content: "# ";
                position: static;
            }
            
            .submission-table td:last-child {
                padding-left: 0;
                margin-top: 15px;
                padding-top: 15px;
                border-top: 2px solid #f0f0f0;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            
            .submission-table td:last-child::before { display: none; }
            
            .btn-view {
                width: 100%;
                text-align: center;
                padding: 10px;
                margin: 0;
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
    <div class="header">
        <h1>MY SUBMISSIONS</h1>
        <div class="header-links">
            <?php if ($is_admin): ?>
                <a href="mainAdmin_dashboard.php">Main Page</a>
            <?php else: ?>
                <a href="staff_dashboard.php">Main Page</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="content-box">
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
        
        <h2 style="margin-top: 30px;">All Submissions</h2>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <!-- ✅ ADDED: Others tab -->
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=recognition" class="filter-tab <?php echo $filter == 'recognition' ? 'active' : ''; ?>">Recognition</a>
                <a href="?filter=achievement" class="filter-tab <?php echo $filter == 'achievement' ? 'active' : ''; ?>">Achievement</a>
                <a href="?filter=others" class="filter-tab <?php echo $filter == 'others' ? 'active' : ''; ?>">Others</a>
            </div>
            
            <a href="recognition_achievement.php" style="
                display: inline-block;
                padding: 12px 24px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 600;
                font-size: 14px;
                transition: all 0.3s;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(102, 126, 234, 0.5)'" 
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(102, 126, 234, 0.3)'">
                ➕ Add New Submission
            </a>
        </div>
        
        <?php
        // ✅ Build WHERE clause
        $where = "WHERE user_id = $user_id";

        if ($filter == 'recognition') {
            $where .= " AND LOWER(category) LIKE '%recognition%'";
        } elseif ($filter == 'achievement') {
            $where .= " AND LOWER(category) LIKE '%achievement%'";
        } elseif ($filter == 'others') {
            // Others = not recognition and not achievement
            $where .= " AND LOWER(category) NOT LIKE '%recognition%' AND LOWER(category) NOT LIKE '%achievement%'";
        } elseif ($filter == 'pending' || $filter == 'approved' || $filter == 'rejected') {
            $where .= " AND status = '$filter'";
        }

        // ✅ Count total for pagination
        $count_sql = "SELECT COUNT(*) as total FROM submissions $where";
        $count_result = mysqli_query($conn, $count_sql);
        $count_row = mysqli_fetch_assoc($count_result);
        $total_records = $count_row['total'];
        $total_pages = ceil($total_records / $per_page);
        if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
        $offset = ($page - 1) * $per_page;

        // ✅ Fetch with pagination
        $sql = "SELECT * FROM submissions $where ORDER BY submitted_at DESC LIMIT $per_page OFFSET $offset";
        $result = mysqli_query($conn, $sql);
        $bil_start = $offset + 1;
        $bil = $bil_start;
        ?>

        <table class="submission-table">
            <thead>
                <tr>
                    <th>Bil.</th>
                    <th>Category</th>
                    <th>Program Name</th>
                    <th>Type</th>
                    <th>Level</th>
                    <th>Date</th>
                    <th>Submitted At</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0):
                    while ($row = mysqli_fetch_assoc($result)):
                        // ✅ FIX: Display category properly — fallback to "Others" if empty/null
                        $cat_raw = trim($row['category']);
                        if (empty($cat_raw)) {
                            $cat_display = 'Others';
                        } else {
                            $cat_display = ucfirst(htmlspecialchars($cat_raw));
                        }
                ?>
                <tr>
                    <td data-label="Bil."><?php echo $bil++; ?></td>
                    <td data-label="Category"><?php echo $cat_display; ?></td>
                    <td data-label="Program"><?php echo htmlspecialchars($row['program_name']); ?></td>
                    <td data-label="Type"><?php echo htmlspecialchars($row['type_of_category']); ?></td>
                    <td data-label="Level"><?php echo htmlspecialchars($row['level']); ?></td>
                    <td data-label="Date"><?php echo date('d/m/Y', strtotime($row['date'])); ?></td>
                    <td data-label="Submitted"><?php echo date('d/m/Y H:i', strtotime($row['submitted_at'])); ?></td>
                    <td data-label="Status">
                        <?php $status = !empty($row['status']) ? $row['status'] : 'saved'; ?>
                        <span class="status-<?php echo $status; ?>">
                            <?php 
                            if ($status == 'saved') echo 'Saved';
                            elseif ($status == 'pending') echo 'Submitted';
                            else echo ucfirst($status); 
                            ?>
                        </span>
                        <?php if ($status == 'rejected' && !empty($row['rejection_comment'])): ?>
                        <br>
                        <small style="color: #c33; cursor: pointer; text-decoration: underline;"
                               data-reason="<?php echo htmlspecialchars($row['rejection_comment'], ENT_QUOTES); ?>"
                               onclick="showRejectionReason(this.getAttribute('data-reason'))">
                            ℹ️ View reason
                        </small>
                        <?php endif; ?>
                    </td>
                    <td data-label="Action">
                        <?php if ($status == 'saved'): ?>
                            <a href="edit_submission.php?id=<?php echo $row['submission_id']; ?>" 
                               class="btn-view" style="background:#ff9800;">✎ Edit</a>
                            <a href="delete_submission.php?id=<?php echo $row['submission_id']; ?>" 
                               class="btn-view" style="background:#f44336;"
                               onclick="return confirm('Are you sure you want to delete this draft?')">🗑 Delete</a>
                            <a href="submit_saved.php?id=<?php echo $row['submission_id']; ?>" 
                               class="btn-view" style="background:#4CAF50;"
                               onclick="return confirm('Submit this for admin review?')">✓ Submit</a>
                        <?php elseif ($status == 'rejected'): ?>
                            <a href="edit_rejected.php?id=<?php echo $row['submission_id']; ?>" 
                               class="btn-view" style="background:#ff9800;">✎ Edit</a>
                        <?php endif; ?>
                        <a href="view_submission.php?id=<?php echo $row['submission_id']; ?>" 
                           class="btn-view" style="background:#2196F3;">👁 View</a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="10" class="no-data">No submissions found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ✅ PAGINATION -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">&#8592; Prev</a>
            <?php endif; ?>

            <?php
            // Show page numbers with smart ellipsis
            for ($i = 1; $i <= $total_pages; $i++):
                if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)):
                    if ($i == $page):
            ?>
                    <span class="current"><?php echo $i; ?></span>
            <?php   else: ?>
                    <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php   endif;
                elseif ($i == $page - 3 || $i == $page + 3):
            ?>
                    <span class="dots">...</span>
            <?php   endif;
            endfor;
            ?>

            <?php if ($page < $total_pages): ?>
                <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">Next &#8594;</a>
            <?php endif; ?>
        </div>
        <div class="pagination-info">
            Showing <?php echo $bil_start; ?>–<?php echo min($bil_start + $per_page - 1, $total_records); ?> of <?php echo $total_records; ?> submissions
        </div>
        <?php endif; ?>

    </div>
    
    <!-- ── REJECTION REASON MODAL ── -->
    <div id="rejectionModal">
        <div class="modal-inner">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 60px; margin-bottom: 10px;">❌</div>
                <h2 style="color: #f44336; font-size: 24px;">Rejection Reason</h2>
            </div>
            <div id="rejectionText" style="background: #fff3cd; border-left: 4px solid #f44336; padding: 15px; border-radius: 5px; color: #333; font-size: 15px; line-height: 1.6; margin-bottom: 25px; max-height: 300px; overflow-y: auto;">
            </div>
            <button onclick="closeRejectionModal()" style="width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                OK, I Understand
            </button>
        </div>
    </div>
    
    <script>
    function showRejectionReason(reason) {
        document.getElementById('rejectionText').textContent = reason;
        document.getElementById('rejectionModal').classList.add('show');
    }
    
    function closeRejectionModal() {
        document.getElementById('rejectionModal').classList.remove('show');
    }
    
    document.getElementById('rejectionModal').addEventListener('click', function(e) {
        if (e.target === this) closeRejectionModal();
    });
    </script>
</body>
</html>