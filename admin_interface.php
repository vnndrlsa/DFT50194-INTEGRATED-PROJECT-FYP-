<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'config.php';

$current_page = isset($_GET['page']) ? $_GET['page'] : 'manage_submission';

// Categories hidden
if ($current_page == 'categories') {
    $current_page = 'manage_submission';
}

// Get available years from DB for dropdowns
$years_result = mysqli_query($conn, "SELECT DISTINCT YEAR(submitted_at) as yr FROM submissions WHERE status != 'saved' ORDER BY yr DESC");
$available_years = [];
while ($y = mysqli_fetch_assoc($years_result)) {
    $available_years[] = $y['yr'];
}
if (empty($available_years)) $available_years = [date('Y')];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMURAS - Admin Interface</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1f0021f0 0%, #d3321d 100%);
            min-height: 100vh; padding: 20px;
        }
        .admin-container {
            background: rgba(30, 20, 70, 0.8);
            border-radius: 20px; padding: 40px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1  { color: white; font-size: 32px; }
        .header-links a { color: white; text-decoration: none; margin-left: 20px; }
        .nav-buttons { display: flex; gap: 15px; margin-bottom: 30px; }
        .nav-btn {
            background: #7c3aed; color: white; padding: 15px 30px;
            border: none; border-radius: 10px; cursor: pointer; font-size: 16px;
            text-decoration: none; display: inline-block; transition: all 0.3s; position: relative;
        }
        .nav-btn:hover, .nav-btn.active { background: #871b23; transform: translateY(-2px); }
        .search-bar { background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .search-bar input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .filter-row { display: flex; gap: 15px; margin-top: 15px; }
        .filter-row select { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .content-area { background: rgba(255,255,255,0.95); border-radius: 15px; padding: 30px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: auto; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; white-space: nowrap; }
        table th:nth-child(2), table td:nth-child(2),
        table th:nth-child(3), table td:nth-child(3) { white-space: normal; word-wrap: break-word; }
        table th:nth-child(9), table td:nth-child(9) { white-space: normal; }
        table th { background: #bc1b68; color: white; font-weight: 500; }
        table tr:hover { background: #f5f5f5; }
        .btn { padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 2px; font-size: 14px; }
        .btn-view     { background: #2196F3; color: white; }
        .btn-edit     { background: #7c3aed; color: white; }
        .btn-delete   { background: #f44336; color: white; }
        .btn-approved { background: #4CAF50; color: white; }
        .btn-rejected { background: #f44336; color: white; }
        .btn-active   { background: #8bc34a; color: white; }
        .btn-deactive { background: #ff5722; color: white; }
        .btn-add { background: #bc1b68; color: white; padding: 12px 24px; float: right; margin-bottom: 20px; }
        .modal { position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); }
        .modal-content { background-color: white; margin: 10% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .modal-content h3 { color: #667eea; margin-bottom: 20px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        .modal-content textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; resize: vertical; }
        .status-badge { padding: 5px 10px; border-radius: 5px; font-size: 12px; }
        .status-badge.status-pending  { background: #ff9800; color: white; }
        .status-badge.status-approved { background: #4CAF50; color: white; }
        .status-badge.status-rejected { background: #f44336; color: white; }
        .notification-badge { background: #f44336; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-left: 5px; font-weight: bold; }
        .nav-btn .tooltip {
            visibility: hidden; opacity: 0; position: absolute; bottom: -45px; left: 50%;
            transform: translateX(-50%); background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; padding: 8px 12px; border-radius: 8px; font-size: 12px; white-space: nowrap;
            transition: opacity 0.3s, visibility 0.3s; z-index: 1000; box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        .nav-btn .tooltip::before {
            content: ''; position: absolute; top: -5px; left: 50%; transform: translateX(-50%);
            border-left: 6px solid transparent; border-right: 6px solid transparent; border-bottom: 6px solid #667eea;
        }
        .nav-btn:hover .tooltip { visibility: visible; opacity: 1; }
        .logo-header { display: flex; top: 20px; left: 20px; z-index: 1000; background: transparent; padding: 10px; }
        .logo-img { height: 70px; width: auto; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4)); }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 6px; margin-top: 25px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; }
        .pagination a { background: white; color: #bc1b68; border: 2px solid #bc1b68; cursor: pointer; }
        .pagination a:hover { background: #bc1b68; color: white; }
        .pagination span.current { background: #bc1b68; color: white; border: 2px solid #bc1b68; }
        .pagination span.dots { color: #999; border: none; background: none; }
        .pagination-info { text-align: center; color: #888; font-size: 13px; margin-top: 8px; }

        /* ── Report Cards ── */
        .report-card {
            background: #1e1470; color: white; padding: 18px 22px;
            border-radius: 12px; margin-bottom: 15px; border: 2px solid #7c3aed;
        }
        .report-card h3 { font-size: 17px; margin-bottom: 4px; }
        .report-card > p  { color: #ccc; margin-bottom: 10px; font-size: 13px; }

        /* ── Filter form inside report card ── */
        .report-filter {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 10px;
            display: inline-flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        .report-filter .filter-group {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .report-filter label {
            font-size: 10px;
            color: #ccc;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-filter input[type="date"],
        .report-filter select {
            padding: 6px 10px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            background: rgba(255,255,255,0.12);
            color: white;
            font-size: 13px;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
            width: 160px;
        }
        .report-filter input[type="date"]:focus,
        .report-filter select:focus {
            border-color: #a78bfa;
        }
        .report-filter input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
        .report-filter select option { background: #1e1470; color: white; }

        .btn-generate {
            background: linear-gradient(135deg, #7c3aed, #bc1b68);
            color: white; padding: 8px 22px; border: none;
            border-radius: 7px; cursor: pointer; font-size: 14px;
            font-weight: 700; text-decoration: none; display: inline-block;
            transition: all 0.3s; white-space: nowrap;
        }
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124,58,237,0.5);
        }
        .btn-generate:disabled, .btn-generate.disabled {
            opacity: 0.4; cursor: not-allowed; transform: none; box-shadow: none;
            pointer-events: none;
        }
        .filter-required-note {
            font-size: 12px; color: #f9a8d4; margin-bottom: 8px;
            display: none;
        }
        .filter-required-note.show { display: block; }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .admin-container { padding: 15px; border-radius: 15px; }
            .header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .header h1 { font-size: 24px; }
            .header-links { display: flex; gap: 15px; }
            .header-links a { margin-left: 0; }
            .nav-buttons { flex-direction: column; gap: 10px; }
            .nav-btn { width: 100%; text-align: center; padding: 12px 20px; font-size: 14px; }
            .nav-btn .tooltip { display: none; }
            .search-bar { padding: 10px; }
            .search-bar input { font-size: 16px; }
            .filter-row { flex-direction: column; gap: 10px; }
            .filter-row select { width: 100%; font-size: 16px; }
            .content-area { padding: 15px; border-radius: 10px; }
            .content-area h2 { font-size: 18px; }
            .btn-add { float: none; width: 100%; margin-bottom: 15px; }
            table thead { display: none; }
            table, table tbody, table tr { display: block; width: 100%; }
            table tr { margin-bottom: 15px; border: 1px solid #ddd; border-radius: 10px; padding: 15px; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            table td { display: block; text-align: left !important; padding: 8px 0 !important; border: none !important; white-space: normal !important; }

            /* Manage Submission mobile labels (unchanged) */
            #submissionsTable td:nth-child(1)::before { content: "Bil: ";             font-weight: bold; color: #bc1b68; }
            #submissionsTable td:nth-child(2)::before { content: "Name: ";            font-weight: bold; color: #bc1b68; }
            #submissionsTable td:nth-child(3)::before { content: "Department: ";      font-weight: bold; color: #bc1b68; }
            #submissionsTable td:nth-child(4)::before { content: "Category: ";        font-weight: bold; color: #bc1b68; }
            #submissionsTable td:nth-child(5)::before { content: "Level: ";           font-weight: bold; color: #bc1b68; }
            #submissionsTable td:nth-child(6)::before { content: "Award Date: ";      font-weight: bold; color: #bc1b68; }
            #submissionsTable td:nth-child(7)::before { content: "Submission Date: "; font-weight: bold; color: #bc1b68; }
            #submissionsTable td:nth-child(8)::before { content: "Status: ";          font-weight: bold; color: #bc1b68; }
            #submissionsTable td:nth-child(9)::before { content: "Action: ";          font-weight: bold; color: #bc1b68; display: block; margin-bottom: 10px; }
            #submissionsTable td:last-child { display: flex; flex-direction: column; gap: 8px; padding-top: 10px !important; }
            #submissionsTable td:last-child .btn, #submissionsTable td:last-child a { width: 100%; text-align: center; margin: 0 !important; }

            /* User Management mobile labels — shifted +1 due to new Staff ID column */
            #userTable td:nth-child(1)::before { content: "Bil: ";        font-weight: bold; color: #bc1b68; }
            #userTable td:nth-child(2)::before { content: "Staff ID: ";   font-weight: bold; color: #bc1b68; }
            #userTable td:nth-child(3)::before { content: "Name: ";       font-weight: bold; color: #bc1b68; }
            #userTable td:nth-child(4)::before { content: "Department: "; font-weight: bold; color: #bc1b68; }
            #userTable td:nth-child(5)::before { content: "Action: ";     font-weight: bold; color: #bc1b68; display: block; margin-bottom: 10px; }
            #userTable td:nth-child(6)::before { content: "Status: ";     font-weight: bold; color: #bc1b68; }
            #userTable td:nth-child(5) { display: flex; flex-direction: column; gap: 8px; padding-top: 10px !important; }
            #userTable td:nth-child(5) .btn, #userTable td:nth-child(5) a { width: 100%; text-align: center; margin: 0 !important; }

            .btn { padding: 10px 16px; font-size: 14px; }
            .report-card { padding: 15px; }
            .report-filter { flex-direction: column; display: flex; }
            .report-filter input[type="date"],
            .report-filter select { width: 100%; }
            .user-filter-row { flex-direction: column !important; }
            .user-filter-row input, .user-filter-row select { width: 100% !important; font-size: 16px; }
        }
    </style>
</head>
<body>

<div class="logo-header">
    <img src="img/Politeknik-Mukah.png" alt="Politeknik Malaysia Logo" class="logo-img" onerror="this.style.display='none';">
</div>

<div class="admin-container">
    <div class="header">
        <h1>ADMIN INTERFACE</h1>
        <div class="header-links">
            <a href="mainAdmin_dashboard.php">Main Page</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="nav-buttons">
        <a href="?page=manage_submission" class="nav-btn <?php echo $current_page == 'manage_submission' ? 'active' : ''; ?>">
            Manage Submission
            <?php
            $pending_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM submissions WHERE status = 'pending'");
            $total_pending  = mysqli_fetch_assoc($pending_result)['count'];
            if ($total_pending > 0): ?>
            <span class="notification-badge"><?php echo $total_pending; ?></span>
            <?php endif; ?>
            <span class="tooltip">Review and approve staff submissions</span>
        </a>
        <a href="?page=user_management" class="nav-btn <?php echo $current_page == 'user_management' ? 'active' : ''; ?>">
            User Management
            <?php
            $deactive_nav       = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE status = 'deactive' AND role = 'staff'");
            $deactive_nav_count = mysqli_fetch_assoc($deactive_nav)['count'];
            if ($deactive_nav_count > 0): ?>
            <span class="notification-badge"><?php echo $deactive_nav_count; ?></span>
            <?php endif; ?>
            <span class="tooltip">Manage staff accounts and permissions</span>
        </a>
        <!-- Categories HIDDEN -->
        <a href="?page=reports" class="nav-btn <?php echo $current_page == 'reports' ? 'active' : ''; ?>">
            Reports
            <span class="tooltip">Generate PDF reports and statistics</span>
        </a>
    </div>

    <!-- ===================== MANAGE SUBMISSION ===================== -->
    <?php if ($current_page == 'manage_submission'): ?>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search by name, department..." oninput="subFilterAndPage()">
            <div class="filter-row">
                <select id="categoryFilter" onchange="subFilterAndPage()">
                    <option value="">All PMURAS</option>
                    <option value="recognition">Recognition</option>
                    <option value="achievement">Achievement</option>
                    <option value="others">Others</option>
                </select>
                <select id="departmentFilter" onchange="subFilterAndPage()">
                    <option value="">All Departments</option>
                    <?php
                    $all_departments = [
                        "Jabatan Kejurutetraan Awam","Jabatan Kejuruteraan Elektrik",
                        "Jabatan Kejuruteraan Mekanikal","Jabatan Perdagangan",
                        "Jabatan Teknologi Maklumat dan Komunikasi","Jabatan Matematik, Sains dan Komputer",
                        "Jabatan Pengajian Am","Jabatan Hal Ehwal dan Pembangunan",
                        "Jabatan Sukan, Kokurikulum dan Kebudayaan","Unit Peperiksaan",
                        "Unit Perhubungan dan Latihan Industri","Unit Pengurusan Aset",
                        "Unit Pengurusan Psikologi","Unit Pembangunan Instruksional dan Multimedia",
                        "Unit Latihan dan Pendidikan Lanjutan","Unit Teknologi Maklumat",
                        "Unit Pusat Sumber","Unit Komunikasi Korporat",
                        "Unit Pembangunan dan Penyelenggaraan Fasiliti","Unit Pengurusan Kolej Kediaman",
                        "Unit Khidmat Pengurusan","Unit Kewangan dan Akaun"
                    ];
                    foreach ($all_departments as $dept): ?>
                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="statusFilter" onchange="subFilterAndPage()">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select id="monthFilter" onchange="subFilterAndPage()">
                    <option value="">All Months</option>
                    <option value="01">January</option><option value="02">February</option>
                    <option value="03">March</option><option value="04">April</option>
                    <option value="05">May</option><option value="06">June</option>
                    <option value="07">July</option><option value="08">August</option>
                    <option value="09">September</option><option value="10">October</option>
                    <option value="11">November</option><option value="12">December</option>
                </select>
                <select id="levelFilter" onchange="subFilterAndPage()">
                    <option value="">All Levels</option>
                    <option value="International">International</option>
                    <option value="National">National</option>
                    <option value="State">State</option>
                </select>
            </div>
        </div>

        <div class="content-area">
            <?php if (isset($_SESSION['success_message'])): ?>
            <div style="background:#d4edda;color:#155724;padding:15px;border-radius:10px;margin-bottom:20px;border-left:4px solid #28a745;font-weight:600;">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
            <div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:10px;margin-bottom:20px;border-left:4px solid #dc3545;font-weight:600;">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
            <?php endif; ?>

            <div style="background:#e3f2fd;padding:10px 15px;border-radius:8px;margin-bottom:15px;font-size:13px;color:#1976d2;display:none;" id="scrollHint">
                <strong>💡 Tip:</strong> Scroll horizontally → to view all columns
            </div>

            <table id="submissionsTable">
                <thead>
                    <tr>
                        <th>Bil.</th><th>Name</th><th>Department</th><th>Category</th>
                        <th>Level</th><th>Award Date</th><th>Submission Date</th>
                        <th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "SELECT s.submission_id, s.category, s.level, s.date, s.submitted_at, s.status,
                               u.full_name, u.department
                        FROM submissions s
                        JOIN users u ON s.user_id = u.user_id
                        WHERE s.status != 'saved'
                        ORDER BY s.submitted_at DESC";
                $result = mysqli_query($conn, $sql);
                $bil = 1;
                while ($row = mysqli_fetch_assoc($result)):
                    $cat_val     = (!empty($row['category'])) ? strtolower(trim($row['category'])) : 'others';
                    $cat_display = ucfirst($cat_val);
                ?>
                <tr data-month="<?php echo date('m', strtotime($row['submitted_at'])); ?>"
                    data-level="<?php echo htmlspecialchars($row['level']); ?>"
                    data-name="<?php echo strtolower(htmlspecialchars($row['full_name'])); ?>"
                    data-dept="<?php echo strtolower(htmlspecialchars($row['department'])); ?>"
                    data-category="<?php echo $cat_val; ?>"
                    data-status="<?php echo $row['status']; ?>">
                    <td class="sub-bil-cell"><?php echo $bil++; ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <td><?php echo $cat_display; ?></td>
                    <td><?php echo htmlspecialchars($row['level']); ?></td>
                    <td><?php echo !empty($row['date']) ? date('d/m/Y', strtotime($row['date'])) : '-'; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['submitted_at'])); ?></td>
                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                    <td>
                        <a href="view_submission.php?id=<?php echo $row['submission_id']; ?>" class="btn btn-view">👁</a>
                        <?php if ($row['status'] == 'pending'): ?>
                        <form method="POST" action="approve_submission.php" style="display:inline;">
                            <input type="hidden" name="submission_id" value="<?php echo $row['submission_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-approved" onclick="return confirm('Approve this submission?')">Approve</button>
                        </form>
                        <a href="javascript:void(0)" onclick="showRejectModal(<?php echo $row['submission_id']; ?>)" class="btn btn-rejected">Reject</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <div id="subPaginationWrap">
                <div class="pagination" id="subPagBtns"></div>
                <div class="pagination-info" id="subPagInfo"></div>
            </div>
        </div>

        <div id="rejectModal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close" onclick="closeRejectModal()">&times;</span>
                <h3>Reject Submission</h3>
                <form id="rejectForm" method="POST" action="approve_submission.php">
                    <input type="hidden" id="reject_submission_id" name="submission_id">
                    <input type="hidden" name="action" value="reject">
                    <div class="form-group">
                        <label>Reason for Rejection:</label><br><br>
                        <textarea name="rejection_comment" rows="5" required placeholder="Please provide a clear reason for rejection..."></textarea>
                    </div>
                    <br>
                    <button type="submit" class="btn btn-rejected">Submit Rejection</button>
                    <button type="button" class="btn" onclick="closeRejectModal()">Cancel</button>
                </form>
            </div>
        </div>

        <script>
        const SUB_PER_PAGE = 20;
        let subCurrentPage = 1;
        function getFilteredSubRows() {
            const keyword  = document.getElementById('searchInput').value.trim().toLowerCase();
            const category = document.getElementById('categoryFilter').value.toLowerCase();
            const dept     = document.getElementById('departmentFilter').value.toLowerCase();
            const status   = document.getElementById('statusFilter').value.toLowerCase();
            const month    = document.getElementById('monthFilter').value;
            const level    = document.getElementById('levelFilter').value;
            return Array.from(document.querySelectorAll('#submissionsTable tbody tr')).filter(function(row) {
                if (keyword  && !row.getAttribute('data-name').includes(keyword) && !row.getAttribute('data-dept').includes(keyword)) return false;
                if (category && row.getAttribute('data-category') !== category) return false;
                if (dept     && !row.getAttribute('data-dept').includes(dept)) return false;
                if (status   && row.getAttribute('data-status') !== status) return false;
                if (month    && row.getAttribute('data-month') !== month) return false;
                if (level    && row.getAttribute('data-level') !== level) return false;
                return true;
            });
        }
        function subFilterAndPage() { subCurrentPage = 1; renderSubTable(); }
        function renderSubTable() {
            const filteredRows = getFilteredSubRows();
            const totalRows    = filteredRows.length;
            const totalPages   = Math.max(1, Math.ceil(totalRows / SUB_PER_PAGE));
            if (subCurrentPage > totalPages) subCurrentPage = totalPages;
            const start = (subCurrentPage - 1) * SUB_PER_PAGE;
            const end   = start + SUB_PER_PAGE;
            Array.from(document.querySelectorAll('#submissionsTable tbody tr')).forEach(r => r.style.display = 'none');
            let vi = 0;
            filteredRows.forEach(function(row, idx) {
                if (idx >= start && idx < end) { row.style.display = ''; row.querySelector('.sub-bil-cell').textContent = start + vi + 1; vi++; }
            });
            const showing  = Math.min(end, totalRows);
            const bilStart = totalRows === 0 ? 0 : start + 1;
            const wrap = document.getElementById('subPaginationWrap');
            const info = document.getElementById('subPagInfo');
            if (totalPages <= 1) {
                wrap.style.display = totalRows > 0 ? 'block' : 'none';
                document.getElementById('subPagBtns').innerHTML = '';
                if (totalRows > 0) info.textContent = 'Showing ' + bilStart + '–' + showing + ' of ' + totalRows + ' submission' + (totalRows !== 1 ? 's' : '');
                return;
            }
            wrap.style.display = 'block';
            info.textContent = 'Showing ' + bilStart + '–' + showing + ' of ' + totalRows + ' submission' + (totalRows !== 1 ? 's' : '');
            const pagBtns = document.getElementById('subPagBtns');
            pagBtns.innerHTML = '';
            function makeLink(label, page, isCurrent) {
                if (isCurrent) { const s = document.createElement('span'); s.className = 'current'; s.textContent = label; pagBtns.appendChild(s); }
                else { const a = document.createElement('a'); a.href = 'javascript:void(0)'; a.textContent = label; a.addEventListener('click', function() { subCurrentPage = page; renderSubTable(); document.getElementById('submissionsTable').scrollIntoView({ behavior: 'smooth', block: 'start' }); }); pagBtns.appendChild(a); }
            }
            function makeDots() { const s = document.createElement('span'); s.className = 'dots'; s.textContent = '...'; pagBtns.appendChild(s); }
            if (subCurrentPage > 1) makeLink('← Prev', subCurrentPage - 1, false);
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= subCurrentPage - 2 && i <= subCurrentPage + 2)) makeLink(i, i, i === subCurrentPage);
                else if (i === subCurrentPage - 3 || i === subCurrentPage + 3) makeDots();
            }
            if (subCurrentPage < totalPages) makeLink('Next →', subCurrentPage + 1, false);
        }
        function showRejectModal(id) { document.getElementById('reject_submission_id').value = id; document.getElementById('rejectModal').style.display = 'block'; }
        function closeRejectModal() { document.getElementById('rejectModal').style.display = 'none'; document.getElementById('rejectForm').reset(); }
        window.onclick = function(e) { if (e.target == document.getElementById('rejectModal')) closeRejectModal(); }
        window.addEventListener('load', function() {
            const ca = document.querySelector('.content-area');
            const sh = document.getElementById('scrollHint');
            if (window.innerWidth > 768 && ca && sh && ca.scrollWidth > ca.clientWidth) sh.style.display = 'block';
            renderSubTable();
        });
        </script>

    <!-- ===================== USER MANAGEMENT ===================== -->
    <?php elseif ($current_page == 'user_management'): ?>
        <div class="content-area">
            <a href="add_user.php" class="btn btn-add">+ Add New User</a>
            <h2 style="clear:both;padding-top:20px;">User Management</h2>
            <?php if (isset($_GET['success']) && $_GET['success'] == 'user_deleted'): ?>
            <div style="background:#d4edda;color:#155724;padding:15px;border-radius:10px;margin:20px 0;border-left:4px solid #28a745;font-weight:600;">✅ User deleted successfully!</div>
            <?php elseif (isset($_GET['error'])): ?>
            <div style="background:#f8d7da;color:#721c24;padding:15px;border-radius:10px;margin:20px 0;border-left:4px solid #dc3545;font-weight:600;">
                <?php
                if ($_GET['error'] == 'cannot_delete_self')      echo '❌ You cannot delete your own account!';
                elseif ($_GET['error'] == 'cannot_delete_admin') echo '❌ Cannot delete another admin account!';
                else echo '❌ Error deleting user. Please try again.';
                ?>
            </div>
            <?php endif; ?>
            <?php
            $deactive_info  = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(is_new_registration) as new_reg FROM users WHERE role = 'staff' AND status = 'deactive'");
            $deactive_data  = mysqli_fetch_assoc($deactive_info);
            $total_deactive = $deactive_data['total'];
            $total_new_reg  = $deactive_data['new_reg'] ?? 0;
            if ($total_deactive > 0): ?>
            <div style="background:#fff3cd;padding:15px;border-radius:10px;margin:20px 0;border-left:4px solid #ff9800;">
                <strong>⚠️ Attention:</strong> There <?php echo $total_deactive == 1 ? 'is' : 'are'; ?> <strong><?php echo $total_deactive; ?></strong> inactive account<?php echo $total_deactive > 1 ? 's' : ''; ?>
                <?php if ($total_new_reg > 0): ?>— including <strong><?php echo $total_new_reg; ?> new registration<?php echo $total_new_reg > 1 ? 's' : ''; ?></strong> pending approval.
                <?php else: ?> that require<?php echo $total_deactive == 1 ? 's' : ''; ?> attention.<?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="user-filter-row" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:15px;">
                <div style="position:relative;flex:1;min-width:200px;">
                    <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#aaa;pointer-events:none;">🔍</span>
                    <input type="text" id="userSearchInput" placeholder="Search by name..." oninput="userFilterAndPage()"
                           style="width:100%;padding:10px 12px 10px 35px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none;"
                           onfocus="this.style.borderColor='#bc1b68'" onblur="this.style.borderColor='#ddd'">
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <label style="font-weight:600;color:#333;white-space:nowrap;font-size:14px;">Filter by Status:</label>
                    <select id="userStatusFilter" onchange="userFilterAndPage()" style="padding:10px 16px;border:1px solid #ddd;border-radius:8px;font-size:14px;cursor:pointer;min-width:150px;">
                        <option value="">All Accounts</option>
                        <option value="active">Active</option>
                        <option value="deactive">Inactive</option>
                    </select>
                </div>
                <span id="userCountLabel" style="color:#888;font-size:13px;white-space:nowrap;"></span>
            </div>
            <table id="userTable">
                <thead>
                    <tr>
                        <th>Bil.</th>
                        <th>Staff ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Action</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "SELECT * FROM users WHERE role = 'staff' ORDER BY status ASC, full_name";
                $result = mysqli_query($conn, $sql);
                $bil = 1;
                while ($row = mysqli_fetch_assoc($result)):
                    $isActive = ($row['status'] == 'active');
                ?>
                <tr data-status="<?php echo $row['status']; ?>" data-name="<?php echo strtolower(htmlspecialchars($row['full_name'])); ?>" style="<?php echo $isActive ? '' : 'background:#fff3cd;'; ?>">
                    <td class="bil-cell"><?php echo $bil++; ?></td>
                    <td><?php echo htmlspecialchars($row['staff_id'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?><?php if ($row['is_new_registration'] == 1): ?><span style="background:#ff9800;color:white;padding:2px 8px;border-radius:5px;font-size:11px;margin-left:5px;">NEW</span><?php endif; ?></td>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <td>
                        <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-edit">✎ Edit</a>
                        <a href="delete_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-delete" onclick="return confirm('Delete <?php echo htmlspecialchars(addslashes($row['full_name'])); ?>?\nThis will also delete all their submissions!')">🗑 Delete</a>
                    </td>
                    <td>
                        <a href="toggle_user_status.php?id=<?php echo $row['user_id']; ?>&status=active" class="btn btn-active toggle-btn" style="<?php echo $isActive ? 'display:none;' : ''; ?>" onclick="handleToggle(event, this)">Activate</a>
                        <a href="toggle_user_status.php?id=<?php echo $row['user_id']; ?>&status=deactive" class="btn btn-deactive toggle-btn" style="<?php echo !$isActive ? 'display:none;' : ''; ?>" onclick="handleToggle(event, this)">Deactivate</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <div id="userPaginationWrap"><div class="pagination" id="userPagBtns"></div><div class="pagination-info" id="userPagInfo"></div></div>
        </div>
        <script>
        const USERS_PER_PAGE = 20; let userCurrentPage = 1;
        function getFilteredRows() {
            const keyword = document.getElementById('userSearchInput').value.trim().toLowerCase();
            const status  = document.getElementById('userStatusFilter').value;
            return Array.from(document.querySelectorAll('#userTable tbody tr')).filter(r => (!keyword || r.getAttribute('data-name').includes(keyword)) && (!status || r.getAttribute('data-status') === status));
        }
        function userFilterAndPage() { userCurrentPage = 1; renderUserTable(); }
        function renderUserTable() {
            const filteredRows = getFilteredRows(); const totalRows = filteredRows.length;
            const totalPages = Math.max(1, Math.ceil(totalRows / USERS_PER_PAGE));
            if (userCurrentPage > totalPages) userCurrentPage = totalPages;
            const start = (userCurrentPage - 1) * USERS_PER_PAGE; const end = start + USERS_PER_PAGE;
            Array.from(document.querySelectorAll('#userTable tbody tr')).forEach(r => r.style.display = 'none');
            let vi = 0;
            filteredRows.forEach((row, idx) => { if (idx >= start && idx < end) { row.style.display = ''; row.querySelector('.bil-cell').textContent = start + vi + 1; vi++; } });
            const showing = Math.min(end, totalRows); const bilStart = totalRows === 0 ? 0 : start + 1;
            document.getElementById('userCountLabel').textContent = totalRows === 0 ? 'No records found.' : bilStart + '–' + showing + ' of ' + totalRows + ' user' + (totalRows !== 1 ? 's' : '');
            const wrap = document.getElementById('userPaginationWrap'); const info = document.getElementById('userPagInfo');
            if (totalPages <= 1) { wrap.style.display = totalRows > 0 ? 'block' : 'none'; document.getElementById('userPagBtns').innerHTML = ''; if (totalRows > 0) info.textContent = 'Showing ' + bilStart + '–' + showing + ' of ' + totalRows + ' user' + (totalRows !== 1 ? 's' : ''); return; }
            wrap.style.display = 'block'; info.textContent = 'Showing ' + bilStart + '–' + showing + ' of ' + totalRows + ' user' + (totalRows !== 1 ? 's' : '');
            const pagBtns = document.getElementById('userPagBtns'); pagBtns.innerHTML = '';
            function makeLink(label, page, isCurrent) { if (isCurrent) { const s = document.createElement('span'); s.className = 'current'; s.textContent = label; pagBtns.appendChild(s); } else { const a = document.createElement('a'); a.href = 'javascript:void(0)'; a.textContent = label; a.addEventListener('click', () => { userCurrentPage = page; renderUserTable(); }); pagBtns.appendChild(a); } }
            function makeDots() { const s = document.createElement('span'); s.className = 'dots'; s.textContent = '...'; pagBtns.appendChild(s); }
            if (userCurrentPage > 1) makeLink('← Prev', userCurrentPage - 1, false);
            for (let i = 1; i <= totalPages; i++) { if (i === 1 || i === totalPages || (i >= userCurrentPage - 2 && i <= userCurrentPage + 2)) makeLink(i, i, i === userCurrentPage); else if (i === userCurrentPage - 3 || i === userCurrentPage + 3) makeDots(); }
            if (userCurrentPage < totalPages) makeLink('Next →', userCurrentPage + 1, false);
        }
        function handleToggle(event, clickedBtn) { const td = clickedBtn.closest('td'); td.querySelectorAll('.toggle-btn').forEach(btn => { btn.style.display = (btn === clickedBtn) ? 'none' : 'inline-block'; }); }
        window.addEventListener('DOMContentLoaded', () => renderUserTable());
        </script>

    <!-- ===================== REPORTS ===================== -->
    <?php elseif ($current_page == 'reports'): ?>
        <div class="content-area">
            <h2 style="margin-bottom:25px;">Generate Reports</h2>

            <!-- ── 1. SUMMARY REPORT ── -->
            <div class="report-card">
                <h3>🏆 Summary Report</h3>
                <p>Comprehensive report of all achievements by department and level</p>
                <div class="report-filter">
                    <div class="filter-group">
                        <label>From Date</label>
                        <input type="date" id="sum_from" onchange="checkSummaryFilter()">
                    </div>
                    <div class="filter-group">
                        <label>To Date</label>
                        <input type="date" id="sum_to" onchange="checkSummaryFilter()">
                    </div>
                </div>
                <p class="filter-required-note" id="sum_note">⚠️ Please select both From and To date to generate report.</p>
                <a id="sum_btn" href="#" class="btn-generate disabled" target="_blank">Generate Report</a>
            </div>

            <!-- ── 2. MONTHLY REPORT ── -->
            <div class="report-card">
                <h3>📊 Monthly Statistics</h3>
                <p>Monthly breakdown of submissions and approval rates</p>
                <div class="report-filter">
                    <div class="filter-group">
                        <label>Month</label>
                        <select id="mon_month" onchange="checkMonthlyFilter()">
                            <option value="">Select Month</option>
                            <option value="01">January</option><option value="02">February</option>
                            <option value="03">March</option><option value="04">April</option>
                            <option value="05">May</option><option value="06">June</option>
                            <option value="07">July</option><option value="08">August</option>
                            <option value="09">September</option><option value="10">October</option>
                            <option value="11">November</option><option value="12">December</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Year</label>
                        <select id="mon_year" onchange="checkMonthlyFilter()">
                            <option value="">Select Year</option>
                            <?php foreach ($available_years as $yr): ?>
                            <option value="<?php echo $yr; ?>"><?php echo $yr; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <p class="filter-required-note" id="mon_note">⚠️ Please select both Month and Year to generate report.</p>
                <a id="mon_btn" href="#" class="btn-generate disabled" target="_blank">Generate Report</a>
            </div>

            <!-- ── 3. DEPARTMENT REPORT ── -->
            <div class="report-card">
                <h3>👥 Department Performance</h3>
                <p>View achievements grouped by department</p>
                <div class="report-filter">
                    <div class="filter-group">
                        <label>From Date</label>
                        <input type="date" id="dept_from" onchange="checkDeptFilter()">
                    </div>
                    <div class="filter-group">
                        <label>To Date</label>
                        <input type="date" id="dept_to" onchange="checkDeptFilter()">
                    </div>
                </div>
                <p class="filter-required-note" id="dept_note">⚠️ Please select both From and To date to generate report.</p>
                <a id="dept_btn" href="#" class="btn-generate disabled" target="_blank">Generate Report</a>
            </div>
        </div>

        <script>
        // ── Summary Report ──
        function checkSummaryFilter() {
            const from = document.getElementById('sum_from').value;
            const to   = document.getElementById('sum_to').value;
            const btn  = document.getElementById('sum_btn');
            const note = document.getElementById('sum_note');
            if (from && to) {
                if (from > to) {
                    note.textContent = '⚠️ "From" date cannot be later than "To" date.';
                    note.classList.add('show');
                    btn.classList.add('disabled');
                    btn.href = '#';
                    return;
                }
                note.classList.remove('show');
                btn.classList.remove('disabled');
                btn.href = 'generate_achievement_report.php?from=' + from + '&to=' + to;
            } else {
                note.textContent = '⚠️ Please select both From and To date to generate report.';
                note.classList.add('show');
                btn.classList.add('disabled');
                btn.href = '#';
            }
        }

        // ── Monthly Report ──
        function checkMonthlyFilter() {
            const month = document.getElementById('mon_month').value;
            const year  = document.getElementById('mon_year').value;
            const btn   = document.getElementById('mon_btn');
            const note  = document.getElementById('mon_note');
            if (month && year) {
                note.classList.remove('show');
                btn.classList.remove('disabled');
                btn.href = 'generate_monthly_report.php?month=' + month + '&year=' + year;
            } else {
                note.classList.add('show');
                btn.classList.add('disabled');
                btn.href = '#';
            }
        }

        // ── Department Report ──
        function checkDeptFilter() {
            const from = document.getElementById('dept_from').value;
            const to   = document.getElementById('dept_to').value;
            const btn  = document.getElementById('dept_btn');
            const note = document.getElementById('dept_note');
            if (from && to) {
                if (from > to) {
                    note.textContent = '⚠️ "From" date cannot be later than "To" date.';
                    note.classList.add('show');
                    btn.classList.add('disabled');
                    btn.href = '#';
                    return;
                }
                note.classList.remove('show');
                btn.classList.remove('disabled');
                btn.href = 'generate_department_report.php?from=' + from + '&to=' + to;
            } else {
                note.textContent = '⚠️ Please select both From and To date to generate report.';
                note.classList.add('show');
                btn.classList.add('disabled');
                btn.href = '#';
            }
        }

        // Show notes on load
        window.addEventListener('DOMContentLoaded', function() {
            document.getElementById('sum_note').classList.add('show');
            document.getElementById('mon_note').classList.add('show');
            document.getElementById('dept_note').classList.add('show');
        });
        </script>

    <?php endif; ?>

</div>
</body>
</html>