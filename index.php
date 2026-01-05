<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photo Transfer</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📸 Photo Transfer</h1>
            <p class="subtitle">Share photos instantly with a simple code</p>
        </header>

        <!-- Code Entry Section -->
        <section id="code-section" class="card">
            <h2>Enter or Generate a Code</h2>
            <div class="code-input-group">
                <input type="text" id="transfer-code" placeholder="Enter 6-character code" maxlength="6" autocomplete="off">
                <button id="btn-generate" class="btn btn-secondary">Generate New</button>
                <button id="btn-proceed" class="btn btn-primary">Proceed</button>
            </div>
            <p class="hint">Enter an existing code to access photos, or generate a new one to start sharing.</p>
        </section>

        <!-- Upload Section (Sender) -->
        <section id="upload-section" class="card hidden">
            <div class="section-header">
                <h2>Upload Photos</h2>
                <div class="current-code">
                    Code: <span id="display-code"></span>
                    <button id="btn-copy-code" class="btn btn-small" title="Copy code">📋</button>
                </div>
            </div>
            
            <div id="upload-area" class="upload-area">
                <div class="upload-icon">📁</div>
                <p>Drag & drop photos here</p>
                <p class="or">or</p>
                <label for="file-input" class="btn btn-primary">Select Photos</label>
                <input type="file" id="file-input" multiple accept="image/*" hidden>
            </div>

            <!-- Preview of selected files -->
            <div id="preview-container" class="preview-container hidden">
                <h3>Selected Photos (<span id="preview-count">0</span>)</h3>
                <div id="preview-grid" class="preview-grid"></div>
                <div class="upload-actions">
                    <button id="btn-clear" class="btn btn-secondary">Clear All</button>
                    <button id="btn-upload" class="btn btn-primary">Upload Photos</button>
                </div>
            </div>

            <!-- Upload Progress -->
            <div id="upload-progress" class="progress-container hidden">
                <div class="progress-bar">
                    <div id="progress-fill" class="progress-fill"></div>
                </div>
                <p id="progress-text">Uploading...</p>
            </div>
        </section>

        <!-- Gallery Section (Receiver/After Upload) -->
        <section id="gallery-section" class="card hidden">
            <div class="section-header">
                <h2>Shared Photos</h2>
                <div class="current-code">
                    Code: <span id="gallery-code"></span>
                    <button id="btn-copy-code-gallery" class="btn btn-small" title="Copy code">📋</button>
                </div>
            </div>

            <div id="gallery-loading" class="loading">
                <div class="spinner"></div>
                <p>Loading photos...</p>
            </div>

            <div id="gallery-empty" class="empty-state hidden">
                <p>📭 No photos found for this code.</p>
                <button id="btn-upload-new" class="btn btn-primary">Upload Photos</button>
            </div>

            <div id="gallery-content" class="hidden">
                <div class="gallery-info">
                    <span id="photo-count">0 photos</span>
                    <span id="total-size"></span>
                </div>
                
                <div id="gallery-grid" class="gallery-grid"></div>

                <div class="gallery-actions">
                    <button id="btn-download-all" class="btn btn-primary">⬇️ Download All</button>
                    <button id="btn-add-more" class="btn btn-secondary">➕ Add More Photos</button>
                    <button id="btn-delete-all" class="btn btn-danger">🗑️ Delete All</button>
                </div>
            </div>
        </section>

        <!-- Back to Home -->
        <div id="back-nav" class="back-nav hidden">
            <button id="btn-back" class="btn btn-link">← Start Over</button>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="image-modal" class="modal hidden">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button id="modal-close" class="modal-close">&times;</button>
            <img id="modal-image" src="" alt="Preview">
            <div class="modal-actions">
                <button id="modal-download" class="btn btn-primary">⬇️ Download</button>
                <button id="modal-delete" class="btn btn-danger">🗑️ Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="toast-container"></div>

    <script src="assets/js/app.js"></script>
</body>
</html>

