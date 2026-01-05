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
    jsonResponse(true, 'Code generated', ['code' => $code]);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get transfer code
    $code = isset($_POST['code']) ? normalizeCode($_POST['code']) : '';
    
    if (!$code) {
        jsonResponse(false, 'Transfer code is required');
    }
    
    if (!isValidCode($code)) {
        jsonResponse(false, 'Invalid transfer code format');
    }
    
    // Create transfer if doesn't exist
    createTransfer($code);
    
    // Check if files were uploaded
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        jsonResponse(false, 'No files uploaded');
    }
    
    $uploadDir = UPLOADS_DIR . $code . '/';
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploaded = 0;
    $errors = [];
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    
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
            $errors[] = "Error uploading $name";
            continue;
        }
        
        // Check file size
        if ($size > $maxFileSize) {
            $errors[] = "$name exceeds 10MB limit";
            continue;
        }
        
        // Validate file type using multiple methods
        if (!isValidImage($tmpName)) {
            $errors[] = "$name is not a valid image";
            continue;
        }
        $mimeType = getMimeType($tmpName);
        
        // Generate safe filename
        $safeFilename = sanitizeFilename($name);
        $destination = $uploadDir . $safeFilename;
        
        // Move uploaded file
        if (move_uploaded_file($tmpName, $destination)) {
            // Add to transfer record
            addFileToTransfer($code, [
                'name' => $safeFilename,
                'original_name' => $name,
                'size' => $size,
                'type' => $mimeType,
                'uploaded_at' => date('Y-m-d H:i:s')
            ]);
            $uploaded++;
        } else {
            $errors[] = "Failed to save $name";
        }
    }
    
    if ($uploaded > 0) {
        $message = "$uploaded file(s) uploaded successfully";
        if (!empty($errors)) {
            $message .= ". Some files had errors.";
        }
        jsonResponse(true, $message, [
            'uploaded' => $uploaded,
            'errors' => $errors
        ]);
    } else {
        jsonResponse(false, 'No files were uploaded', ['errors' => $errors]);
    }
}

// Invalid request
jsonResponse(false, 'Invalid request');
