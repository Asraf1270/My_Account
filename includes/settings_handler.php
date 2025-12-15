<?php
// File: includes/settings_handler.php
// Purpose: Load and save per-user settings securely

declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function get_user_settings_path(int $userId): string {
    return __DIR__ . "/../data/users/{$userId}/settings.json";
}

function load_user_settings(int $userId): array {
    $path = get_user_settings_path($userId);
    if (!file_exists($path)) {
        // Default settings
        return [
            'theme' => 'light',
            'language' => 'en',
            'privacy' => [
                'show_email' => false,
                'newsletter' => true
            ],
            'two_factor_enabled' => false,
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    $data = load_json($path);
    // Merge with defaults to avoid missing keys
    $defaults = [
        'theme' => 'light',
        'language' => 'en',
        'privacy' => ['show_email' => false, 'newsletter' => true],
        'two_factor_enabled' => false
    ];
    return array_merge($defaults, $data);
}

function save_user_settings(int $userId, array $settings): void {
    $path = get_user_settings_path($userId);
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    save_json($path, $settings);
}