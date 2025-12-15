<?php
// File: pages/expense_action.php
// Purpose: Handle adding new transactions via AJAX

declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in() || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$expenseFile = __DIR__ . "/../data/users/{$userId}/expense.json";

$transactions = load_json($expenseFile);
$maxId = $transactions ? max(array_column($transactions, 'id')) : 0;

$type = $_POST['type'] ?? '';
$amount = trim($_POST['amount'] ?? '');
$category = trim($_POST['category'] ?? '');
$date = $_POST['date'] ?? '';
$note = trim($_POST['note'] ?? '');

if (!in_array($type, ['income', 'expense'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid type']);
    exit;
}

$amountFloat = (float)$amount;
if ($amountFloat <= 0) {
    echo json_encode(['success' => false, 'error' => 'Amount must be greater than 0']);
    exit;
}

if (empty($category)) {
    echo json_encode(['success' => false, 'error' => 'Category required']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date']);
    exit;
}

$now = date('Y-m-d H:i:s');

$transactions[] = [
    'id' => $maxId + 1,
    'type' => $type,
    'amount' => $amountFloat,
    'category' => $category,
    'note' => $note,
    'date' => $date,
    'created_at' => $now
];

save_json($expenseFile, $transactions);

echo json_encode(['success' => true]);