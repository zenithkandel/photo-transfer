<?php
/**
 * Download API Endpoint
 * Handles single file download or ZIP of all files
 */

require_once __DIR__ . '/../includes/helpers.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die('Method not allowed');
}

// Get transfer code
$code = isset($_GET['code']) ? normalizeCode($_GET['code']) : '';

if (!$code || !isValidCode($code)) {
    http_response_code(400);
    die('Invalid transfer code');
}

// Get transfer data
$transfer = getTransfer($code);

if ($transfer === null || empty($transfer['files'])) {
    http_response_code(404);
    die('No files found');
}

$uploadDir = UPLOADS_DIR . $code . '/';

// Download all as ZIP
if (isset($_GET['all']) && $_GET['all'] == '1') {
    // Check if ZipArchive is available
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        die('ZIP functionality not available');
    }
    
    $zipFile = sys_get_temp_dir() . '/files_' . $code . '_' . time() . '.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
        http_response_code(500);
        die('Could not create ZIP file');
    }
    
    // Add files to ZIP
    foreach ($transfer['files'] as $file) {
        $filePath = $uploadDir . $file['name'];
        if (file_exists($filePath)) {
            // Use original name in ZIP
            $zip->addFile($filePath, $file['original_name']);
        }
    }
    
    $zip->close();
    
    // Log the download
    logAction('DOWNLOAD_ALL', [
        'code' => $code,
        'file_count' => count($transfer['files']),
        'zip_size' => filesize($zipFile)
    ]);
    
    // Send ZIP file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="files_' . $code . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    header('Cache-Control: no-cache, must-revalidate');
    
    readfile($zipFile);
    
    // Clean up temp file
    unlink($zipFile);
    exit;
}

// Download single file
if (isset($_GET['file'])) {
    $requestedFile = basename($_GET['file']); // Sanitize
    
    // Find file in transfer
    $fileInfo = null;
    foreach ($transfer['files'] as $file) {
        if ($file['name'] === $requestedFile) {
            $fileInfo = $file;
            break;
        }
    }
    
    if (!$fileInfo) {
        http_response_code(404);
        die('File not found');
    }
    
    $filePath = $uploadDir . $requestedFile;
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        die('File not found on server');
    }
    
    // Log the download
    logAction('FILE_DOWNLOADED', [
        'code' => $code,
        'file' => $fileInfo['original_name'],
        'size' => $fileInfo['size']
    ]);
    
    // Get MIME type using helper function
    $mimeType = getMimeType($filePath);
    
    // Send file
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $fileInfo['original_name'] . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    
    readfile($filePath);
    exit;
}

// No valid parameters
http_response_code(400);
die('Missing file parameter');
