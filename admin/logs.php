<?php
// File: admin/logs.php
// Purpose: View activity logs

declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$logs = load_json(__DIR__ . '/../data/logs.json');
usort($logs, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-full">
    <div class="min-h-full flex flex-col">
        <header class="bg-indigo-700 text-white p-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <h1 class="text-2xl font-bold">Activity Logs</h1>
                <a href="/admin/index.php" class="text-sm hover:underline">← Back to Dashboard</a>
            </div>
        </header>

        <main class="p-8">
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-4">Timestamp</th>
                            <th class="text-left p-4">Admin ID</th>
                            <th class="text-left p-4">Action</th>
                            <th class="text-left p-4">Target User</th>
                            <th class="text-left p-4">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr class="border-t">
                                <td class="p-4"><?= htmlspecialchars($log['timestamp']) ?></td>
                                <td class="p-4"><?= $log['admin_id'] ?></td>
                                <td class="p-4"><?= htmlspecialchars($log['action']) ?></td>
                                <td class="p-4"><?= $log['target_user_id'] ?? '-' ?></td>
                                <td class="p-4"><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="5" class="text-center p-8 text-gray-500">No logs yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>