<?php
session_start();
include '../connection/db.php';

$fileKey = $_GET['file_key'] ?? null;
$mode    = $_GET['mode'] ?? 'preview'; // preview | download

if (!$fileKey) {
    http_response_code(400);
    echo "Missing file key";
    exit;
}

/* ================= FETCH FILE FROM FILES TABLE ================= */

$stmt = $conn->prepare("
    SELECT file_name, file_type, file_data
    FROM files
    WHERE file_key = :file_key
    LIMIT 1
");
$stmt->execute([':file_key' => $fileKey]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    echo "File not found";
    exit;
}

$fileName = $file['file_name'] ?: 'file';
$fileType = $file['file_type'] ?: 'application/octet-stream';

/* ================= HEADERS ================= */

header('Content-Type: ' . $fileType);
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

if ($mode === 'download') {
    header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
} else {
    header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
}

/* ================= OUTPUT FILE ================= */

if (!empty($file['file_data'])) {

    // handle LOB (PostgreSQL / PDO)
    if (is_resource($file['file_data'])) {
        fpassthru($file['file_data']);
    } else {
        echo $file['file_data'];
    }

    exit;
}

http_response_code(500);
echo "File data missing";
exit;