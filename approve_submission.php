<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'config.php';

$submission_id = isset($_POST['submission_id']) ? intval($_POST['submission_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$from_page = isset($_POST['from_page']) ? $_POST['from_page'] : 'admin'; // Track where request came from

if ($submission_id == 0 || empty($action)) {
    header("Location: admin_interface.php?page=manage_submission");
    exit();
}

if ($action == 'approve') {
    // Approve submission
    $sql = "UPDATE submissions 
            SET status = 'approved', 
                rejection_comment = NULL 
            WHERE submission_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $submission_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "✅ Submission approved successfully!";
    } else {
        $_SESSION['error_message'] = "❌ Error approving submission.";
    }
    mysqli_stmt_close($stmt);
    
} elseif ($action == 'reject') {
    // Reject submission with reason
    $rejection_comment = isset($_POST['rejection_comment']) ? trim($_POST['rejection_comment']) : '';
    
    if (empty($rejection_comment)) {
        $_SESSION['error_message'] = "❌ Rejection reason is required!";
        if ($from_page == 'view') {
            header("Location: view_submission.php?id=" . $submission_id);
        } else {
            header("Location: admin_interface.php?page=manage_submission");
        }
        exit();
    }
    
    $sql = "UPDATE submissions 
            SET status = 'rejected', 
                rejection_comment = ? 
            WHERE submission_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $rejection_comment, $submission_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "✅ Submission rejected successfully!";
    } else {
        $_SESSION['error_message'] = "❌ Error rejecting submission.";
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);

// Redirect based on where the request came from
if ($from_page == 'view') {
    header("Location: view_submission.php?id=" . $submission_id);
} else {
    header("Location: admin_interface.php?page=manage_submission");
}
exit();
?>