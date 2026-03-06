# File Transfer

A simple, elegant file sharing application that allows users to share files instantly using unique 6-character codes. No registration required, no database needed.

![File Transfer](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## Features

- **Instant Sharing** - Generate a unique 6-character code and start sharing immediately
- **No Registration** - No sign-up required, just generate a code and share
- **Multiple File Types** - Support for images, documents, archives, audio, video, and more
- **Drag & Drop** - Easy file upload with drag and drop support
- **File Previews** - Image thumbnails and file type icons for easy identification
- **Bulk Operations** - Upload, download, and delete multiple files at once
- **ZIP Download** - Download all files as a single ZIP archive
- **Responsive Design** - Works beautifully on desktop and mobile devices
- **Dark Theme** - Modern, eye-friendly dark interface
- **Security First** - File validation, sanitization, and dangerous file type blocking

## Screenshots

The application features a clean, minimal dark interface with:
- Code entry/generation screen
- Drag & drop upload area with file previews
- Gallery view with thumbnails and file icons
- Modal preview for images and file details

## Tech Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript
- **Backend**: PHP 7.4+
- **Storage**: JSON file-based (no database required)
- **Icons**: Font Awesome 6.5
- **Fonts**: Inter (Google Fonts)

## Installation

### Requirements

- PHP 7.4 or higher
- Apache/Nginx web server
- Write permissions for `uploads/` and `data/` directories

### Steps

1. **Clone or download** this repository to your web server:
   ```bash
   git clone https://github.com/yourusername/file-transfer.git
   ```

2. **Navigate** to the project directory:
   ```bash
   cd file-transfer
   ```

3. **Set permissions** for upload and data directories:
   ```bash
   chmod 755 uploads/
   chmod 755 data/
   ```

4. **Configure PHP** (optional) - For larger file uploads, update `php.ini`:
   ```ini
   upload_max_filesize = 50M
   post_max_size = 55M
   max_file_uploads = 20
   ```

5. **Access** the application via your web browser:
   ```
   http://localhost/file-transfer/
   ```

## Usage

### Sharing Files

1. Click **"Generate"** to create a new 6-character code
2. **Drag & drop** files or click **"Browse Files"** to select files
3. Click **"Upload Files"** to upload
4. **Share the code** with your recipient

### Receiving Files

1. Enter the **6-character code** you received
2. Click **"Proceed"** to view shared files
3. **Download individual files** or **Download All** as ZIP

## Supported File Types

### Images
`JPG` `JPEG` `PNG` `GIF` `WEBP` `BMP` `SVG` `ICO` `TIFF`

### Documents
`PDF` `DOC` `DOCX` `XLS` `XLSX` `PPT` `PPTX` `TXT` `RTF` `CSV` `MD`

### Archives
`ZIP` `RAR` `7Z` `TAR` `GZ`

### Audio
`MP3` `WAV` `OGG` `FLAC` `AAC` `M4A`

### Video
`MP4` `WEBM` `AVI` `MOV` `MKV` `WMV`

### Other
`HTML` `CSS` `JSON` `XML` `SQL` `TTF` `OTF` `WOFF` `WOFF2`

## File Size Limits

- **Per file**: 50MB maximum
- **Total**: Unlimited (server storage dependent)

## Security Features

- **File Extension Validation** - Only allowed file types can be uploaded
- **Dangerous File Blocking** - PHP, executable, and script files are blocked
- **Content Scanning** - Files are scanned for embedded PHP code
- **Filename Sanitization** - Filenames are sanitized to prevent directory traversal
- **Unique Naming** - Files are renamed with timestamps to prevent overwrites
- **No Direct Execution** - Uploaded files cannot be executed by the server

### Blocked File Types

For security, the following file types are blocked:
- PHP files (`.php`, `.phtml`, `.phar`)
- Executables (`.exe`, `.bat`, `.cmd`, `.sh`)
- Scripts (`.js`, `.vbs`, `.ps1`)
- System files (`.dll`, `.so`, `.htaccess`)

## Project Structure

```
file-transfer/
├── api/
│   ├── upload.php      # File upload endpoint
│   ├── fetch.php       # Retrieve files by code
│   ├── download.php    # Single/bulk file download
│   └── delete.php      # Delete files
├── assets/
│   ├── css/
│   │   └── style.css   # Application styles
│   └── js/
│       └── app.js      # Frontend JavaScript
├── data/
│   └── transfers.json  # Transfer metadata storage
├── includes/
│   └── helpers.php     # Helper functions
├── uploads/            # File storage directory
├── index.php           # Main application
├── LICENSE             # MIT License
└── README.md           # This file
```

## API Endpoints

### Generate Code
```
GET /api/upload.php?action=generate
```
Returns: `{ success: true, data: { code: "ABC123" } }`

### Upload Files
```
POST /api/upload.php
Content-Type: multipart/form-data

code: ABC123
files[]: (binary)
```

### Fetch Files
```
GET /api/fetch.php?code=ABC123
```
Returns: `{ success: true, data: { files: [...] } }`

### Download File
```
GET /api/download.php?code=ABC123&file=filename.jpg
GET /api/download.php?code=ABC123&all=1  (ZIP download)
```

### Delete File
```
POST /api/delete.php
Content-Type: application/json

{ "code": "ABC123", "file": "filename.jpg" }
{ "code": "ABC123", "all": true }
```

## Configuration

### PHP Settings (`php.ini`)

For optimal performance with large files:

```ini
upload_max_filesize = 50M
post_max_size = 55M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
max_file_uploads = 20
```

### Apache Settings (`.htaccess`)

The application includes security headers. For additional configuration:

```apache
# Prevent PHP execution in uploads
<Directory "uploads">
    php_flag engine off
</Directory>
```

## Customization

### Changing Max File Size

Update in two places:

1. `api/upload.php`:
   ```php
   $maxFileSize = 50 * 1024 * 1024; // Change 50 to desired MB
   ```

2. `assets/js/app.js`:
   ```javascript
   maxFileSize: 50 * 1024 * 1024 // Change 50 to desired MB
   ```

### Adding File Types

Update `includes/helpers.php`:

1. Add extension to `getAllowedExtensions()`
2. Add MIME type to `getMimeTypeByExtension()`
3. Add icon mapping to `getFileIcon()`
4. Update `assets/js/app.js` `FILE_CONFIG` object

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- [Font Awesome](https://fontawesome.com/) for icons
- [Google Fonts](https://fonts.google.com/) for the Inter typeface
- Built with vanilla HTML, CSS, and JavaScript

---

Made with ❤️ for simple, secure file sharing
