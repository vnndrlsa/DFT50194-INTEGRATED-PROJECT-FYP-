<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';
include 'upload_helper.php';   // ← helper folder "document pdf"

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name             = $_POST['name'];
    $department       = $_POST['department'];
    $category         = $_POST['category'];
    $program_name     = trim($_POST['program_name']);
    $type_of_category = $_POST['type_of_category'];
    $date             = $_POST['date'];
    $level            = $_POST['level'];

    $action_type = isset($_POST['action_type']) ? $_POST['action_type'] : 'submit';
    $status      = ($action_type == 'save') ? 'saved' : 'pending';

    // ── STEP 1: INSERT dulu tanpa dokumen (untuk dapat submission_id) ──────
    $document_name = null;
    $document_path = null;

    $sql  = "INSERT INTO submissions 
             (user_id, name, department, category, program_name, type_of_category, 
              date, level, document_name, document_path, status, submitted_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issssssssss",
        $user_id,
        $name,
        $department,
        $category,
        $program_name,
        $type_of_category,
        $date,
        $level,
        $document_name,   // null dulu
        $document_path,   // null dulu
        $status
    );

    if (!mysqli_stmt_execute($stmt)) {
        $_SESSION['error_message'] = "❌ Error: " . mysqli_error($conn);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header("Location: recognition_achievement.php");
        exit();
    }

    $new_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // ── STEP 2: Upload PDF ke folder "document pdf" ────────────────────────
    if (isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {

        $upload = handlePdfUpload($_FILES['document'], $new_id, '');

        if ($upload['success']) {
            // UPDATE path dalam DB
            $upd = mysqli_prepare($conn,
                "UPDATE submissions SET document_name = ?, document_path = ? WHERE submission_id = ?"
            );
            mysqli_stmt_bind_param($upd, "ssi",
                $upload['document_name'],
                $upload['document_path'],
                $new_id
            );
            mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

        } else {
            // Upload gagal — padam submission yang baru dibuat, tunjuk error
            $del = mysqli_prepare($conn, "DELETE FROM submissions WHERE submission_id = ?");
            mysqli_stmt_bind_param($del, "i", $new_id);
            mysqli_stmt_execute($del);
            mysqli_stmt_close($del);

            $_SESSION['error_message'] = "❌ " . $upload['error'];
            mysqli_close($conn);
            header("Location: recognition_achievement.php");
            exit();
        }
    }

    // ── STEP 3: Redirect ───────────────────────────────────────────────────
    if ($status == 'saved') {
        $_SESSION['success_message'] = "✅ Draft saved successfully! You can edit it later.";
    } else {
        $_SESSION['success_message'] = "✅ Submission successful! Waiting for admin review.";
    }

    mysqli_close($conn);
    header("Location: recognition_achievement.php");
    exit();
}

header("Location: recognition_achievement.php");
exit();
?>