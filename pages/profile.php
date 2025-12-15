<?php
// File: pages/profile.php
// Purpose: View and edit user profile, upload avatar, change password

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

require_login();

$userId = (int)$_SESSION['user_id'];
$usersFile = __DIR__ . '/../data/users.json';
$profileFile = __DIR__ . "/../data/profiles/profile_{$userId}.json";
$avatarPath = "/data/users/{$userId}/uploads/profile.jpg";
$avatarThumbPath = "/data/users/{$userId}/uploads/profile_thumb.jpg";

$users = load_json($usersFile);
$currentUser = $users[$userId] ?? null;
$profile = load_json($profileFile);

$errors = [];
$success = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';

        // === Update Profile Info ===
        if ($action === 'update_profile') {
            $full_name = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if (strlen($full_name) < 2) {
                $errors[] = 'Full name must be at least 2 characters.';
            }

            if (!empty($errors)) {
                // skip
            } else {
                $profile['full_name'] = $full_name;
                $profile['phone'] = $phone;
                save_json($profileFile, $profile);
                $success[] = 'Profile updated successfully.';
            }
        }

        // === Change Password ===
        elseif ($action === 'change_password') {
            $current_pass = $_POST['current_password'] ?? '';
            $new_pass = $_POST['new_password'] ?? '';
            $confirm_pass = $_POST['confirm_password'] ?? '';

            if (!password_verify($current_pass, $currentUser['password_hash'])) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($new_pass) < 8 || !preg_match('/[0-9]/', $new_pass) || !preg_match('/[a-zA-Z]/', $new_pass)) {
                $errors[] = 'New password must be 8+ chars with at least 1 letter and 1 number.';
            } elseif ($new_pass !== $confirm_pass) {
                $errors[] = 'New passwords do not match.';
            } else {
                // Atomic update of users.json
                $handle = fopen($usersFile, 'c+');
                flock($handle, LOCK_EX);
                $allUsers = json_decode(fread($handle, filesize($usersFile) ?: 0), true) ?: [];
                $allUsers[$userId]['password_hash'] = password_hash($new_pass, PASSWORD_DEFAULT);
                ftruncate($handle, 0);
                rewind($handle);
                fwrite($handle, json_encode($allUsers, JSON_PRETTY_PRINT));
                flock($handle, LOCK_UN);
                fclose($handle);

                $success[] = 'Password changed successfully.';
            }
        }

        // === Upload Avatar ===
        elseif ($action === 'upload_avatar' && isset($_FILES['avatar'])) {
            $result = handle_profile_upload($_FILES['avatar'], $userId);
            if ($result === true) {
                $success[] = 'Profile picture updated!';
            } else {
                $errors[] = $result; // string error message
            }
        }
    }
}

$csrfToken = create_csrf_token();
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - My Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-full">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">My Profile</h1>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                <ul><?php foreach ($success as $s): ?><li><?= htmlspecialchars($s) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Avatar Section -->
            <div class="text-center">
                <div class="relative inline-block">
                    <?php if (file_exists(__DIR__ . '/../' . $avatarPath)): ?>
                        <img src="<?= $avatarThumbPath ?>?t=<?= filemtime(__DIR__ . '/../' . $avatarThumbPath) ?>"
                             alt="Profile" class="w-40 h-40 rounded-full object-cover border-4 border-white shadow-lg">
                    <?php else: ?>
                        <div class="w-40 h-40 rounded-full bg-indigo-500 flex items-center justify-center text-white text-5xl font-bold shadow-lg">
                            <?= strtoupper(substr($currentUser['username'], 0, 2)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <form method="POST" enctype="multipart/form-data" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="upload_avatar">
                    <label class="block">
                        <span class="sr-only">Choose profile photo</span>
                        <input type="file" name="avatar" accept="image/jpeg,image/png" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </label>
                    <button type="submit" class="mt-3 bg-indigo-600 text-white px-6 py-2 rounded-full hover:bg-indigo-700">
                        Upload New Picture
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-2">Max 2MB, JPG/PNG only</p>
            </div>

            <!-- Profile Form -->
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold mb-4">Personal Information</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Username</label>
                                <input type="text" value="<?= htmlspecialchars($currentUser['username']) ?>" disabled
                                       class="mt-1 block w-full rounded-md bg-gray-100 px-3 py-2 border border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" value="<?= htmlspecialchars($currentUser['email']) ?>" disabled
                                       class="mt-1 block w-full rounded-md bg-gray-100 px-3 py-2 border border-gray-300">
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold mb-4">Change Password</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="change_password">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input type="password" name="current_password" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">New Password</label>
                                <input type="password" name="new_password" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" name="confirm_password" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                            </div>
                        </div>
                        <div class="mt-6">
                            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>