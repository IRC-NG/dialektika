<?php
// Database Configuration - Using environment variables for production
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'lab');
define('DB_PASS', getenv('DB_PASSWORD') ?: 'password');
define('DB_NAME', getenv('DB_NAME') ?: 'portal_berita');

// Upload Directory Configuration
define('UPLOAD_DIR', getenv('UPLOAD_DIR') ?: 'uploads/');

// Connect to Database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check Connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input
function sanitize($data)
{
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags($data)));
}

// Function to upload file
function uploadFile($file, $target_dir = null)
{
    // Use default upload directory if not specified
    if ($target_dir === null) {
        $target_dir = UPLOAD_DIR;
    }

    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Create uploads directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Create uploads directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Get file extension
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    // Generate unique filename to prevent overwriting
    $unique_name = uniqid() . '_' . time() . '.' . $imageFileType;
    $target_file = $target_dir . $unique_name;

    // Allow certain file formats
    $allowed_extensions = array("jpg", "jpeg", "png", "gif", "pdf", "doc", "docx", "mp3", "mp4", "avi", "mov", "html");

    if (!in_array($imageFileType, $allowed_extensions)) {
        return false;
    }

    // Check file size (max 50MB)
    if ($file["size"] > 50000000) {
        return false;
    }

    // Try to upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $unique_name;
    } else {
        return false;
    }
}
?>
