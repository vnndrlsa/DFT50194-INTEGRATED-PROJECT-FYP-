<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submission_id'])) {
    $submission_id = intval($_POST['submission_id']);
    $rejection_comment = mysqli_real_escape_string($conn, $_POST['rejection_comment']);
    
    $sql = "UPDATE submissions 
            SET status = 'rejected', 
                rejection_comment = '$rejection_comment' 
            WHERE submission_id = $submission_id";
    
    if (mysqli_query($conn, $sql)) {
        $_SESSION['message'] = "Submission rejected successfully!";
    } else {
        $_SESSION['error'] = "Rejection failed: " . mysqli_error($conn);
    }
    
    header("Location: admin_interface.php?page=manage_submission");
    exit();
}
?>
