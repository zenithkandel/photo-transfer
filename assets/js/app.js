/**
 * File Transfer - Main Application JavaScript
 */

// Theme handling
const ThemeManager = {
    init() {
        const toggle = document.getElementById('theme-toggle');
        if (toggle) {
            toggle.addEventListener('click', () => this.toggle());
        }
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
            }
        });
    },
    
    toggle() {
        const current = document.documentElement.getAttribute('data-theme');
        const newTheme = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    },
    
    isDark() {
        return document.documentElement.getAttribute('data-theme') === 'dark';
    }
};

// Initialize theme manager immediately
ThemeManager.init();

// File type configurations
const FILE_CONFIG = {
    // Icons mapping by extension
    icons: {
        // Images
        jpg: 'fa-file-image', jpeg: 'fa-file-image', png: 'fa-file-image',
        gif: 'fa-file-image', webp: 'fa-file-image', bmp: 'fa-file-image',
        svg: 'fa-file-image', ico: 'fa-file-image', tiff: 'fa-file-image',
        tif: 'fa-file-image', psd: 'fa-file-image',
        
        // Documents
        pdf: 'fa-file-pdf',
        doc: 'fa-file-word', docx: 'fa-file-word',
        xls: 'fa-file-excel', xlsx: 'fa-file-excel', csv: 'fa-file-excel',
        ppt: 'fa-file-powerpoint', pptx: 'fa-file-powerpoint',
        txt: 'fa-file-lines', rtf: 'fa-file-lines', md: 'fa-file-lines',
        
        // Archives
        zip: 'fa-file-zipper', rar: 'fa-file-zipper', '7z': 'fa-file-zipper',
        tar: 'fa-file-zipper', gz: 'fa-file-zipper',
        
        // Audio
        mp3: 'fa-file-audio', wav: 'fa-file-audio', ogg: 'fa-file-audio',
        flac: 'fa-file-audio', aac: 'fa-file-audio', m4a: 'fa-file-audio',
        
        // Video
        mp4: 'fa-file-video', webm: 'fa-file-video', avi: 'fa-file-video',
        mov: 'fa-file-video', mkv: 'fa-file-video', wmv: 'fa-file-video',
        
        // Code
        html: 'fa-file-code', htm: 'fa-file-code', css: 'fa-file-code',
        js: 'fa-file-code', json: 'fa-file-code', xml: 'fa-file-code',
        sql: 'fa-database',
        
        // Fonts
        ttf: 'fa-font', otf: 'fa-font', woff: 'fa-font', woff2: 'fa-font',
        
        // Design
        ai: 'fa-bezier-curve', eps: 'fa-bezier-curve'
    },
    
    // Extensions that can be previewed as images
    imageExtensions: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico'],
    
    // Allowed extensions
    allowedExtensions: [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'ico', 'tiff', 'tif',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'txt', 'rtf', 'csv',
        'zip', 'rar', '7z', 'tar', 'gz',
        'mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a',
        'mp4', 'webm', 'avi', 'mov', 'mkv', 'wmv',
        'html', 'htm', 'css', 'js', 'json', 'xml', 'md', 'sql',
        'ttf', 'otf', 'woff', 'woff2',
        'psd', 'ai', 'eps'
    ],
    
    maxFileSize: 50 * 1024 * 1024 // 50MB
};

// Helper function to get file extension
function getFileExtension(filename) {
    return filename.split('.').pop().toLowerCase();
}

// Helper function to get icon for file
function getFileIcon(filename) {
    const ext = getFileExtension(filename);
    return FILE_CONFIG.icons[ext] || 'fa-file';
}

// Helper function to check if file is an image
function isImageFile(filename) {
    const ext = getFileExtension(filename);
    return FILE_CONFIG.imageExtensions.includes(ext);
}

// Helper function to check if file extension is allowed
function isAllowedExtension(filename) {
    const ext = getFileExtension(filename);
    return FILE_CONFIG.allowedExtensions.includes(ext);
}

// Global State
const state = {
    currentCode: '',
    selectedFiles: [],
    currentModalFile: null,
    uploadedBytes: 0,
    totalBytes: 0
};

