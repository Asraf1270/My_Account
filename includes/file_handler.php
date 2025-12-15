<?php
// File: includes/file_handler.php
// Purpose: Secure file upload handling, validation, thumbnail creation

declare(strict_types=1);

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx'];
const ALLOWED_MIME = [
    'image/jpeg', 'image/png', 'image/gif',
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

function sanitize_filename(string $filename): string {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return substr($filename, 0, 100); // limit length
}

function get_file_icon(string $ext): string {
    return match(strtolower($ext)) {
        'pdf' => 'fa-file-pdf text-red-600',
        'docx' => 'fa-file-word text-blue-600',
        'jpg', 'jpeg', 'png', 'gif' => 'fa-file-image text-green-600',
        default => 'fa-file text-gray-600'
    };
}

function create_thumbnail(string $sourcePath, string $destPath, int $maxSize = 300): bool {
    list($width, $height, $type) = getimagesize($sourcePath);
    if (!$width || !$height) return false;

    $ratio = $width / $height;
    if ($ratio > 1) {
        $newWidth = $maxSize;
        $newHeight = intval($maxSize / $ratio);
    } else {
        $newHeight = $maxSize;
        $newWidth = intval($maxSize * $ratio);
    }

    $thumb = imagecreatetruecolor($newWidth, $newHeight);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }

    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $result = imagejpeg($thumb, $destPath, 85);

    imagedestroy($source);
    imagedestroy($thumb);

    return $result;
}

function get_user_files(int $userId): array {
    $uploadDir = __DIR__ . "/../data/users/{$userId}/uploads/";
    $files = [];

    if (!is_dir($uploadDir)) return $files;

    foreach (scandir($uploadDir) as $file) {
        if ($file === '.' || $file === '..' || strpos($file, 'thumb_') === 0) continue;

        $path = $uploadDir . $file;
        if (!is_file($path)) continue;

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $originalName = $file; // In real app, store original name in DB or metadata

        $files[] = [
            'stored_name' => $file,
            'original_name' => $originalName,
            'extension' => $ext,
            'size' => filesize($path),
            'uploaded_at' => filemtime($path),
            'icon' => get_file_icon($ext)
        ];
    }

    // Sort by upload time descending
    usort($files, fn($a, $b) => $b['uploaded_at'] - $a['uploaded_at']);

    return $files;
}