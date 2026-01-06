<?php
/**
 * Delete API Endpoint
 * Handles deletion of single files or entire transfer sessions
 */

require_once __DIR__ . '/../includes/helpers.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(false, 'Invalid request data');
}

// Get transfer code
$code = isset($input['code']) ? normalizeCode($input['code']) : '';

if (!$code || !isValidCode($code)) {
    jsonResponse(false, 'Invalid transfer code');
}

// Check if transfer exists
$transfer = getTransfer($code);

if ($transfer === null) {
    jsonResponse(false, 'Transfer not found');
}

// Delete all files (entire session)
if (isset($input['all']) && $input['all'] === true) {
    $fileCount = count($transfer['files']);
    if (deleteTransfer($code)) {
        logAction('TRANSFER_DELETED', [
            'code' => $code,
            'files_deleted' => $fileCount
        ]);
        jsonResponse(true, 'All files deleted successfully');
    } else {
        logAction('DELETE_FAILED', ['code' => $code, 'type' => 'all']);
        jsonResponse(false, 'Failed to delete transfer');
    }
}

// Delete single file
if (isset($input['file'])) {
    $filename = basename($input['file']); // Sanitize
    
    // Check if file exists in transfer
    $fileExists = false;
    $originalName = $filename;
    foreach ($transfer['files'] as $file) {
        if ($file['name'] === $filename) {
            $fileExists = true;
            $originalName = $file['original_name'];
            break;
        }
    }
    
    if (!$fileExists) {
        jsonResponse(false, 'File not found');
    }
    
    if (removeFileFromTransfer($code, $filename)) {
        logAction('FILE_DELETED', [
            'code' => $code,
            'file' => $originalName
        ]);
        jsonResponse(true, 'File deleted successfully');
    } else {
        logAction('DELETE_FAILED', ['code' => $code, 'file' => $filename]);
        jsonResponse(false, 'Failed to delete file');
    }
}

// No valid parameters
jsonResponse(false, 'Missing delete parameters');
