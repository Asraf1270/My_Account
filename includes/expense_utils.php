<?php
// File: includes/expense_utils.php
// Purpose: Helper functions for expense calculations

declare(strict_types=1);

function calculate_monthly_summary(array $transactions): array {
    $income = 0.0;
    $expenses = 0.0;

    foreach ($transactions as $t) {
        $amount = (float)$t['amount'];
        if ($t['type'] === 'income') {
            $income += $amount;
        } else {
            $expenses += $amount;
        }
    }

    return [
        'income' => $income,
        'expenses' => $expenses,
        'balance' => $income - $expenses
    ];
}

function group_by_category(array $transactions): array {
    $income = [];
    $expense = [];

    foreach ($transactions as $t) {
        $cat = $t['category'] ?: 'Uncategorized';
        $amount = (float)$t['amount'];

        if ($t['type'] === 'income') {
            $income[$cat] = ($income[$cat] ?? 0) + $amount;
        } else {
            $expense[$cat] = ($expense[$cat] ?? 0) + $amount;
        }
    }

    arsort($income);
    arsort($expense);

    return ['income' => $income, 'expense' => $expense];
}