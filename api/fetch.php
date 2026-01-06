<?php
/**
 * Fetch API Endpoint
 * Returns list of files for a given transfer code
 */

require_once __DIR__ . '/../includes/helpers.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Invalid request method');
}

// Get transfer code
$code = isset($_GET['code']) ? normalizeCode($_GET['code']) : '';

if (!$code) {
    jsonResponse(false, 'Transfer code is required');
}

if (!isValidCode($code)) {
    jsonResponse(false, 'Invalid transfer code format');
}

// Get transfer data
$transfer = getTransfer($code);

if ($transfer === null) {
    logAction('FILES_FETCHED', ['code' => $code, 'found' => false]);
    jsonResponse(true, 'No transfer found', [
        'code' => $code,
        'files' => []
    ]);
}

// Log the fetch action
logAction('FILES_FETCHED', [
    'code' => $code,
    'found' => true,
    'file_count' => count($transfer['files'])
]);

// Return transfer data
jsonResponse(true, 'Transfer found', [
    'code' => $code,
    'created_at' => $transfer['created_at'],
    'files' => $transfer['files']
]);
