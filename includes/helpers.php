<?php
/**
 * Helper functions for Photo Transfer application
 */

// Path constants
define('DATA_FILE', __DIR__ . '/../data/transfers.json');
define('UPLOADS_DIR', __DIR__ . '/../uploads/');

/**
 * Read transfers data from JSON file
 * @return array
 */
function readTransfers() {
    if (!file_exists(DATA_FILE)) {
        return [];
    }
    $content = file_get_contents(DATA_FILE);
    return json_decode($content, true) ?: [];
}

/**
 * Write transfers data to JSON file
 * @param array $data
 * @return bool
 */
function writeTransfers($data) {
    $dir = dirname(DATA_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Generate a unique transfer code
 * @param int $length
 * @return string
 */
function generateCode($length = 6) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Excluded confusing chars: I, O, 0, 1
    $code = '';
    $max = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $max)];
    }
    
    // Ensure code is unique
    $transfers = readTransfers();
    if (isset($transfers[$code])) {
        return generateCode($length); // Recursively generate new code
    }
    
    return $code;
}

/**
 * Normalize code to uppercase
 * @param string $code
 * @return string
 */
function normalizeCode($code) {
    return strtoupper(trim($code));
}

/**
 * Validate transfer code format
 * @param string $code
 * @return bool
 */
function isValidCode($code) {
    return preg_match('/^[A-Z0-9]{6}$/', normalizeCode($code));
}

/**
 * Get transfer by code
 * @param string $code
 * @return array|null
 */
function getTransfer($code) {
    $code = normalizeCode($code);
    $transfers = readTransfers();
    return $transfers[$code] ?? null;
}

/**
 * Create a new transfer session
 * @param string $code
 * @return bool
 */
function createTransfer($code) {
    $code = normalizeCode($code);
    $transfers = readTransfers();
    
    if (isset($transfers[$code])) {
        return true; // Already exists
    }
    
    $transfers[$code] = [
        'created_at' => date('Y-m-d H:i:s'),
        'files' => []
    ];
    
    // Create upload directory
    $uploadDir = UPLOADS_DIR . $code . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    return writeTransfers($transfers);
}

/**
 * Add file to transfer
 * @param string $code
 * @param array $fileInfo
 * @return bool
 */
function addFileToTransfer($code, $fileInfo) {
    $code = normalizeCode($code);
    $transfers = readTransfers();
    
    if (!isset($transfers[$code])) {
        return false;
    }
    
    $transfers[$code]['files'][] = $fileInfo;
    return writeTransfers($transfers);
}

/**
 * Remove file from transfer
 * @param string $code
 * @param string $filename
 * @return bool
 */
function removeFileFromTransfer($code, $filename) {
    $code = normalizeCode($code);
    $transfers = readTransfers();
    
    if (!isset($transfers[$code])) {
        return false;
    }
    
    // Remove from files array
    $transfers[$code]['files'] = array_filter(
        $transfers[$code]['files'],
        function($file) use ($filename) {
            return $file['name'] !== $filename;
        }
    );
    $transfers[$code]['files'] = array_values($transfers[$code]['files']); // Re-index
    
    // Delete physical file
    $filePath = UPLOADS_DIR . $code . '/' . $filename;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    return writeTransfers($transfers);
}

/**
 * Delete entire transfer session
 * @param string $code
 * @return bool
 */
function deleteTransfer($code) {
    $code = normalizeCode($code);
    $transfers = readTransfers();
    
    if (!isset($transfers[$code])) {
        return false;
    }
    
    // Delete upload directory and contents
    $uploadDir = UPLOADS_DIR . $code . '/';
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($uploadDir);
    }
    
    // Remove from transfers
    unset($transfers[$code]);
    return writeTransfers($transfers);
}

/**
 * Sanitize filename for safe storage
 * @param string $filename
 * @return string
 */
function sanitizeFilename($filename) {
    // Remove path info
    $filename = basename($filename);
    // Replace dangerous characters
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    // Ensure unique name with timestamp
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $name = pathinfo($filename, PATHINFO_FILENAME);
    return $name . '_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
}

/**
 * Check if file is an allowed image type
 * @param string $mimeType
 * @return bool
 */
function isAllowedImageType($mimeType) {
    $allowed = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp'
    ];
    return in_array($mimeType, $allowed);
}

/**
 * Format file size for display
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Send JSON response
 * @param bool $success
 * @param string $message
 * @param array $data
 */
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
