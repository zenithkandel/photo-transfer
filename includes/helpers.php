<?php
/**
 * Helper functions for File Transfer application
 */

// Path constants
define('DATA_FILE', __DIR__ . '/../data/transfers.json');
define('UPLOADS_DIR', __DIR__ . '/../uploads/');
define('LOG_FILE', __DIR__ . '/../data/system.log');

/**
 * Log an action to the system log file
 * @param string $action The action being performed
 * @param array $details Additional details to log
 * @return bool
 */
function logAction($action, $details = []) {
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $ip = getClientIP();
    $timestamp = date('Y-m-d H:i:s');
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
    
    $logEntry = [
        'timestamp' => $timestamp,
        'ip' => $ip,
        'action' => $action,
        'details' => $details,
        'user_agent' => $userAgent
    ];
    
    $logLine = '[' . $timestamp . '] [' . $ip . '] ' . $action;
    if (!empty($details)) {
        $logLine .= ' | ' . json_encode($details, JSON_UNESCAPED_SLASHES);
    }
    $logLine .= PHP_EOL;
    
    return file_put_contents(LOG_FILE, $logLine, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Get client IP address
 * @return string
 */
function getClientIP() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Proxy
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // Handle comma-separated IPs (take the first one)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return 'Unknown';
}

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
    // Remove path info and null bytes
    $filename = basename($filename);
    $filename = str_replace(chr(0), '', $filename);
    
    // Get extension and name separately
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $name = pathinfo($filename, PATHINFO_FILENAME);
    
    // Remove any double extensions (security)
    $name = preg_replace('/\.(php|phtml|phar|exe|bat|cmd|sh|js)$/i', '', $name);
    
    // Replace dangerous/special characters with underscore
    $name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
    
    // Remove consecutive underscores
    $name = preg_replace('/_+/', '_', $name);
    
    // Trim underscores from start/end
    $name = trim($name, '_');
    
    // Ensure name is not empty
    if (empty($name)) {
        $name = 'file';
    }
    
    // Truncate if too long (max 50 chars for name)
    if (strlen($name) > 50) {
        $name = substr($name, 0, 50);
    }
    
    // Add timestamp and random string for uniqueness
    $uniqueId = time() . '_' . bin2hex(random_bytes(4));
    
    return $name . '_' . $uniqueId . '.' . $ext;
}

/**
 * Get MIME type of a file with fallback methods
 * @param string $filePath
 * @return string
 */
function getMimeType($filePath) {
    // Try finfo class first (most reliable)
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);
        if ($mime) return $mime;
    }
    
    // Try mime_content_type
    if (function_exists('mime_content_type')) {
        $mime = mime_content_type($filePath);
        if ($mime) return $mime;
    }
    
    // Fallback: detect by file extension
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    return getMimeTypeByExtension($ext);
}

/**
 * Get MIME type by file extension
 * @param string $ext
 * @return string
 */
function getMimeTypeByExtension($ext) {
    $mimeTypes = [
        // Images
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        
        // Documents
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'txt' => 'text/plain',
        'rtf' => 'application/rtf',
        'csv' => 'text/csv',
        
        // Archives
        'zip' => 'application/zip',
        'rar' => 'application/vnd.rar',
        '7z' => 'application/x-7z-compressed',
        'tar' => 'application/x-tar',
        'gz' => 'application/gzip',
        
        // Audio
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'flac' => 'audio/flac',
        'aac' => 'audio/aac',
        'm4a' => 'audio/mp4',
        
        // Video
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'avi' => 'video/x-msvideo',
        'mov' => 'video/quicktime',
        'mkv' => 'video/x-matroska',
        'wmv' => 'video/x-ms-wmv',
        
        // Code
        'html' => 'text/html',
        'htm' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'php' => 'application/x-php',
        'py' => 'text/x-python',
        'java' => 'text/x-java-source',
        'c' => 'text/x-c',
        'cpp' => 'text/x-c++',
        'h' => 'text/x-c',
        'md' => 'text/markdown',
        
        // Fonts
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        
        // Other
        'exe' => 'application/x-msdownload',
        'apk' => 'application/vnd.android.package-archive',
        'dmg' => 'application/x-apple-diskimage',
        'iso' => 'application/x-iso9660-image',
        'sql' => 'application/sql',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript'
    ];
    
    return $mimeTypes[$ext] ?? 'application/octet-stream';
}

/**
 * Get list of allowed file extensions
 * @return array
 */
function getAllowedExtensions() {
    return [
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'tiff', 'tif',
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'txt', 'rtf', 'csv',
        // Archives
        'zip', 'rar', '7z', 'tar', 'gz',
        // Audio
        'mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a',
        // Video
        'mp4', 'webm', 'avi', 'mov', 'mkv', 'wmv',
        // Code
        'html', 'htm', 'css', 'js', 'json', 'xml', 'md', 'sql',
        // Fonts
        'ttf', 'otf', 'woff', 'woff2',
        // Design
        'psd', 'ai', 'eps'
    ];
}

/**
 * Get blocked/dangerous file extensions
 * @return array
 */
