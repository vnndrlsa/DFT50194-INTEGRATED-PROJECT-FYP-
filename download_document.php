<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';
include 'upload_helper.php';   // untuk UPLOAD_DIR constant

$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($submission_id <= 0) {
    die("Invalid submission ID.");
}

// Ambil maklumat submission dari DB
$sql  = "SELECT s.submission_id, s.user_id, s.document_name, s.document_path, u.full_name 
         FROM submissions s 
         JOIN users u ON s.user_id = u.user_id 
         WHERE s.submission_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $submission_id);
mysqli_stmt_execute($stmt);
$result     = mysqli_stmt_get_result($stmt);
$submission = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
mysqli_close($conn);

if (!$submission) {
    die("Submission not found.");
}

// Keselamatan: user biasa hanya boleh tengok fail sendiri
if ($_SESSION['role'] != 'admin' && $submission['user_id'] != $_SESSION['user_id']) {
    die("Unauthorized access to this document.");
}

$document_path = $submission['document_path'];
$document_name = $submission['document_name'];

if (empty($document_path)) {
    die("Tiada fail dokumen untuk submission ini.");
}

// ── Resolve full path ────────────────────────────────────────────────────────
// DB simpan filename sahaja contoh: "SUB-0001-20260407.pdf"
// Gunakan UPLOAD_DIR dari upload_helper.php untuk bina full path
if (!file_exists($document_path)) {
    $document_path = UPLOAD_DIR . basename($document_path);
}

if (!file_exists($document_path)) {
    die("Fail tidak dijumpai. Fail mungkin telah dipindah atau dipadam.");
}

// PDF sahaja
$file_ext = strtolower(pathinfo($document_name, PATHINFO_EXTENSION));
if ($file_ext !== 'pdf') {
    die("Jenis fail tidak sah.");
}

// ── Serve file ───────────────────────────────────────────────────────────────
$is_download = isset($_GET['download']) && $_GET['download'] == '1';
$disposition = $is_download ? 'attachment' : 'inline';

header('Content-Type: application/pdf');
header('Content-Disposition: ' . $disposition . '; filename="' . basename($document_name) . '"');
header('Content-Length: ' . filesize($document_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($document_path);
exit();
?>