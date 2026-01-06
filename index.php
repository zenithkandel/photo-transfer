<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Transfer - Share Files Instantly</title>
    <meta name="description" content="Share files instantly with a simple 6-character code. No registration required.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-paper-plane"></i>
            </div>
            <h1>File <span>Transfer</span></h1>
            <p class="subtitle">Share files instantly with a simple code</p>
        </header>

        <!-- Code Entry Section -->
        <section id="code-section" class="card">
            <div class="card-icon">
                <i class="fas fa-key"></i>
            </div>
            <h2>Enter or Generate a Code</h2>
            <div class="code-input-group">
                <div class="input-wrapper">
                    <i class="fas fa-hashtag"></i>
                    <input type="text" id="transfer-code" placeholder="Enter 6-character code" maxlength="6" autocomplete="off">
                </div>
                <div class="btn-group">
                    <button id="btn-generate" class="btn btn-secondary"><i class="fas fa-random"></i> Generate</button>
                    <button id="btn-proceed" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Proceed</button>
                </div>
            </div>
            <p class="hint"><i class="fas fa-info-circle"></i> Enter an existing code to access files, or generate a new one to start sharing.</p>
        </section>

        <!-- Upload Section (Sender) -->
        <section id="upload-section" class="card hidden">
            <div class="section-header">
                <h2><i class="fas fa-cloud-upload-alt"></i> Upload Files</h2>
                <div class="current-code">
                    <span class="code-label">Code:</span>
                    <span id="display-code" class="code-value"></span>
                    <button id="btn-copy-code" class="btn btn-icon" title="Copy code"><i class="fas fa-copy"></i></button>
                </div>
            </div>
            
            <div id="upload-area" class="upload-area">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h3>Drag & drop files here</h3>
                <p class="or">or</p>
                <label for="file-input" class="btn btn-primary"><i class="fas fa-folder-open"></i> Browse Files</label>
                <input type="file" id="file-input" multiple hidden>
                <p class="file-hint">Images, Documents, Archives, Audio, Video (Max 50MB each)</p>
            </div>

            <!-- Preview of selected files -->
            <div id="preview-container" class="preview-container hidden">
                <div class="preview-header">
                    <h3><i class="fas fa-files"></i> Selected Files (<span id="preview-count">0</span>)</h3>
                </div>
                <div id="preview-grid" class="preview-grid"></div>
                <div class="upload-actions">
                    <button id="btn-clear" class="btn btn-secondary"><i class="fas fa-times"></i> Clear All</button>
                    <button id="btn-upload" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Files</button>
                </div>
            </div>

            <!-- Upload Progress -->
            <div id="upload-progress" class="progress-container hidden">
                <div class="progress-header">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span id="progress-text">Uploading...</span>
                </div>
                <div class="progress-bar">
                    <div id="progress-fill" class="progress-fill"></div>
                </div>
                <div class="progress-stats">
                    <span id="progress-percent">0%</span>
                    <span id="progress-size"></span>
                </div>
            </div>
        </section>

        <!-- Gallery Section (Receiver/After Upload) -->
        <section id="gallery-section" class="card hidden">
            <div class="section-header">
                <h2><i class="fas fa-folder-open"></i> Shared Files</h2>
                <div class="current-code">
                    <span class="code-label">Code:</span>
                    <span id="gallery-code" class="code-value"></span>
                    <button id="btn-copy-code-gallery" class="btn btn-icon" title="Copy code"><i class="fas fa-copy"></i></button>
                </div>
            </div>

            <div id="gallery-loading" class="loading">
                <div class="spinner-container">
                    <i class="fas fa-circle-notch fa-spin"></i>
                </div>
                <p>Loading files...</p>
            </div>

            <div id="gallery-empty" class="empty-state hidden">
                <div class="empty-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3>No files found</h3>
                <p>This transfer code doesn't have any files yet.</p>
                <button id="btn-upload-new" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Files</button>
            </div>

            <div id="gallery-content" class="hidden">
                <div class="gallery-info">
                    <div class="info-item">
                        <i class="fas fa-files"></i>
                        <span id="photo-count">0 files</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-database"></i>
                        <span id="total-size"></span>
                    </div>
                </div>
                
                <div id="gallery-grid" class="gallery-grid"></div>

                <div class="gallery-actions">
                    <button id="btn-download-all" class="btn btn-primary"><i class="fas fa-download"></i> Download All</button>
                    <button id="btn-add-more" class="btn btn-secondary"><i class="fas fa-plus"></i> Add More</button>
                    <button id="btn-delete-all" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete All</button>
                </div>
            </div>
        </section>

        <!-- Back to Home -->
        <div id="back-nav" class="back-nav hidden">
            <button id="btn-back" class="btn btn-link"><i class="fas fa-arrow-left"></i> Start Over</button>
        </div>

        <!-- Footer -->
        <footer>
            <p>Secure file transfer · No registration required</p>
        </footer>
    </div>

    <!-- File Preview Modal -->
    <div id="image-modal" class="modal hidden">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <span id="modal-filename" class="modal-filename"></span>
                <button id="modal-close" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <img id="modal-image" src="" alt="Preview">
                <div id="modal-file-preview" class="file-preview hidden">
                    <i id="modal-file-icon" class="fas fa-file"></i>
                    <span id="modal-file-type"></span>
                </div>
            </div>
            <div class="modal-actions">
                <button id="modal-download" class="btn btn-primary"><i class="fas fa-download"></i> Download</button>
                <button id="modal-delete" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="toast-container"></div>

    <script src="assets/js/app.js"></script>
</body>
</html>

