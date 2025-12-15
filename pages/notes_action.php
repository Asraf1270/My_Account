<?php
// File: pages/notes_action.php
// Purpose: AJAX endpoint for notes CRUD and preview, returns JSON

declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/markdown.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$notesFile = __DIR__ . "/../data/users/{$userId}/notes.json";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? '';

$notes = load_json($notesFile);
$maxId = max(array_column($notes, 'id') ?: [0]);

if ($action === 'create_note' || $action === 'update_note') {
    $id = (int)($_POST['note_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content_markdown'] ?? '');
    $tagsStr = trim($_POST['tags'] ?? '');
    $tags = array_filter(array_map('trim', explode(',', $tagsStr)));

    if (empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Title and content required']);
        exit;
    }

    $now = date('Y-m-d H:i:s');

    if ($action === 'create_note') {
        $id = $maxId + 1;
        $notes[] = [
            'id' => $id,
            'title' => $title,
            'content_markdown' => $content,
            'tags' => $tags,
            'created_at' => $now,
            'updated_at' => $now
        ];
    } else {
        foreach ($notes as &$note) {
            if ($note['id'] === $id) {
                $note['title'] = $title;
                $note['content_markdown'] = $content;
                $note['tags'] = $tags;
                $note['updated_at'] = $now;
                break;
            }
        }
    }

    save_json($notesFile, $notes);
    echo json_encode(['success' => true]);

} elseif ($action === 'delete_note') {
    $id = (int)($_POST['note_id'] ?? 0);
    $notes = array_filter($notes, fn($n) => $n['id'] !== $id);
    save_json($notesFile, $notes);
    echo json_encode(['success' => true]);

} elseif ($action === 'preview_markdown') {
    $content = trim($_POST['content_markdown'] ?? '');
    $html = render_markdown($content);
    echo json_encode(['success' => true, 'html' => $html]);

} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}