// DOM Elements
const elements = {
    // Sections
    codeSection: document.getElementById('code-section'),
    uploadSection: document.getElementById('upload-section'),
    gallerySection: document.getElementById('gallery-section'),
    backNav: document.getElementById('back-nav'),
    
    // Code input
    transferCode: document.getElementById('transfer-code'),
    btnGenerate: document.getElementById('btn-generate'),
    btnProceed: document.getElementById('btn-proceed'),
    
    // Upload
    displayCode: document.getElementById('display-code'),
    uploadArea: document.getElementById('upload-area'),
    fileInput: document.getElementById('file-input'),
    previewContainer: document.getElementById('preview-container'),
    previewGrid: document.getElementById('preview-grid'),
    previewCount: document.getElementById('preview-count'),
    btnClear: document.getElementById('btn-clear'),
    btnUpload: document.getElementById('btn-upload'),
    btnCopyCode: document.getElementById('btn-copy-code'),
    
    // Progress
    uploadProgress: document.getElementById('upload-progress'),
    progressFill: document.getElementById('progress-fill'),
    progressText: document.getElementById('progress-text'),
    progressPercent: document.getElementById('progress-percent'),
    progressSize: document.getElementById('progress-size'),
    
    // Gallery
    galleryCode: document.getElementById('gallery-code'),
    galleryLoading: document.getElementById('gallery-loading'),
    galleryEmpty: document.getElementById('gallery-empty'),
    galleryContent: document.getElementById('gallery-content'),
    galleryGrid: document.getElementById('gallery-grid'),
    fileCount: document.getElementById('file-count'),
    totalSize: document.getElementById('total-size'),
    btnUploadNew: document.getElementById('btn-upload-new'),
    btnDownloadAll: document.getElementById('btn-download-all'),
    btnAddMore: document.getElementById('btn-add-more'),
    btnDeleteAll: document.getElementById('btn-delete-all'),
    btnCopyCodeGallery: document.getElementById('btn-copy-code-gallery'),
    
    // Modal
    imageModal: document.getElementById('image-modal'),
    modalImage: document.getElementById('modal-image'),
    modalFilename: document.getElementById('modal-filename'),
    modalClose: document.getElementById('modal-close'),
    modalDownload: document.getElementById('modal-download'),
    modalDelete: document.getElementById('modal-delete'),
    modalFilePreview: document.getElementById('modal-file-preview'),
    modalFileIcon: document.getElementById('modal-file-icon'),
    modalFileType: document.getElementById('modal-file-type'),
    
    // Navigation
    btnBack: document.getElementById('btn-back'),
    
    // Toast
    toastContainer: document.getElementById('toast-container')
};

// Initialize
document.addEventListener('DOMContentLoaded', init);

function init() {
    bindEvents();
    
    // Check URL for code parameter
    const urlParams = new URLSearchParams(window.location.search);
    const codeParam = urlParams.get('code');
    if (codeParam) {
        elements.transferCode.value = codeParam.toUpperCase();
        proceed();
    }
}

// Event Bindings
function bindEvents() {
    // Code section
    elements.btnGenerate.addEventListener('click', generateCode);
    elements.btnProceed.addEventListener('click', proceed);
    elements.transferCode.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') proceed();
    });
    elements.transferCode.addEventListener('input', (e) => {
        e.target.value = e.target.value.toUpperCase();
    });
    
    // Upload area
    elements.uploadArea.addEventListener('click', (e) => {
        // Only trigger if clicking on the area itself, not on the label/button
        if (e.target === elements.uploadArea || 
            e.target.closest('.upload-icon') || 
            e.target.tagName === 'H3' ||
            e.target.tagName === 'P') {
            elements.fileInput.click();
        }
    });
    elements.fileInput.addEventListener('change', handleFileSelect);
    elements.uploadArea.addEventListener('dragover', handleDragOver);
    elements.uploadArea.addEventListener('dragleave', handleDragLeave);
    elements.uploadArea.addEventListener('drop', handleDrop);
    
    // Upload actions
    elements.btnClear.addEventListener('click', clearSelectedFiles);
    elements.btnUpload.addEventListener('click', uploadFiles);
    elements.btnCopyCode.addEventListener('click', () => copyCode(state.currentCode));
    
    // Gallery actions
    elements.btnUploadNew.addEventListener('click', showUploadSection);
    elements.btnAddMore.addEventListener('click', showUploadSection);
    elements.btnDownloadAll.addEventListener('click', downloadAll);
    elements.btnDeleteAll.addEventListener('click', deleteAll);
    elements.btnCopyCodeGallery.addEventListener('click', () => copyCode(state.currentCode));
    
    // Modal
    elements.modalClose.addEventListener('click', closeModal);
    elements.imageModal.querySelector('.modal-overlay').addEventListener('click', closeModal);
    elements.modalDownload.addEventListener('click', downloadCurrentModal);
    elements.modalDelete.addEventListener('click', deleteCurrentModal);
    
    // Navigation
    elements.btnBack.addEventListener('click', goBack);
    
    // Keyboard events
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !elements.imageModal.classList.contains('hidden')) {
            closeModal();
        }
    });
}

