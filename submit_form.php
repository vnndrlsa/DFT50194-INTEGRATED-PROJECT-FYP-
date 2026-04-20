<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';
include 'upload_helper.php';   // ← helper folder "document pdf"

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id          = $_SESSION['user_id'];
    $name             = mysqli_real_escape_string($conn, $_POST['name']);
    $department       = mysqli_real_escape_string($conn, $_POST['department']);
    $category         = mysqli_real_escape_string($conn, $_POST['category']);
    $program_name     = mysqli_real_escape_string($conn, $_POST['program_name']);
    $type_of_category = mysqli_real_escape_string($conn, $_POST['type_of_category']);
    $date             = mysqli_real_escape_string($conn, $_POST['date']);
    $level            = mysqli_real_escape_string($conn, $_POST['level']);

    // Tentukan action & status
    if (isset($_POST['save'])) {
        $action = 'save';
        $status = 'saved';
    } elseif (isset($_POST['submit'])) {
        $action = 'submit';
        $status = 'pending';
    } else {
        $action = 'save';
        $status = 'saved';
    }

    // ── Handle file upload ──────────────────────────────────────────────────
    // Kita perlu submission_id dulu untuk nama fail yang unik.
    // Jadi kita INSERT dulu tanpa fail, pastu upload fail, pastu UPDATE path.

    $document_name = '';
    $document_path = '';

    // INSERT submission tanpa document path dulu
    $stmt = $conn->prepare(
        "INSERT INTO submissions 
         (user_id, name, department, category, program_name, type_of_category, 
          date, level, document_name, document_path, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("issssssssss",
        $user_id,
        $name,
        $department,
        $category,
        $program_name,
        $type_of_category,
        $date,
        $level,
        $document_name,   // kosong dulu
        $document_path,   // kosong dulu
        $status
    );

    if (!$stmt->execute()) {
        $_SESSION['error_message'] = "❌ Submission failed: " . $stmt->error;
        $stmt->close();
        header("Location: recognition_achievement.php");
        exit();
    }

    $new_submission_id = $conn->insert_id;
    $stmt->close();

    // ── Upload fail PDF sekarang (dah ada submission_id) ───────────────────
    if (isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {

        $upload = handlePdfUpload($_FILES['document'], $new_submission_id, '');

        if ($upload['success']) {
            // UPDATE path dalam DB
            $upd = $conn->prepare(
                "UPDATE submissions 
                 SET document_name = ?, document_path = ? 
                 WHERE submission_id = ?"
            );
            $upd->bind_param("ssi",
                $upload['document_name'],
                $upload['document_path'],
                $new_submission_id
            );
            $upd->execute();
            $upd->close();

        } else {
            // Fail ada tapi gagal upload — padam submission yang baru dibuat
            $del = $conn->prepare("DELETE FROM submissions WHERE submission_id = ?");
            $del->bind_param("i", $new_submission_id);
            $del->execute();
            $del->close();

            $_SESSION['error_message'] = "❌ " . $upload['error'];
            header("Location: recognition_achievement.php");
            exit();
        }
    }

    // ── Redirect mengikut action ────────────────────────────────────────────
    if ($action == 'save') {
        $_SESSION['success_message'] = "✅ Submission saved successfully! You can continue adding more submissions.";
        $_SESSION['alert_type']      = 'save';
        header("Location: recognition_achievement.php");
    } else {
        $_SESSION['success_message'] = "✅ Submission submitted successfully!";
        $_SESSION['alert_type']      = 'submit';
        if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
            header("Location: mainAdmin_dashboard.php");
        } else {
            header("Location: staff_dashboard.php");
        }
    }

    exit();
}
?>