<?php
// upload_image.php
require_once 'config.php'; // Ensure config is loaded for any helper functions if needed, though we might just use standard PHP here

// Allowed origins to prevent CSRF if needed, but for this local admin panel we can be lenient or strict
// header('Access-Control-Allow-Origin: *'); 

// Use UPLOAD_DIR from config
$imageFolder = UPLOAD_DIR;

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    return;
}

reset($_FILES);
$temp = current($_FILES);

if (is_uploaded_file($temp['tmp_name'])) {

    // Validate image
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $temp['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);

    if (!in_array(strtolower($ext), $allowed)) {
        header("HTTP/1.1 400 Invalid extension.");
        echo json_encode(['error' => 'Hanya file gambar (jpg, png, gif, webp) yang diperbolehkan.']);
        return;
    }

    // Generate unique name
    $newFilename = time() . '_' . rand(100, 999) . '.' . $ext;
    $filetowrite = $imageFolder . $newFilename;

    if (move_uploaded_file($temp['tmp_name'], $filetowrite)) {
        // Return JSON response for TinyMCE
        echo json_encode(['location' => $filetowrite]);
    } else {
        header("HTTP/1.1 500 Server Error");
        echo json_encode(['error' => 'Gagal mengupload gambar.']);
    }
} else {
    header("HTTP/1.1 500 Server Error");
    echo json_encode(['error' => 'Tidak ada file yang diupload.']);
}
?>