// Code Generation & Proceeding
async function generateCode() {
    try {
        const response = await fetch('api/upload.php?action=generate');
        const data = await response.json();
        
        if (data.success) {
            elements.transferCode.value = data.data.code;
            showToast('New code generated!', 'success');
        } else {
            showToast(data.message || 'Failed to generate code', 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
        console.error(error);
    }
}

async function proceed() {
    const code = elements.transferCode.value.trim().toUpperCase();
    
    if (!code) {
        showToast('Please enter a code', 'warning');
        elements.transferCode.focus();
        return;
    }
    
    if (code.length !== 6) {
        showToast('Code must be 6 characters', 'warning');
        elements.transferCode.focus();
        return;
    }
    
    state.currentCode = code;
    
    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('code', code);
    window.history.pushState({}, '', url);
    
    // Check if transfer exists and has files
    await loadGallery();
}

// File Selection & Preview
function handleFileSelect(e) {
    addFiles(e.target.files);
}

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    elements.uploadArea.classList.add('dragover');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    elements.uploadArea.classList.remove('dragover');
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    elements.uploadArea.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    addFiles(files);
}

function addFiles(fileList) {
    const validFiles = [];
    const errors = [];
    
    Array.from(fileList).forEach(file => {
        // Check file extension
        if (!isAllowedExtension(file.name)) {
            errors.push(`${file.name}: File type not supported`);
            return;
        }
        
        // Check file size
        if (file.size > FILE_CONFIG.maxFileSize) {
            errors.push(`${file.name}: Exceeds 50MB limit`);
            return;
        }
        
        validFiles.push(file);
    });
    
    if (errors.length > 0) {
        showToast(errors[0], 'warning');
        if (errors.length > 1) {
            console.warn('File validation errors:', errors);
        }
    }
    
    if (validFiles.length === 0 && errors.length > 0) {
        return;
    }
    
    state.selectedFiles = [...state.selectedFiles, ...validFiles];
    updatePreview();
}

function updatePreview() {
    if (state.selectedFiles.length === 0) {
        elements.previewContainer.classList.add('hidden');
        return;
    }
    
    elements.previewContainer.classList.remove('hidden');
    elements.previewCount.textContent = state.selectedFiles.length;
    elements.previewGrid.innerHTML = '';
    
    state.selectedFiles.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'preview-item';
        
        // Check if file is an image that can be previewed
        if (isImageFile(file.name) && file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = file.name;
            item.appendChild(img);
        } else {
            // Show file icon for non-images
            item.classList.add('file-type-preview');
            const iconWrapper = document.createElement('div');
            iconWrapper.className = 'file-icon-wrapper';
            iconWrapper.innerHTML = `
                <i class="fas ${getFileIcon(file.name)}"></i>
                <span class="file-ext">${getFileExtension(file.name).toUpperCase()}</span>
            `;
            item.appendChild(iconWrapper);
        }
        
        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-btn';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.onclick = (e) => {
            e.stopPropagation();
            removeFile(index);
        };
        
        item.appendChild(removeBtn);
        elements.previewGrid.appendChild(item);
    });
}

function removeFile(index) {
    state.selectedFiles.splice(index, 1);
    updatePreview();
}

function clearSelectedFiles() {
    state.selectedFiles = [];
    elements.fileInput.value = '';
    updatePreview();
}

