<?php
// File: admin/index.php
// Purpose: Admin dashboard overview

declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$userId = (int)$_SESSION['user_id'];
$users = load_json(__DIR__ . '/../data/users.json');
$totalUsers = count($users);
$activeUsers = count(array_filter($users, fn($u) => empty($u['deleted_at'])));

$logs = load_json(__DIR__ . '/../data/logs.json');
$recentLogs = array_slice(array_reverse($logs), 0, 10);
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-full">
    <div class="min-h-full flex flex-col">
        <header class="bg-indigo-700 text-white p-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <h1 class="text-2xl font-bold">Admin Dashboard</h1>
                <a href="/pages/logout.php" class="text-sm hover:underline">Logout</a>
            </div>
        </header>

        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside class="w-64 bg-white shadow-md">
                <nav class="p-4 space-y-2">
                    <a href="/admin/index.php" class="block py-2 px-4 bg-indigo-100 text-indigo-700 rounded font-medium">Overview</a>
                    <a href="/admin/users.php" class="block py-2 px-4 hover:bg-gray-100 rounded">Manage Users</a>
                    <a href="/admin/logs.php" class="block py-2 px-4 hover:bg-gray-100 rounded">Activity Logs</a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 p-8">
                <h2 class="text-3xl font-bold mb-8">Welcome, Admin</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <div class="bg-white p-6 rounded-xl shadow">
                        <p class="text-sm text-gray-600">Total Users</p>
                        <p class="text-4xl font-bold text-indigo-600 mt-2"><?= $totalUsers ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow">
                        <p class="text-sm text-gray-600">Active Users</p>
                        <p class="text-4xl font-bold text-green-600 mt-2"><?= $activeUsers ?></p>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow">
                        <p class="text-sm text-gray-600">Recent Actions</p>
                        <p class="text-4xl font-bold text-purple-600 mt-2"><?= count($recentLogs) ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-xl font-semibold mb-4">Recent Activity</h3>
                    <?php if (empty($recentLogs)): ?>
                        <p class="text-gray-500">No recent activity.</p>
                    <?php else: ?>
                        <table class="w-full text-sm">
                            <thead class="border-b">
                                <tr>
                                    <th class="text-left py-2">Time</th>
                                    <th class="text-left py-2">Admin</th>
                                    <th class="text-left py-2">Action</th>
                                    <th class="text-left py-2">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLogs as $log): ?>
                                    <tr class="border-b">
                                        <td class="py-2"><?= $log['timestamp'] ?></td>
                                        <td class="py-2">Admin #<?= $log['admin_id'] ?></td>
                                        <td class="py-2"><?= htmlspecialchars($log['action']) ?></td>
                                        <td class="py-2"><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>