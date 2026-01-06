<?php
/**
 * Upload API Endpoint
 * Handles file uploads and code generation
 */

require_once __DIR__ . '/../includes/helpers.php';

// Handle code generation request
if (isset($_GET['action']) && $_GET['action'] === 'generate') {
    $code = generateCode();
    createTransfer($code);
    logAction('CODE_GENERATED', ['code' => $code]);
    jsonResponse(true, 'Code generated', ['code' => $code]);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get transfer code
    $code = isset($_POST['code']) ? normalizeCode($_POST['code']) : '';
    
    if (!$code) {
        logAction('UPLOAD_FAILED', ['error' => 'No code provided']);
        jsonResponse(false, 'Transfer code is required');
    }
    
    if (!isValidCode($code)) {
        logAction('UPLOAD_FAILED', ['code' => $code, 'error' => 'Invalid code format']);
        jsonResponse(false, 'Invalid transfer code format');
    }
    
    // Create transfer if doesn't exist
    createTransfer($code);
    
    // Check if files were uploaded
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        logAction('UPLOAD_FAILED', ['code' => $code, 'error' => 'No files']);
        jsonResponse(false, 'No files uploaded');
    }
    
    $uploadDir = UPLOADS_DIR . $code . '/';
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploaded = 0;
    $uploadedFiles = [];
    $errors = [];
    $maxFileSize = 50 * 1024 * 1024; // 50MB per file
    
    // Process each file
    $files = $_FILES['files'];
    $fileCount = count($files['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        $name = $files['name'][$i];
        $tmpName = $files['tmp_name'][$i];
        $error = $files['error'][$i];
        $size = $files['size'][$i];
        
        // Check for upload errors
        if ($error !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by extension'
            ];
            $errors[] = $name . ': ' . ($errorMessages[$error] ?? 'Unknown error');
            continue;
        }
        
        // Check file size
        if ($size > $maxFileSize) {
            $errors[] = "$name: Exceeds 50MB limit";
            continue;
        }
        
        // Validate file type and content
        $validation = validateFile($tmpName, $name);
        if (!$validation['valid']) {
            $errors[] = "$name: " . $validation['error'];
            logAction('UPLOAD_BLOCKED', ['code' => $code, 'file' => $name, 'reason' => $validation['error']]);
            continue;
        }
        
        // Get MIME type
        $mimeType = getMimeType($tmpName);
        
        // Get file category and icon
        $category = getFileCategory($name);
        $icon = getFileIcon($name);
        
        // Generate safe filename
        $safeFilename = sanitizeFilename($name);
        $destination = $uploadDir . $safeFilename;
        
        // Move uploaded file
        if (move_uploaded_file($tmpName, $destination)) {
            // Add to transfer record with extended metadata
            addFileToTransfer($code, [
                'name' => $safeFilename,
                'original_name' => $name,
                'size' => $size,
                'type' => $mimeType,
                'category' => $category,
                'icon' => $icon,
                'uploaded_at' => date('Y-m-d H:i:s')
            ]);
            $uploaded++;
            $uploadedFiles[] = $name;
        } else {
            $errors[] = "Failed to save $name";
        }
    }
    
    if ($uploaded > 0) {
        $message = "$uploaded file(s) uploaded successfully";
        if (!empty($errors)) {
            $message .= ". Some files had errors.";
        }
        logAction('FILES_UPLOADED', [
            'code' => $code,
            'count' => $uploaded,
            'files' => $uploadedFiles,
            'total_size' => array_sum(array_column($_FILES['files'], 'size'))
        ]);
        jsonResponse(true, $message, [
            'uploaded' => $uploaded,
            'errors' => $errors
        ]);
    } else {
        logAction('UPLOAD_FAILED', ['code' => $code, 'errors' => $errors]);
        jsonResponse(false, 'No files were uploaded', ['errors' => $errors]);
    }
}

// Invalid request
jsonResponse(false, 'Invalid request');