// File Upload
async function uploadFiles() {
    if (state.selectedFiles.length === 0) {
        showToast('No files selected', 'warning');
        return;
    }
    
    const formData = new FormData();
    formData.append('code', state.currentCode);
    
    // Calculate total size
    state.totalBytes = state.selectedFiles.reduce((acc, file) => acc + file.size, 0);
    state.uploadedBytes = 0;
    
    state.selectedFiles.forEach((file, index) => {
        formData.append('files[]', file);
    });
    
    // Show progress
    elements.uploadProgress.classList.remove('hidden');
    elements.btnUpload.disabled = true;
    elements.btnClear.disabled = true;
    elements.progressText.textContent = 'Preparing upload...';
    elements.progressPercent.textContent = '0%';
    elements.progressSize.textContent = `0 / ${formatFileSize(state.totalBytes)}`;
    
    try {
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                elements.progressFill.style.width = percent + '%';
                elements.progressPercent.textContent = percent + '%';
                elements.progressText.textContent = percent < 100 ? 'Uploading...' : 'Processing...';
                elements.progressSize.textContent = `${formatFileSize(e.loaded)} / ${formatFileSize(e.total)}`;
            }
        });
        
        xhr.onload = function() {
            elements.uploadProgress.classList.add('hidden');
            elements.btnUpload.disabled = false;
            elements.btnClear.disabled = false;
            elements.progressFill.style.width = '0%';
            
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showToast(`${response.data.uploaded} file(s) uploaded successfully!`, 'success');
                    clearSelectedFiles();
                    loadGallery();
                } else {
                    showToast(response.message || 'Upload failed', 'error');
                }
            } else {
                showToast('Upload failed', 'error');
            }
        };
        
        xhr.onerror = function() {
            elements.uploadProgress.classList.add('hidden');
            elements.btnUpload.disabled = false;
            elements.btnClear.disabled = false;
            showToast('Connection error', 'error');
        };
        
        xhr.open('POST', 'api/upload.php');
        xhr.send(formData);
        
    } catch (error) {
        elements.uploadProgress.classList.add('hidden');
        elements.btnUpload.disabled = false;
        elements.btnClear.disabled = false;
        showToast('Upload failed', 'error');
        console.error(error);
    }
}

// Gallery
async function loadGallery() {
    // Hide other sections
    elements.codeSection.classList.add('hidden');
    elements.uploadSection.classList.add('hidden');
    elements.gallerySection.classList.remove('hidden');
    elements.backNav.classList.remove('hidden');
    
    // Show loading
    elements.galleryLoading.classList.remove('hidden');
    elements.galleryEmpty.classList.add('hidden');
    elements.galleryContent.classList.add('hidden');
    
    // Update code display
    elements.galleryCode.textContent = state.currentCode;
    
    try {
        const response = await fetch(`api/fetch.php?code=${state.currentCode}`);
        const data = await response.json();
        
        elements.galleryLoading.classList.add('hidden');
        
        if (data.success && data.data.files && data.data.files.length > 0) {
            renderGallery(data.data);
        } else {
            // No files - show empty state or upload section
            elements.galleryEmpty.classList.remove('hidden');
        }
    } catch (error) {
        elements.galleryLoading.classList.add('hidden');
        elements.galleryEmpty.classList.remove('hidden');
        showToast('Failed to load files', 'error');
        console.error(error);
    }
}

function renderGallery(data) {
    const files = data.files;
    
    elements.galleryContent.classList.remove('hidden');
    elements.fileCount.textContent = `${files.length} file${files.length !== 1 ? 's' : ''}`;
    
    // Calculate total size
    const totalBytes = files.reduce((acc, file) => acc + file.size, 0);
    elements.totalSize.textContent = formatFileSize(totalBytes);
    
    // Render grid
    elements.galleryGrid.innerHTML = '';
    
    files.forEach(file => {
        const item = document.createElement('div');
        item.className = 'gallery-item';
        item.onclick = () => openModal(file);
        
        const isImage = isImageFile(file.original_name || file.name);
        
        if (isImage) {
            const img = document.createElement('img');
            img.src = `uploads/${state.currentCode}/${file.name}`;
            img.alt = file.original_name;
            img.loading = 'lazy';
            item.appendChild(img);
        } else {
            // File type preview
            item.classList.add('file-type-item');
            const filePreview = document.createElement('div');
            filePreview.className = 'file-type-preview-gallery';
            const iconClass = file.icon || getFileIcon(file.original_name || file.name);
            const ext = getFileExtension(file.original_name || file.name);
            filePreview.innerHTML = `
                <i class="fas ${iconClass}"></i>
                <span class="file-ext-badge">${ext.toUpperCase()}</span>
            `;
            item.appendChild(filePreview);
        }
        
        const overlay = document.createElement('div');
        overlay.className = 'overlay';
        overlay.innerHTML = `
            <div class="file-info">
                <span class="name">${file.original_name}</span>
                <span class="size">${formatFileSize(file.size)}</span>
            </div>
        `;
        
        const actions = document.createElement('div');
        actions.className = 'item-actions';
        
        const downloadBtn = document.createElement('button');
        downloadBtn.className = 'download-btn';
        downloadBtn.innerHTML = '<i class="fas fa-download"></i>';
        downloadBtn.title = 'Download';
        downloadBtn.onclick = (e) => {
            e.stopPropagation();
            downloadFile(file.name);
        };
        
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'delete-btn';
        deleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
        deleteBtn.title = 'Delete';
        deleteBtn.onclick = (e) => {
            e.stopPropagation();
            deleteFile(file.name);
        };
        
        actions.appendChild(downloadBtn);
        actions.appendChild(deleteBtn);
        
        item.appendChild(overlay);
        item.appendChild(actions);
        elements.galleryGrid.appendChild(item);
    });
}

