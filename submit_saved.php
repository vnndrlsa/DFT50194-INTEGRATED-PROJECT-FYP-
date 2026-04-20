<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$user_id = $_SESSION['user_id'];
$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($submission_id == 0) {
    header("Location: view_my_submissions.php");
    exit();
}

// Update status from saved to pending - only if belongs to this user
$sql = "UPDATE submissions SET status = 'pending' WHERE submission_id = ? AND user_id = ? AND status = 'saved'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $submission_id, $user_id);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    $_SESSION['success_message'] = "Submission sent successfully! Waiting for admin review.";
} else {
    $_SESSION['error_message'] = "Error submitting. Please try again.";
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

header("Location: view_my_submissions.php");
exit();
?>