<?php
if (isset($_GET['file'])) {
    $file = $_GET['file'];
    if (file_exists($file)) {
        // Clear the log file
        file_put_contents($file, '');
        echo 'Logs cleared successfully.';
    } else {
        echo 'Log file does not exist.';
    }
} else {
    echo 'No file specified.';
}