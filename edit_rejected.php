<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';
include 'upload_helper.php';   // ← guna helper yang sama

$user_id       = $_SESSION['user_id'];
$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($submission_id == 0) {
    header("Location: view_my_submissions.php");
    exit();
}

// Fetch the REJECTED submission - only if belongs to this user and status is 'rejected'
$sql  = "SELECT * FROM submissions WHERE submission_id = ? AND user_id = ? AND status = 'rejected'";
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

    // Keep existing file info by default
    $document_name = $submission['document_name'];
    $document_path = $submission['document_path'];

    $file_uploaded = isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($file_uploaded) {
        // ── Guna handlePdfUpload dari upload_helper.php ──────────────────────
        // Naming jadi: SUB-0001-20260407.pdf (consistent dengan submit_form & edit_submission)
        $upload = handlePdfUpload($_FILES['document'], $submission_id, $submission['document_path']);

        if ($upload['success']) {
            $document_name = $upload['document_name']; // original name (display)
            $document_path = $upload['document_path']; // filename in DB
        } else {
            if ($upload['error'] !== 'no_file') {
                $error_message = "❌ " . $upload['error'];
            }
        }

    } elseif (empty($submission['document_path'])) {
        $error_message = "⚠️ You must upload a PDF document because there is no file currently attached to this submission.";
    }

    if (empty($error_message)) {
        $update_sql = "UPDATE submissions 
                       SET category = ?, program_name = ?, type_of_category = ?, 
                           date = ?, level = ?, status = 'pending',
                           document_name = ?, document_path = ?,
                           rejection_comment = NULL,
                           submitted_at = NOW()
                       WHERE submission_id = ? AND user_id = ?";

        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "sssssssii",
            $category,
            $program_name,
            $type_of_category,
            $date,
            $level,
            $document_name,
            $document_path,
            $submission_id,
            $user_id
        );

        if (mysqli_stmt_execute($update_stmt)) {
            mysqli_stmt_close($update_stmt);
            mysqli_close($conn);
            header("Location: view_my_submissions.php?resubmitted=1");
            exit();
        } else {
            $error_message = "❌ Error updating submission: " . mysqli_error($conn);
        }
        mysqli_stmt_close($update_stmt);
    }
}

// Fetch categories, types, levels
$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");
$types_result      = mysqli_query($conn, "SELECT * FROM category_types ORDER BY category_type_name");
$levels_result     = mysqli_query($conn, "SELECT * FROM levels ORDER BY level_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMURAS - Edit Rejected Submission</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1f0021f0 0%, #d3321d 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: rgba(30, 20, 70, 0.9);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 { color: white; font-size: 28px; }
        .header-links a { color: white; text-decoration: none; margin-left: 20px; }

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
        }

        .form-title {
            color: #bc1b68;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .rejection-notice {
            background: #fff3cd;
            border-left: 4px solid #ff9800;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .rejection-notice h3 { color: #856404; margin-bottom: 10px; font-size: 16px; }
        .rejection-notice p  { color: #856404; font-size: 14px; line-height: 1.6; }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus { outline: none; border-color: #bc1b68; }

        .file-info {
            background: #f0f0f0;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }

        .file-info strong { color: #333; }
        .help-text { font-size: 12px; color: #666; margin-top: 5px; }

        .button-group { display: flex; gap: 15px; margin-top: 30px; }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-submit { background: #4CAF50; color: white; flex: 1; }
        .btn-submit:hover { background: #45a049; transform: translateY(-2px); }
        .btn-cancel { background: #666; color: white; }
        .btn-cancel:hover { background: #555; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error   { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }

        .logo-header { display: flex; padding: 10px; }
        .logo-img    { height: 70px; width: auto; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4)); }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .container { padding: 20px; }
            .form-container { padding: 20px; }
            .header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .header h1 { font-size: 22px; }
            .button-group { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>

<div class="logo-header">
    <img src="img/Politeknik-Mukah.png" alt="Politeknik Malaysia Logo" class="logo-img"
         onerror="this.style.display='none';">
</div>

<div class="container">
    <div class="header">
        <h1>✎ Edit Rejected Submission</h1>
        <div class="header-links">
            <a href="view_my_submissions.php">← Back to My Submissions</a>
        </div>
    </div>

    <div class="form-container">
        <h2 class="form-title">Fix & Resubmit Your Submission</h2>

        <?php if (!empty($submission['rejection_comment'])): ?>
        <div class="rejection-notice">
            <h3>📋 Rejection Reason from Admin:</h3>
            <p><?php echo nl2br(htmlspecialchars($submission['rejection_comment'])); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label>PMURAS Category *</label>
                <select name="category" required>
                    <option value="">Select Category</option>
                    <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                    <option value="<?php echo htmlspecialchars($cat['category_name']); ?>"
                            <?php echo ($submission['category'] == $cat['category_name']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Program Name *</label>
                <input type="text" name="program_name"
                       value="<?php echo htmlspecialchars($submission['program_name']); ?>"
                       required placeholder="Enter program/award name">
            </div>

            <div class="form-group">
                <label>Type of Category *</label>
                <select name="type_of_category" required>
                    <option value="">Select Type</option>
                    <?php while ($type = mysqli_fetch_assoc($types_result)): ?>
                    <option value="<?php echo htmlspecialchars($type['category_type_name']); ?>"
                            <?php echo ($submission['type_of_category'] == $type['category_type_name']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type['category_type_name']); ?>
                    </option>
                    <?php endwhile; ?>
                    <option value="Others" <?php echo ($submission['type_of_category'] == 'Others') ? 'selected' : ''; ?>>Others</option>
                </select>
            </div>

            <div class="form-group">
                <label>Date of Award/Recognition *</label>
                <input type="date" name="date"
                       value="<?php echo htmlspecialchars($submission['date']); ?>"
                       required max="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label>Level *</label>
                <select name="level" required>
                    <option value="">Select Level</option>
                    <?php while ($level = mysqli_fetch_assoc($levels_result)): ?>
                    <option value="<?php echo htmlspecialchars($level['level_name']); ?>"
                            <?php echo ($submission['level'] == $level['level_name']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($level['level_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>
                    Upload Document
                    <?php echo empty($submission['document_path'])
                        ? '(REQUIRED - No file currently attached) *'
                        : '(Optional - leave empty to keep current file)'; ?>
                </label>
                <input type="file" name="document" accept=".pdf"
                       <?php echo empty($submission['document_path']) ? 'required' : ''; ?>>
                <div class="help-text">📄 Only PDF files, max 2MB</div>

                <?php if (!empty($submission['document_name']) && !empty($submission['document_path'])): ?>
                <div class="file-info">
                    <strong>Current file:</strong> <?php echo htmlspecialchars($submission['document_name']); ?>
                    &nbsp;·&nbsp; <em>(uploading a new file will replace this one)</em>
                </div>
                <?php elseif (!empty($submission['document_name']) && empty($submission['document_path'])): ?>
                <div class="file-info" style="background:#fff3cd;border-left:4px solid #ff9800;color:#856404;">
                    <strong>⚠️ Warning:</strong> Current file "<?php echo htmlspecialchars($submission['document_name']); ?>" is missing. You MUST upload a new file.
                </div>
                <?php endif; ?>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-submit">✓ Resubmit for Review</button>
                <a href="view_my_submissions.php" class="btn btn-cancel">Cancel</a>
            </div>

        </form>
    </div>
</div>
</body>
</html>