function showUploadSection() {
    elements.gallerySection.classList.add('hidden');
    elements.uploadSection.classList.remove('hidden');
    elements.displayCode.textContent = state.currentCode;
}

// Download Functions
function downloadFile(filename) {
    const link = document.createElement('a');
    link.href = `api/download.php?code=${state.currentCode}&file=${encodeURIComponent(filename)}`;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function downloadAll() {
    showToast('Preparing download...', 'info');
    const link = document.createElement('a');
    link.href = `api/download.php?code=${state.currentCode}&all=1`;
    link.download = `files_${state.currentCode}.zip`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Delete Functions
async function deleteFile(filename) {
    if (!confirm('Delete this file?')) return;
    
    try {
        const response = await fetch('api/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: state.currentCode, file: filename })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('File deleted', 'success');
            loadGallery();
        } else {
            showToast(data.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
        console.error(error);
    }
}

async function deleteAll() {
    if (!confirm('Delete ALL files? This cannot be undone.')) return;
    
    try {
        const response = await fetch('api/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: state.currentCode, all: true })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('All files deleted', 'success');
            goBack();
        } else {
            showToast(data.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showToast('Connection error', 'error');
        console.error(error);
    }
}

// Modal Functions
function openModal(file) {
    state.currentModalFile = file;
    elements.modalFilename.textContent = file.original_name;
    
    const isImage = isImageFile(file.original_name || file.name);
    
    if (isImage) {
        // Show image preview
        elements.modalImage.src = `uploads/${state.currentCode}/${file.name}`;
        elements.modalImage.classList.remove('hidden');
        elements.modalFilePreview.classList.add('hidden');
    } else {
        // Show file icon preview
        elements.modalImage.classList.add('hidden');
        elements.modalFilePreview.classList.remove('hidden');
        const iconClass = file.icon || getFileIcon(file.original_name || file.name);
        elements.modalFileIcon.className = `fas ${iconClass}`;
        const ext = getFileExtension(file.original_name || file.name);
        elements.modalFileType.textContent = ext.toUpperCase() + ' File';
    }
    
    elements.imageModal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    elements.imageModal.classList.add('hidden');
    document.body.style.overflow = '';
    state.currentModalFile = null;
}

function downloadCurrentModal() {
    if (state.currentModalFile) {
        downloadFile(state.currentModalFile.name);
    }
}

async function deleteCurrentModal() {
    if (state.currentModalFile) {
        closeModal();
        await deleteFile(state.currentModalFile.name);
    }
}

// Navigation
function goBack() {
    // Clear URL parameter
    const url = new URL(window.location);
    url.searchParams.delete('code');
    window.history.pushState({}, '', url);
    
    // Reset state
    state.currentCode = '';
    state.selectedFiles = [];
    
    // Show code section
    elements.codeSection.classList.remove('hidden');
    elements.uploadSection.classList.add('hidden');
    elements.gallerySection.classList.add('hidden');
    elements.backNav.classList.add('hidden');
    
    elements.transferCode.value = '';
    elements.transferCode.focus();
}

// Utility Functions
function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        showToast('Code copied to clipboard!', 'success');
    }).catch(() => {
        // Fallback
        const input = document.createElement('input');
        input.value = code;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        showToast('Code copied!', 'success');
    });
}

function formatFileSize(bytes) {
    if (bytes >= 1073741824) {
        return (bytes / 1073741824).toFixed(2) + ' GB';
    } else if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    }
    return bytes + ' bytes';
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Add icon based on type
    let icon = 'fa-info-circle';
    if (type === 'success') icon = 'fa-check-circle';
    else if (type === 'error') icon = 'fa-exclamation-circle';
    else if (type === 'warning') icon = 'fa-exclamation-triangle';
    
    toast.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;
    
    elements.toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
