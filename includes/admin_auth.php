<?php
// File: includes/admin_auth.php
// Purpose: Middleware to enforce admin-only access

declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

function require_admin(): void {
    require_login();

    $userId = (int)$_SESSION['user_id'];
    $users = load_json(__DIR__ . '/../data/users.json');

    if (!isset($users[$userId]) || $users[$userId]['role'] !== 'admin') {
        http_response_code(403);
        echo '<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>';
        exit;
    }
}

function log_admin_action(int $adminId, string $action, ?int $targetUserId = null, string $details = ''): void {
    $logFile = __DIR__ . '/../data/logs.json';
    $logs = load_json($logFile);

    $logs[] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'admin_id' => $adminId,
        'action' => $action,
        'target_user_id' => $targetUserId,
        'details' => $details
    ];

    save_json($logFile, $logs);
}