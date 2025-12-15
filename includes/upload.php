<?php
// File: includes/upload.php
// Purpose: Handle profile picture upload with validation, resizing, and thumbnail creation using GD

declare(strict_types=1);

function handle_profile_upload(array $file, int $userId): bool|string {
    $uploadDir = __DIR__ . "/../data/users/{$userId}/uploads/";
    $targetFile = $uploadDir . 'profile.jpg';
    $thumbFile = $uploadDir . 'profile_thumb.jpg';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Upload error.';
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return 'File too large (max 2MB).';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png'])) {
        return 'Only JPG and PNG allowed.';
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        return 'Failed to save image.';
    }

    // Create 200x200 thumbnail
    list($width, $height) = getimagesize($targetFile);
    $thumbSize = 200;
    $thumb = imagecreatetruecolor($thumbSize, $thumbSize);

    if ($mime === 'image/jpeg') {
        $source = imagecreatefromjpeg($targetFile);
    } else {
        $source = imagecreatefrompng($targetFile);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    // Crop to square from center
    $src_x = $src_y = 0;
    $src_w = $width;
    $src_h = $height;
    if ($width > $height) {
        $src_x = intval(($width - $height) / 2);
        $src_w = $height;
    } elseif ($height > $width) {
        $src_y = intval(($height - $width) / 2);
        $src_h = $width;
    }

    imagecopyresampled($thumb, $source, 0, 0, $src_x, $src_y, $thumbSize, $thumbSize, $src_w, $src_h);

    imagejpeg($thumb, $thumbFile, 85);

    imagedestroy($source);
    imagedestroy($thumb);

    return true;
}