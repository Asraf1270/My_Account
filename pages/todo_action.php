<?php
// File: pages/todo_action.php
// Purpose: AJAX endpoint for To-Do CRUD operations (returns JSON)

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
$todoFile = __DIR__ . "/../data/users/{$userId}/todo.json";

$todos = load_json($todoFile);
$maxId = $todos ? max(array_column($todos, 'id')) : 0;

$action = $_POST['action'] ?? '';
$now = date('Y-m-d H:i:s');

switch ($action) {
    case 'add_task':
        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            echo json_encode(['success' => false, 'error' => 'Title required']);
            exit;
        }

        $todos[] = [
            'id' => $maxId + 1,
            'title' => $title,
            'description' => trim($_POST['description'] ?? ''),
            'opt_due_date' => $_POST['opt_due_date'] ?: null,
            'completed' => false,
            'created_at' => $now,
            'completed_at' => null
        ];
        save_json($todoFile, $todos);
        echo json_encode(['success' => true]);
        break;

    case 'edit_task':
        $id = (int)($_POST['task_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        if (empty($title) || $id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
            exit;
        }

        foreach ($todos as &$todo) {
            if ($todo['id'] === $id) {
                $todo['title'] = $title;
                $todo['description'] = trim($_POST['description'] ?? '');
                $todo['opt_due_date'] = $_POST['opt_due_date'] ?: null;
                break;
            }
        }
        save_json($todoFile, $todos);
        echo json_encode(['success' => true]);
        break;

    case 'toggle_complete':
        $id = (int)($_POST['task_id'] ?? 0);
        $completed = !empty($_POST['completed']);

        foreach ($todos as &$todo) {
            if ($todo['id'] === $id) {
                $todo['completed'] = $completed;
                $todo['completed_at'] = $completed ? $now : null;
                break;
            }
        }
        save_json($todoFile, $todos);
        echo json_encode(['success' => true]);
        break;

    case 'delete_task':
        $id = (int)($_POST['task_id'] ?? 0);
        $todos = array_filter($todos, fn($t) => $t['id'] !== $id);
        $todos = array_values($todos); // reindex
        save_json($todoFile, $todos);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}