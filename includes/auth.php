<?php
// Purpose: Authentication helper functions for checking login status, requiring login, logging in/out users.
// Place: project/includes/auth.php

declare(strict_types=1);

session_start(); // Ensure sessions are started; can be moved to a central init if needed.

// Helper function to read JSON file with locking
function json_read(string $file): array {
    if (!file_exists($file)) {
        return [];
    }
    $handle = fopen($file, 'r');
    if ($handle === false) {
        throw new Exception("Unable to open file: $file");
    }
    flock($handle, LOCK_SH);
    $content = fread($handle, filesize($file) ?: 0);
    flock($handle, LOCK_UN);
    fclose($handle);
    return json_decode($content, true) ?: [];
}

// Helper function to write JSON file with locking
function json_write(string $file, array $data): void {
    $handle = fopen($file, 'c');
    if ($handle === false) {
        throw new Exception("Unable to open file: $file");
    }
    flock($handle, LOCK_EX);
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($data, JSON_PRETTY_PRINT));
    flock($handle, LOCK_UN);
    fclose($handle);
}

// Check if user is logged in
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']);
}

// Require login, redirect if not
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /pages/login.php');
        exit;
    }
}

// Log in a user by setting session
function login_user(int $id): void {
    $_SESSION['user_id'] = $id;
    session_regenerate_id(true); // Regenerate ID for security
}

// Log out a user
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}