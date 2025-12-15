<?php
// File: pages/expense.php
// Purpose: Simple expense & income tracker with add form, transaction list, monthly summary, and Chart.js visualization

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/expense_utils.php';

require_login();

$userId = (int)$_SESSION['user_id'];
$expenseFile = __DIR__ . "/../data/users/{$userId}/expense.json";

$transactions = load_json($expenseFile);
usort($transactions, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

// Current month for summary
$currentMonth = date('Y-m'); // e.g., 2025-12
$monthStart = $currentMonth . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart)); // last day of month

$monthlyTransactions = array_filter($transactions, fn($t) => $t['date'] >= $monthStart && $t['date'] <= $monthEnd);

$summary = calculate_monthly_summary($monthlyTransactions);
$byCategory = group_by_category($monthlyTransactions);

$csrfToken = create_csrf_token();
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses & Income - My Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-full">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Expense & Income Tracker</h1>

        <!-- Add Transaction Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Add New Transaction</h2>
            <form id="expenseForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Type</label>
                    <select name="type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border">
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0.00"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <input type="text" name="category" list="commonCategories" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border">
                    <datalist id="commonCategories">
                        <option value="Food">
                        <option value="Transport">
                        <option value="Shopping">
                        <option value="Entertainment">
                        <option value="Bills">
                        <option value="Salary">
                        <option value="Freelance">
                        <option value="Other">
                    </datalist>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Note (optional)</label>
                    <input type="text" name="note" placeholder="e.g., Lunch at cafe"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border">
                </div>

                <div class="md:col-start-1 lg:col-start-3 flex items-end">
                    <button type="button" onclick="addTransaction()" class="w-full bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
                        Add Transaction
                    </button>
                </div>
            </form>
        </div>

        <!-- Monthly Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
                <p class="text-sm text-green-700 font-medium">Total Income</p>
                <p class="text-3xl font-bold text-green-800 mt-2">$<?= number_format($summary['income'], 2) ?></p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                <p class="text-sm text-red-700 font-medium">Total Expenses</p>
                <p class="text-3xl font-bold text-red-800 mt-2">$<?= number_format($summary['expenses'], 2) ?></p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 text-center">
                <p class="text-sm text-blue-700 font-medium">Net Balance</p>
                <p class="text-3xl font-bold <?= $summary['balance'] >= 0 ? 'text-blue-800' : 'text-red-800' ?> mt-2">
                    $<?= number_format($summary['balance'], 2) ?>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold mb-4">Expenses by Category (<?= date('F Y') ?>)</h2>
                <canvas id="categoryChart" height="300"></canvas>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-semibold mb-4">Recent Transactions</h2>
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    <?php if (empty($transactions)): ?>
                        <p class="text-gray-500 text-center py-8">No transactions yet.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($transactions, 0, 10) as $t): ?>
                            <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                                <div class="flex-1">
                                    <p class="font-medium <?= $t['type'] === 'income' ? 'text-green-700' : 'text-red-700' ?>">
                                        <?= \( t['type'] === 'income' ? '+' : '-' ?> \)<?= number_format($t['amount'], 2) ?>
                                        <span class="text-sm text-gray-600 ml-2"><?= htmlspecialchars($t['category']) ?></span>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?= htmlspecialchars($t['note'] ?: 'No note') ?> • <?= date('M j, Y', strtotime($t['date'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Prepare data for chart
        const categories = <?= json_encode(array_keys($byCategory['expense'])) ?>;
        const amounts = <?= json_encode(array_values($byCategory['expense'])) ?>;

        const ctx = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categories.length ? categories : ['No expenses'],
                datasets: [{
                    data: amounts.length ? amounts : [1],
                    backgroundColor: [
                        '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6',
                        '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.parsed.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        async function addTransaction() {
            const form = document.getElementById('expenseForm');
            const formData = new FormData(form);

            const response = await fetch('/pages/expense_action.php', {
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