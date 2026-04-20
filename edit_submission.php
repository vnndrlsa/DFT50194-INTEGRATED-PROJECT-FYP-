<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';
include 'upload_helper.php';

$user_id       = $_SESSION['user_id'];
$is_admin      = ($_SESSION['role'] == 'admin');
$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($submission_id == 0) {
    header("Location: view_my_submissions.php");
    exit();
}

// ✅ Fetch submission — TANPA document_data
$sql  = "SELECT submission_id, user_id, name, department, category, program_name,
                type_of_category, date, level, status, document_name, document_path
         FROM submissions
         WHERE submission_id = ? AND user_id = ? AND status = 'saved'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $submission_id, $user_id);
mysqli_stmt_execute($stmt);
$result     = mysqli_stmt_get_result($stmt);
$submission = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$submission) {
    header("Location: view_my_submissions.php");
    exit();
}

$success_message = "";
$error_message   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category         = $_POST['category'];
    $program_name     = trim($_POST['program_name']);
    $type_of_category = $_POST['type_of_category'];
    $date             = $_POST['date'];
    $level            = $_POST['level'];
    $new_status       = (isset($_POST['action_type']) && $_POST['action_type'] == 'submit')
                        ? 'pending' : 'saved';

    // Kekal fail lama sebagai default
    $document_name = $submission['document_name'];
    $document_path = $submission['document_path'];

    // Upload fail baru kalau ada
    if (isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = handlePdfUpload($_FILES['document'], $submission_id, $submission['document_path']);
        if ($upload['success']) {
            $document_name = $upload['document_name'];
            $document_path = $upload['document_path'];
        } else {
            $error_message = $upload['error'];
        }
    }

    if (empty($error_message)) {
        // ✅ UPDATE — TANPA document_data
        $update_sql = "UPDATE submissions 
                       SET category = ?, program_name = ?, type_of_category = ?,
                           date = ?, level = ?, status = ?,
                           document_name = ?, document_path = ?
                       WHERE submission_id = ? AND user_id = ?";

        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "ssssssssii",
            $category, $program_name, $type_of_category,
            $date, $level, $new_status,
            $document_name, $document_path,
            $submission_id, $user_id
        );

        if (mysqli_stmt_execute($update_stmt)) {
            if ($new_status == 'pending') {
                mysqli_stmt_close($update_stmt);
                mysqli_close($conn);
                header("Location: view_my_submissions.php?submitted=1");
                exit();
            } else {
                $success_message = "✅ Draft saved successfully!";
                // Refresh data — TANPA document_data
                $stmt2 = mysqli_prepare($conn,
                    "SELECT submission_id, user_id, name, department, category, program_name,
                            type_of_category, date, level, status, document_name, document_path
                     FROM submissions WHERE submission_id = ?"
                );
                mysqli_stmt_bind_param($stmt2, "i", $submission_id);
                mysqli_stmt_execute($stmt2);
                $result2    = mysqli_stmt_get_result($stmt2);
                $submission = mysqli_fetch_assoc($result2);
                mysqli_stmt_close($stmt2);
            }
        } else {
            $error_message = "❌ Error saving. Please try again.";
        }
        mysqli_stmt_close($update_stmt);
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMURAS - Edit Submission</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1f0021f0 0%, #d3321d 100%);
            min-height: 100vh; padding: 20px;
        }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-links a { color: white; text-decoration: none; margin-left: 20px; }
        .content-box {
            background: rgba(255,255,255,0.95); border-radius: 15px; padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); max-width: 800px; margin: 0 auto;
        }
        .page-title    { color: #667eea; font-size: 24px; font-weight: 700; margin-bottom: 5px; }
        .page-subtitle { color: #999; font-size: 14px; margin-bottom: 25px; }
        .back-btn {
            display: inline-block; padding: 10px 20px; background: #667eea;
            color: white; text-decoration: none; border-radius: 8px;
            font-weight: 600; font-size: 14px; margin-bottom: 25px; transition: all 0.3s;
        }
        .back-btn:hover { background: #871b23; transform: translateY(-2px); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px; }
        .form-group input,
        .form-group select {
            width: 100%; padding: 12px 15px; border: 2px solid #ddd;
            border-radius: 8px; font-size: 14px; transition: border-color 0.3s; font-family: inherit;
        }
        .form-group input:focus,
        .form-group select:focus { outline: none; border-color: #667eea; }
        .form-group input[readonly] { background: #f5f5f5; color: #888; cursor: not-allowed; }
        .current-file {
            background: #f0f4ff; border: 2px solid #667eea; border-radius: 8px;
            padding: 12px 15px; margin-bottom: 10px;
            display: flex; align-items: center; justify-content: space-between;
            gap: 10px; flex-wrap: wrap;
        }
        .current-file span { color: #667eea; font-weight: 600; font-size: 14px; }
        .btn-view-pdf {
            display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; border: none; border-radius: 6px;
            font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s;
        }
        .btn-view-pdf:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.45); }
        .file-hint { font-size: 12px; color: #999; margin-top: 5px; }
        .btn-row { display: flex; gap: 15px; margin-top: 30px; flex-wrap: wrap; }
        .btn {
            flex: 1; padding: 13px 20px; border: none; border-radius: 8px;
            font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.3s;
            text-align: center; text-decoration: none; display: block; min-width: 120px;
        }
        .btn-save   { background: #4CAF50; color: white; }
        .btn-save:hover   { background: #388E3C; transform: translateY(-2px); }
        .btn-submit { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.5); }
        .btn-cancel { background: #f1f1f1; color: #555; }
        .btn-cancel:hover { background: #ddd; }
        .success-msg {
            background: #d4edda; color: #155724; padding: 14px; border-radius: 8px;
            margin-bottom: 20px; font-weight: 600; border-left: 4px solid #28a745;
        }
        .error-msg {
            background: #f8d7da; color: #721c24; padding: 14px; border-radius: 8px;
            margin-bottom: 20px; font-weight: 600; border-left: 4px solid #dc3545;
        }
        .divider { border: none; border-top: 2px solid #f0f0f0; margin: 20px 0; }
        .logo-header { display: flex; padding: 10px; }
        .logo-img { height: 70px; width: auto; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4)); }
        /* PDF Modal */
        .pdf-modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.75); z-index: 9999;
            justify-content: center; align-items: center;
        }
        .pdf-modal-overlay.active { display: flex; }
        .pdf-modal {
            background: #fff; border-radius: 12px; width: 90vw; max-width: 960px; height: 88vh;
            display: flex; flex-direction: column; overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.45); animation: modalIn 0.25s ease;
        }
        @keyframes modalIn {
            from { transform: scale(0.93); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }
        .pdf-modal-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 20px; background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; flex-shrink: 0;
        }
        .pdf-modal-header h3 {
            font-size: 15px; font-weight: 700; margin: 0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 65%;
        }
        .pdf-modal-actions { display: flex; gap: 10px; align-items: center; }
        .btn-modal-download {
            padding: 6px 14px; background: rgba(255,255,255,0.25); color: white;
            text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 600;
        }
        .btn-modal-download:hover { background: rgba(255,255,255,0.4); }
        .btn-modal-close {
            background: rgba(255,255,255,0.2); border: none; color: white; font-size: 20px;
            cursor: pointer; border-radius: 6px; width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
        }
        .btn-modal-close:hover { background: rgba(255,255,255,0.4); }
        .pdf-modal-body { flex: 1; overflow: hidden; }
        .pdf-modal-body iframe { width: 100%; height: 100%; border: none; display: block; }
    </style>
</head>
<body>

<div class="logo-header">
    <img src="img/Politeknik-Mukah.png" alt="Politeknik Malaysia Logo" class="logo-img"
         onerror="this.style.display='none';">
</div>

<div class="header">
    <div></div>
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

    <a href="view_my_submissions.php" class="back-btn">← Back to My Submissions</a>

    <div class="page-title">✏️ Edit Submission</div>
    <div class="page-subtitle">You can edit your saved draft below. Name and Department cannot be changed.</div>

    <hr class="divider">

    <?php if ($success_message): ?>
        <div class="success-msg"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="error-msg"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-group">
            <label>Name:</label>
            <input type="text" value="<?= htmlspecialchars($submission['name']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Department:</label>
            <input type="text" value="<?= htmlspecialchars($submission['department']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Category:</label>
            <select name="category" required>
                <option value="">Select category</option>
                <?php
                $conn2 = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                $res = mysqli_query($conn2, "SELECT * FROM categories ORDER BY category_name");
                while ($cat = mysqli_fetch_assoc($res)):
                ?>
                <option value="<?= htmlspecialchars($cat['category']) ?>"
                    <?= $submission['category'] == $cat['category'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['category_name']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Program Name:</label>
            <input type="text" name="program_name" required
                   value="<?= htmlspecialchars($submission['program_name']) ?>"
                   placeholder="Enter program name">
        </div>

        <div class="form-group">
            <label>Type of Category:</label>
            <select name="type_of_category" required>
                <option value="">Select type</option>
                <?php
                $res2 = mysqli_query($conn2, "SELECT * FROM category_types ORDER BY category_type_name");
                while ($type = mysqli_fetch_assoc($res2)):
                ?>
                <option value="<?= htmlspecialchars($type['category_type_name']) ?>"
                    <?= $submission['type_of_category'] == $type['category_type_name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type['category_type_name']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Date:</label>
            <input type="date" name="date" required
                   value="<?= htmlspecialchars($submission['date']) ?>">
        </div>

        <div class="form-group">
            <label>Level:</label>
            <select name="level" required>
                <option value="">Select Level</option>
                <?php
                $res3 = mysqli_query($conn2, "SELECT * FROM levels ORDER BY level_name");
                while ($lvl = mysqli_fetch_assoc($res3)):
                ?>
                <option value="<?= htmlspecialchars($lvl['level_name']) ?>"
                    <?= $submission['level'] == $lvl['level_name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($lvl['level_name']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Document Upload: (PDF only, Max 2MB)</label>

            <?php if (!empty($submission['document_name'])): ?>
                <div class="current-file">
                    <span>📄 <?= htmlspecialchars($submission['document_name']) ?></span>
                    <button type="button" class="btn-view-pdf"
                            onclick="openPdfModal(
                                'download_document.php?id=<?= $submission_id ?>',
                                '<?= addslashes(htmlspecialchars($submission['document_name'])) ?>'
                            )">
                        👁️ View PDF
                    </button>
                </div>
                <p class="file-hint">Upload a new file to replace it, or leave empty to keep the current file.</p>
            <?php endif; ?>

            <input type="file" name="document" accept=".pdf" style="margin-top:10px;">
        </div>

        <input type="hidden" name="action_type" id="action_type" value="save">

        <div class="btn-row">
            <a href="view_my_submissions.php" class="btn btn-cancel">✕ Cancel</a>
            <button type="button" class="btn btn-save"   onclick="doAction('save')">💾 Save Draft</button>
            <button type="button" class="btn btn-submit" onclick="doAction('submit')">✓ Submit</button>
        </div>

    </form>
</div>

<!-- PDF Modal -->
<div class="pdf-modal-overlay" id="pdfModalOverlay" onclick="closePdfModalOutside(event)">
    <div class="pdf-modal">
        <div class="pdf-modal-header">
            <h3 id="pdfModalTitle">📄 Document Preview</h3>
            <div class="pdf-modal-actions">
                <a id="pdfDownloadBtn" href="#" download class="btn-modal-download">⬇️ Download</a>
                <button class="btn-modal-close" onclick="closePdfModal()">✕</button>
            </div>
        </div>
        <div class="pdf-modal-body">
            <iframe id="pdfIframe" src="" title="PDF Viewer"></iframe>
        </div>
    </div>
</div>

<script>
function doAction(type) {
    const fields = document.querySelectorAll('[required]');
    let valid = true;
    fields.forEach(f => {
        if (!f.value) { f.style.border = '2px solid red'; valid = false; }
        else { f.style.border = ''; }
    });
    if (!valid) { alert('Please fill in all required fields!'); return; }
    if (type === 'submit' && !confirm('Submit for admin review? You cannot edit after submitting.')) return;
    document.getElementById('action_type').value = type;
    document.querySelector('form').submit();
}

function openPdfModal(url, fileName) {
    document.getElementById('pdfModalTitle').textContent = '📄 ' + fileName;
    document.getElementById('pdfIframe').src = url + '&t=' + Date.now();
    document.getElementById('pdfDownloadBtn').href = url + '&download=1';
    document.getElementById('pdfModalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePdfModal() {
    document.getElementById('pdfModalOverlay').classList.remove('active');
    document.getElementById('pdfIframe').src = '';
    document.body.style.overflow = '';
}

function closePdfModalOutside(e) {
    if (e.target === document.getElementById('pdfModalOverlay')) closePdfModal();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closePdfModal(); });
</script>

</body>
</html>