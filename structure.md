# Photo Transfer - Project Structure & Work Plan

## Project Overview

A lightweight, browser-based photo sharing application that allows users to transfer photos between devices using unique identifier codes. The system operates without a database, using JSON file storage for metadata management.

---

## Technical Description

### Architecture
- **Type**: Single-page web application with PHP backend
- **Pattern**: Simple MVC-like structure (no framework)
- **Storage**: File-based (JSON metadata + filesystem for images)
- **Communication**: AJAX requests between frontend and PHP API endpoints

### Data Flow
```
[Sender] → Enter/Generate Code → Upload Photos → Photos stored in /uploads/{code}/
                                                      ↓
                                              JSON index updated
                                                      ↓
[Receiver] → Enter Same Code → Fetch Photos List → Preview/Download → Optional Delete
```

### Storage Structure
```
/uploads/
    └── {unique_code}/
            ├── image1.jpg
            ├── image2.png
            └── ...

/data/
    └── transfers.json
```

### JSON Schema (`transfers.json`)
```json
{
    "ABC123": {
        "created_at": "2026-01-05 10:30:00",
        "files": [
            {"name": "image1.jpg", "original_name": "vacation.jpg", "size": 102400},
            {"name": "image2.png", "original_name": "family.png", "size": 204800}
        ]
    },
    "XYZ789": {
        "created_at": "2026-01-05 11:00:00",
        "files": [...]
    }
}
```

---

## Tech Stack

| Layer      | Technology |
|------------|------------|
| Frontend   | HTML5, CSS3, Vanilla JavaScript |
| Backend    | PHP 7.4+   |
| Storage    | JSON file + Filesystem |
| Server     | Apache (XAMPP) |

---

## File Structure

```
photo-transfer/
│
├── index.php                 # Main entry point (single page app)
│
├── api/
│   ├── upload.php            # Handle file uploads
│   ├── fetch.php             # Get photos by code
│   ├── download.php          # Download single/all photos
│   └── delete.php            # Delete photos/session
│
├── assets/
│   ├── css/
│   │   └── style.css         # Main stylesheet
│   └── js/
│       └── app.js            # Main JavaScript (AJAX, UI logic)
│
├── data/
│   └── transfers.json        # Metadata storage (auto-created)
│
├── uploads/                  # Photo storage (auto-created)
│   └── {code}/               # Subdirectories per transfer code
│
├── includes/
│   └── helpers.php           # Utility functions (JSON read/write, etc.)
│
└── structure.md              # This file
```

---

## Work Plan

### Phase 1: Setup & Core Structure
- [x] Initialize project with git
- [ ] Create folder structure (`api/`, `assets/`, `data/`, `uploads/`, `includes/`)
- [ ] Create `includes/helpers.php` with utility functions
- [ ] Create base `index.php` with HTML structure
- [ ] Create `assets/css/style.css` with basic styling
- [ ] Create `assets/js/app.js` with core logic skeleton

### Phase 2: Upload Functionality (Sender Side)
- [ ] Build upload form UI in `index.php`
- [ ] Implement code generation (6-char alphanumeric)
- [ ] Create `api/upload.php` endpoint
  - Accept multiple file uploads
  - Create directory under `/uploads/{code}/`
  - Store file metadata in `transfers.json`
  - Return success/error response
- [ ] Add JavaScript for:
  - File selection with preview
  - AJAX upload with progress
  - Display generated/entered code
  - Show upload success with share code

### Phase 3: Download Functionality (Receiver Side)
- [ ] Build "Enter Code" form UI
- [ ] Create `api/fetch.php` endpoint
  - Read `transfers.json` for matching code
  - Return list of files with metadata
- [ ] Create `api/download.php` endpoint
  - Single file download
  - All files as ZIP download
- [ ] Add JavaScript for:
  - Code submission
  - Photo gallery/preview display
  - Individual download buttons
  - "Download All" button

### Phase 4: Delete Functionality
- [ ] Create `api/delete.php` endpoint
  - Delete single file
  - Delete entire transfer session
  - Update `transfers.json` accordingly
  - Remove files/folders from filesystem
- [ ] Add delete buttons to UI (both sender & receiver views)
- [ ] Confirmation dialogs before deletion

### Phase 5: Polish & Testing
- [ ] Responsive design adjustments
- [ ] Error handling improvements
- [ ] Loading states and user feedback
- [ ] File type validation (images only)
- [ ] File size limits
- [ ] Basic testing across browsers

---

## API Endpoints Summary

| Endpoint | Method | Parameters | Description |
|----------|--------|------------|-------------|
| `api/upload.php` | POST | `code`, `files[]` | Upload photos to a transfer session |
| `api/fetch.php` | GET | `code` | Get list of photos for a code |
| `api/download.php` | GET | `code`, `file` (optional) | Download single file or all as ZIP |
| `api/delete.php` | POST | `code`, `file` (optional) | Delete single file or entire session |

---

## Security Considerations (Basic)

- Sanitize file names to prevent directory traversal
- Validate file types (only allow image MIME types)
- Limit file size (e.g., 10MB per file)
- Use random generated codes (harder to guess)
- No authentication required (by design - simplicity focus)

---

## Notes

- Codes are case-insensitive (stored/compared in uppercase)
- No expiration mechanism (can be added later)
- No user accounts or authentication
- ZIP download requires PHP ZipArchive extension
