<?php
// File: admin/users.php
// Purpose: List and manage all users

declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$adminId = (int)$_SESSION['user_id'];
$usersFile = __DIR__ . '/../data/users.json';
$users = load_json($usersFile);

$message = $_SESSION['admin_message'] ?? '';
unset($_SESSION['admin_message']);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    if ($targetId === $adminId) {
        $message = 'Cannot perform this action on yourself.';
    } elseif (!isset($users[$targetId])) {
        $message = 'User not found.';
    } else {
        if ($action === 'reset_password') {
            $tempPass = bin2hex(random_bytes(6)); // 12-char random
            $hashed = password_hash($tempPass, PASSWORD_DEFAULT);

            $users[$targetId]['password_hash'] = $hashed;
            save_json($usersFile, $users);

            log_admin_action($adminId, 'reset_password', $targetId, "Temporary password: $tempPass");
            $message = "Password reset. Temporary password: <strong>$tempPass</strong> (user must change it on next login)";
        }

        elseif ($action === 'delete_user' && !empty($_POST['confirm'])) {
            $archiveDir = __DIR__ . '/../data/users_archive/' . $targetId . '_' . date('Ymd_His');
            $userDir = __DIR__ . '/../data/users/' . $targetId;

            if (is_dir($userDir)) {
                rename($userDir, $archiveDir);
            }

            $users[$targetId]['deleted_at'] = date('Y-m-d H:i:s');
            save_json($usersFile, $users);

            log_admin_action($adminId, 'delete_user', $targetId, "Archived to $archiveDir");
            $message = 'User deleted and archived successfully.';
        }
    }

    $_SESSION['admin_message'] = $message;
    header('Location: /admin/users.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-full">
    <div class="min-h-full flex flex-col">
        <?php include __DIR__ . '/sidebar.php'; // We'll reuse sidebar logic ?>

        <main class="flex-1 p-8">
            <h2 class="text-3xl font-bold mb-8">Manage Users</h2>

            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-md <?= strpos($message, 'success') ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?> border">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-4">ID</th>
                            <th class="text-left p-4">Username</th>
                            <th class="text-left p-4">Email</th>
                            <th class="text-left p-4">Role</th>
                            <th class="text-left p-4">Created</th>
                            <th class="text-left p-4">Status</th>
                            <th class="text-center p-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $id => $user): ?>
                            <tr class="border-t <?= !empty($user['deleted_at']) ? 'bg-red-50' : '' ?>">
                                <td class="p-4"><?= $id ?></td>
                                <td class="p-4"><?= htmlspecialchars($user['username']) ?></td>
                                <td class="p-4"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="p-4"><span class="px-2 py-1 text-xs rounded <?= $user['role'] === 'admin' ? 'bg-purple-200 text-purple-800' : 'bg-gray-200' ?>"><?= $user['role'] ?></span></td>
                                <td class="p-4"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td class="p-4"><?= !empty($user['deleted_at']) ? 'Deleted' : 'Active' ?></td>
                                <td class="p-4 text-center space-x-2">
                                    <?php if (empty($user['deleted_at'])): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="user_id" value="<?= $id ?>">
                                            <input type="hidden" name="action" value="reset_password">
                                            <button type="submit" class="text-blue-600 hover:underline text-sm">Reset Pass</button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this user? Data will be archived.')">
                                            <input type="hidden" name="user_id" value="<?= $id ?>">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="confirm" value="1">
                                            <button type="submit" class="text-red-600 hover:underline text-sm">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>