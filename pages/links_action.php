<?php
// File: pages/links_action.php
// Purpose: AJAX endpoint for bookmarks CRUD + optional title/favicon fetch

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
$linksFile = __DIR__ . "/../data/users/{$userId}/links.json";
$uploadsDir = __DIR__ . "/../data/users/{$userId}/uploads/";

if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

$links = load_json($linksFile);
$maxId = $links ? max(array_column($links, 'id')) : 0;

$action = $_POST['action'] ?? '';
$now = date('Y-m-d H:i:s');

function fetch_page_title(string $url): string {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MyAccountBot/1.0)');
    $html = curl_exec($ch);
    curl_close($ch);

    if ($html && preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
        return trim(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'));
    }
    return '';
}

function fetch_and_save_favicon(string $url, int $linkId, string $uploadsDir): void {
    $domain = parse_url($url, PHP_URL_HOST);
    if (!$domain) return;

    $possible = [
        "https://{$domain}/favicon.ico",
        "https://{$domain}/apple-touch-icon.png",
        "https://{$domain}/apple-touch-icon-precomposed.png"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');

    foreach ($possible as $faviconUrl) {
        curl_setopt($ch, CURLOPT_URL, $faviconUrl);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($info['http_code'] === 200 && $info['content_type'] && strpos($info['content_type'], 'image/') === 0) {
            $ext = match($info['content_type']) {
                'image/x-icon', 'image/vnd.microsoft.icon' => 'ico',
                'image/png' => 'png',
                default => 'ico'
            };
            file_put_contents($uploadsDir . "favicon_{$linkId}.{$ext}", $data);
            break;
        }
    }
    curl_close($ch);
}

switch ($action) {
    case 'add_link':
    case 'edit_link':
        $url = trim($_POST['url'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid URL']);
            exit;
        }

        // Auto-fetch title if empty
        if (empty($title)) {
            $title = fetch_page_title($url) ?: parse_url($url, PHP_URL_HOST) ?: 'Untitled';
        }

        if ($action === 'add_link') {
            $newId = $maxId + 1;
            $links[] = [
                'id' => $newId,
                'url' => $url,
                'title' => $title,
                'description' => $description,
                'category' => $category ?: null,
                'added_at' => $now
            ];
            save_json($linksFile, $links);

            // Best-effort favicon fetch
            fetch_and_save_favicon($url, $newId, $uploadsDir);

            echo json_encode(['success' => true]);
        } else {
            $id = (int)($_POST['link_id'] ?? 0);
            foreach ($links as &$link) {
                if ($link['id'] === $id) {
                    $link['url'] = $url;
                    $link['title'] = $title;
                    $link['description'] = $description;
                    $link['category'] = $category ?: null;
                    break;
                }
            }
            save_json($linksFile, $links);
            echo json_encode(['success' => true]);
        }
        break;

    case 'delete_link':
        $id = (int)($_POST['link_id'] ?? 0);
        $links = array_filter($links, fn($l) => $l['id'] !== $id);
        $links = array_values($links);
        save_json($linksFile, $links);

        // Optional: remove favicon
        foreach (['ico', 'png'] as $ext) {
            $file = $uploadsDir . "favicon_{$id}.{$ext}";
            if (file_exists($file)) unlink($file);
        }

        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}