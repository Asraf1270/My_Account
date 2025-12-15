<?php
// File: pages/uploads.php
// Purpose: User file upload area with list, thumbnails, download, delete

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/file_handler.php';

require_login();

$userId = (int)$_SESSION['user_id'];
$uploadDir = __DIR__ . "/../data/users/{$userId}/uploads/";
$uploadUrl = "/data/users/{$userId}/uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$files = get_user_files($userId);
$csrfToken = create_csrf_token();

$message = '';
if (isset($_SESSION['upload_message'])) {
    $message = $_SESSION['upload_message'];
    unset($_SESSION['upload_message']);
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Uploads - My Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-full">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">My Files</h1>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md <?= strpos($message, 'successfully') !== false ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Upload New File</h2>
            <form action="/pages/uploads_action.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Choose file (max 5MB)</label>
                        <input type="file" name="userfile" required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-2 text-xs text-gray-500">Allowed: Images (jpg, png, gif), PDF, DOCX</p>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-2 rounded-md hover:bg-indigo-700 flex items-center justify-center gap-2">
                            <i class="fas fa-upload"></i> Upload File
                        </button>
                    </div>
                </div>
                <div class="mt-4">
                    <progress id="progressBar" class="w-full h-2 hidden" value="0" max="100"></progress>
                    <p id="progressText" class="text-sm text-gray-600 mt-2 hidden">Uploading...</p>
                </div>
            </form>
        </div>

        <!-- Files List -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if (empty($files)): ?>
                <p class="col-span-full text-center text-gray-500 py-12">No files uploaded yet.</p>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <?php
                    $filePath = $uploadDir . $file['stored_name'];
                    $fileUrl = $uploadUrl . $file['stored_name'];
                    $thumbPath = $uploadDir . 'thumb_' . $file['stored_name'];
                    $thumbUrl = $uploadUrl . 'thumb_' . $file['stored_name'];
                    $isImage = in_array($file['extension'], ['jpg', 'jpeg', 'png', 'gif']);
                    $hasThumb = $isImage && file_exists($thumbPath);
                    ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="h-48 bg-gray-100 flex items-center justify-center">
                            <?php if ($isImage): ?>
                                <img src="<?= $hasThumb ? $thumbUrl : $fileUrl ?>?t=<?= filemtime($filePath) ?>"
                                     alt="<?= htmlspecialchars($file['original_name']) ?>"
                                     class="max-w-full max-h-full object-contain">
                            <?php else: ?>
                                <i class="fas <?= $file['icon'] ?> text-6xl text-gray-400"></i>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <p class="font-medium text-sm truncate" title="<?= htmlspecialchars($file['original_name']) ?>">
                                <?= htmlspecialchars($file['original_name']) ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?= number_format($file['size'] / 1024, 1) ?> KB • <?= date('M j, Y', $file['uploaded_at']) ?>
                            </p>
                            <div class="flex justify-between mt-4">
                                <a href="<?= $fileUrl ?>" download class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <button onclick="deleteFile('<?= $file['stored_name'] ?>', '<?= htmlspecialchars($file['original_name']) ?>')"
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Progress bar for large uploads
        const form = document.getElementById('uploadForm');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');

        form.addEventListener('submit', function() {
            progressBar.classList.remove('hidden');
            progressText.classList.remove('hidden');
        });

        // XHR for progress (optional enhancement)
        if (window.XMLHttpRequest.prototype.upload) {
            const xhr = new XMLHttpRequest();
            const originalOpen = xhr.open;
            const originalSend = xhr.send;

            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.value = percent;
                    progressText.textContent = `Uploading... ${percent}%`;
                }
            });

            // Override only if needed — here we just show basic progress
        }

        async function deleteFile(storedName, originalName) {
            if (!confirm(`Delete "${originalName}" permanently?`)) return;

            const formData = new FormData();
            formData.append('csrf_token', '<?= $csrfToken ?>');
            formData.append('action', 'delete');
            formData.append('filename', storedName);

            const response = await fetch('/pages/uploads_action.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.error);
            }
        }
    </script>
</body>
</html>