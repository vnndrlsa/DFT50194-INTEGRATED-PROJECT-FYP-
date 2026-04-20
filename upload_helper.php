<?php
/**
 * upload_helper.php
 * Helper to upload PDF files into the "document pdf" folder
 */

define('UPLOAD_DIR',     __DIR__ . '/document pdf/');
define('UPLOAD_DIR_WEB', 'document pdf/');

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

/**
 * Generate clean PDF filename
 * Result: SUB-0001-20260407.pdf
 */
function generatePdfFilename(int $submission_id): string
{
    return 'SUB-' . str_pad($submission_id, 4, '0', STR_PAD_LEFT)
         . '-' . date('Ymd')
         . '.pdf';
}

/**
 * Handle PDF upload
 *
 * @param array  $file           $_FILES['document']
 * @param int    $submission_id  Submission ID
 * @param string $old_path       Old filename/path in DB (will be deleted if replaced)
 * @return array ['success', 'document_name', 'document_path', 'error']
 */
function handlePdfUpload(array $file, int $submission_id, string $old_path = ''): array
{
    $old_path = $old_path ?? '';

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'no_file'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed. Error code: ' . $file['error']];
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'pdf') {
        return ['success' => false, 'error' => 'Only PDF files are allowed!'];
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File size must not exceed 2MB!'];
    }

    $safe_name   = generatePdfFilename($submission_id);
    $target_path = UPLOAD_DIR . $safe_name;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => false, 'error' => 'Failed to save file. Please check folder permission for "document pdf".'];
    }

    // Delete old file — no orphan/duplicate
    if (!empty($old_path)) {
        $old_full = file_exists($old_path) ? $old_path : UPLOAD_DIR . basename($old_path);
        if (file_exists($old_full) && $old_full !== $target_path) {
            @unlink($old_full);
        }
    }

    return [
        'success'       => true,
        'document_name' => $file['name'],  // original name for display
        'document_path' => $safe_name,     // filename only stored in DB
        'error'         => ''
    ];
}
?>