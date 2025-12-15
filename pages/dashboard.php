<?php
// File: pages/dashboard.php
// Purpose: Authenticated user dashboard with stats, recent activity, and expense chart

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login();

$userId = $_SESSION['user_id'];
$users = load_json(__DIR__ . '/../data/users.json');
$user = $users[$userId] ?? null;

if (!$user) {
    logout_user();
    header('Location: /pages/login.php');
    exit;
}

$profile = load_json(__DIR__ . "/../data/profiles/profile_{$userId}.json");
$notes = load_json(__DIR__ . "/../data/users/{$userId}/notes.json");
$todos = load_json(__DIR__ . "/../data/users/{$userId}/todo.json");
$bookmarks = load_json(__DIR__ . "/../data/users/{$userId}/links.json");
$expenses = load_json(__DIR__ . "/../data/users/{$userId}/expense.json");

$notesCount = count($notes);
$todosCount = count(array_filter($todos, fn($t) => empty($t['completed'])));
$bookmarksCount = count($bookmarks);
$uploadsCount = count(glob(__DIR__ . "/../data/users/{$userId}/uploads/*"));

$recentActivity = [];
foreach ($notes as $i => $n) $recentActivity[] = ['type' => 'note', 'title' => $n['title'] ?? 'Untitled', 'time' => $n['updated_at'] ?? $n['created_at']];
foreach ($todos as $i => $t) if (!empty($t['completed_at'])) $recentActivity[] = ['type' => 'task', 'title' => $t['task'], 'time' => $t['completed_at']];
usort($recentActivity, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
$recentActivity = array_slice($recentActivity, 0, 5);
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - My Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="h-full bg-gray-50">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">
            Welcome back, <?= htmlspecialchars($profile['full_name'] ?: $user['username']) ?>!
        </h1>

        <!-- Stats Widgets -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Notes</p>
                        <p class="text-3xl font-bold text-indigo-600"><?= $notesCount ?></p>
                    </div>
                    <i class="fas fa-sticky-note text-4xl text-indigo-500 opacity-70"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Active Tasks</p>
                        <p class="text-3xl font-bold text-green-600"><?= $todosCount ?></p>
                    </div>
                    <i class="fas fa-tasks text-4xl text-green-500 opacity-70"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Bookmarks</p>
                        <p class="text-3xl font-bold text-purple-600"><?= $bookmarksCount ?></p>
                    </div>
                    <i class="fas fa-bookmark text-4xl text-purple-500 opacity-70"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Uploads</p>
                        <p class="text-3xl font-bold text-orange-600"><?= $uploadsCount ?></p>
                    </div>
                    <i class="fas fa-cloud-upload-alt text-4xl text-orange-500 opacity-70"></i>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Activity -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <i class="fas fa-history text-indigo-600"></i> Recent Activity
                </h2>
                <?php if (empty($recentActivity)): ?>
                    <p class="text-gray-500">No recent activity.</p>
                <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach ($recentActivity as $act): ?>
                            <li class="flex items-center gap-3 text-sm">
                                <i class="fas fa-<?= $act['type'] === 'note' ? 'sticky-note text-indigo-500' : 'check-circle text-green-500' ?>"></i>
                                <span class="text-gray-700"><?= htmlspecialchars($act['title']) ?></span>
                                <span class="text-gray-400 ml-auto"><?= date('M j, g:i A', strtotime($act['time'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Expense Summary Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-pie text-emerald-600"></i> Monthly Expenses
                </h2>
                <canvas id="expenseChart" height="200"></canvas>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const ctx = document.getElementById('expenseChart').getContext('2d');
        const expenses = <?= json_encode(array_values($expenses)) ?>;
        const labels = expenses.map(e => e.date || 'Unknown');
        const amounts = expenses.map(e => parseFloat(e.amount) || 0);

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels.length ? labels : ['No data'],
                datasets: [{
                    data: amounts.length ? amounts : [1],
                    backgroundColor: ['#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20 } }
                }
            }
        });
    </script>
</body>
</html>