function getBlockedExtensions() {
    return [
        'php', 'php3', 'php4', 'php5', 'phtml', 'phar',
        'exe', 'msi', 'bat', 'cmd', 'com', 'scr',
        'sh', 'bash', 'zsh', 'csh',
        'dll', 'so', 'dylib',
        'vbs', 'vbe', 'js', 'jse', 'ws', 'wsf', 'wsc', 'wsh',
        'ps1', 'ps1xml', 'ps2', 'ps2xml', 'psc1', 'psc2',
        'reg', 'inf', 'scf', 'lnk', 'hta',
        'cpl', 'msc', 'jar', 'jnlp',
        'htaccess', 'htpasswd'
    ];
}

/**
 * Check if file extension is allowed
 * @param string $filename
 * @return bool
 */
function isAllowedFile($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Check if extension is blocked (security)
    if (in_array($ext, getBlockedExtensions())) {
        return false;
    }
    
    // Check if extension is in allowed list
    return in_array($ext, getAllowedExtensions());
}

/**
 * Validate file by checking extension and basic integrity
 * @param string $filePath
 * @param string $originalName
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateFile($filePath, $originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Check blocked extensions
    if (in_array($ext, getBlockedExtensions())) {
        return ['valid' => false, 'error' => 'File type not allowed for security reasons'];
    }
    
    // Check allowed extensions
    if (!in_array($ext, getAllowedExtensions())) {
        return ['valid' => false, 'error' => 'File type not supported'];
    }
    
    // Additional validation for images
    if (isImageExtension($ext)) {
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'Invalid or corrupted image file'];
        }
    }
    
    // Check for PHP code in file content (security)
    $content = @file_get_contents($filePath, false, null, 0, 1024);
    if ($content !== false && preg_match('/<\?php|<\?=/i', $content)) {
        return ['valid' => false, 'error' => 'File contains potentially dangerous content'];
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Check if extension is an image type
 * @param string $ext
 * @return bool
 */
function isImageExtension($ext) {
    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'tiff', 'tif'];
    return in_array(strtolower($ext), $imageExts);
}

/**
 * Check if file is an image by its MIME type
 * @param string $mimeType
 * @return bool
 */
function isImageMime($mimeType) {
    return strpos($mimeType, 'image/') === 0;
}

/**
 * Get file category based on extension
 * @param string $filename
 * @return string
 */
function getFileCategory($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $categories = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'tiff', 'tif', 'psd', 'ai', 'eps'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'txt', 'rtf', 'csv', 'md'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
        'audio' => ['mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a'],
        'video' => ['mp4', 'webm', 'avi', 'mov', 'mkv', 'wmv'],
        'code' => ['html', 'htm', 'css', 'js', 'json', 'xml', 'sql'],
        'font' => ['ttf', 'otf', 'woff', 'woff2']
    ];
    
    foreach ($categories as $category => $extensions) {
        if (in_array($ext, $extensions)) {
            return $category;
        }
    }
    
    return 'other';
}

/**
 * Get Font Awesome icon class for file type
 * @param string $filename
 * @return string
 */
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $icons = [
        // Images
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'gif' => 'fa-file-image',
        'webp' => 'fa-file-image',
        'bmp' => 'fa-file-image',
        'svg' => 'fa-file-image',
        'ico' => 'fa-file-image',
        'tiff' => 'fa-file-image',
        'tif' => 'fa-file-image',
        'psd' => 'fa-file-image',
        
        // Documents
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word',
        'docx' => 'fa-file-word',
        'xls' => 'fa-file-excel',
        'xlsx' => 'fa-file-excel',
        'csv' => 'fa-file-excel',
        'ppt' => 'fa-file-powerpoint',
        'pptx' => 'fa-file-powerpoint',
        'txt' => 'fa-file-lines',
        'rtf' => 'fa-file-lines',
        'md' => 'fa-file-lines',
        
        // Archives
        'zip' => 'fa-file-zipper',
        'rar' => 'fa-file-zipper',
        '7z' => 'fa-file-zipper',
        'tar' => 'fa-file-zipper',
        'gz' => 'fa-file-zipper',
        
        // Audio
        'mp3' => 'fa-file-audio',
        'wav' => 'fa-file-audio',
        'ogg' => 'fa-file-audio',
        'flac' => 'fa-file-audio',
        'aac' => 'fa-file-audio',
        'm4a' => 'fa-file-audio',
        
        // Video
        'mp4' => 'fa-file-video',
        'webm' => 'fa-file-video',
        'avi' => 'fa-file-video',
        'mov' => 'fa-file-video',
        'mkv' => 'fa-file-video',
        'wmv' => 'fa-file-video',
        
        // Code
        'html' => 'fa-file-code',
        'htm' => 'fa-file-code',
        'css' => 'fa-file-code',
        'js' => 'fa-file-code',
        'json' => 'fa-file-code',
        'xml' => 'fa-file-code',
        'sql' => 'fa-database',
        
        // Fonts
        'ttf' => 'fa-font',
        'otf' => 'fa-font',
        'woff' => 'fa-font',
        'woff2' => 'fa-font',
        
        // Design
        'ai' => 'fa-bezier-curve',
        'eps' => 'fa-bezier-curve'
    ];
    
    return $icons[$ext] ?? 'fa-file';
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
