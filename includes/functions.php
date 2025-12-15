<?php
// File: includes/functions.php
// Purpose: Common helper functions

declare(strict_types=1);

function load_json(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    return json_decode($content, true) ?: [];
}

function save_json(string $path, array $data): void {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function count_items(int $userId, string $type): int {
    $base = __DIR__ . "/../data/users/{$userId}";
    return match($type) {
        'notes' => count(load_json("{$base}/notes.json")),
        'todos' => count(array_filter(load_json("{$base}/todo.json"), fn($t) => empty($t['completed']))),
        'bookmarks' => count(load_json("{$base}/links.json")),
        'uploads' => count(glob("{$base}/uploads/*")),
        default => 0
    };
}