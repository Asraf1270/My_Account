<?php
// File: pages/uploads_action.php
// Purpose: Handle file upload and delete securely

declare(strict_types=1);
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/file_handler.php';

if (!is_logged_in()) {
    $_SESSION['upload_message'] = 'Unauthorized access.';
    header('Location: /pages/uploads.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$uploadDir = __DIR__ . "/../data/users/{$userId}/uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$action = $_POST['action'] ?? 'upload';

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['upload_message'] = 'Invalid CSRF token.';
    header('Location: /pages/uploads.php');
    exit;
}

if ($action === 'upload' && isset($_FILES['userfile'])) {
    $file = $_FILES['userfile'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['upload_message'] = 'Upload error.';
        header('Location: /pages/uploads.php');
        exit;
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        $_SESSION['upload_message'] = 'File too large (max 5MB).';
        header('Location: /pages/uploads.php');
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, ALLOWED_MIME)) {
        $_SESSION['upload_message'] = 'File type not allowed.';
        header('Location: /pages/uploads.php');
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        $_SESSION['upload_message'] = 'Extension not allowed.';
        header('Location: /pages/uploads.php');
        exit;
    }

    $safeName = uniqid('file_', true) . '.' . $ext;
    $destPath = $uploadDir . $safeName;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        // Create thumbnail for images
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            create_thumbnail($destPath, $uploadDir . 'thumb_' . $safeName);
        }

        $_SESSION['upload_message'] = 'File uploaded successfully!';
    } else {
        $_SESSION['upload_message'] = 'Failed to save file.';
    }

} elseif ($action === 'delete') {
    $filename = basename($_POST['filename'] ?? '');
    if (empty($filename)) {
        echo json_encode(['success' => false, 'error' => 'No file specified']);
        exit;
    }

    $path = $uploadDir . $filename;
    $thumb = $uploadDir . 'thumb_' . $filename;

    if (file_exists($path) && unlink($path)) {
        if (file_exists($thumb)) unlink($thumb);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Delete failed']);
    }
    exit;
}

header('Location: /pages/uploads.php');