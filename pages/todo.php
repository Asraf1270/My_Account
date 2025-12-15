<?php
// File: pages/todo.php
// Purpose: Per-user To-Do List with add/edit/delete/toggle, filters, and AJAX support

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$userId = (int)$_SESSION['user_id'];
$todoFile = __DIR__ . "/../data/users/{$userId}/todo.json";

$todos = load_json($todoFile);
usort($todos, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

// Filters
$filter = $_GET['filter'] ?? 'all'; // all, pending, completed

$filteredTodos = match($filter) {
    'pending' => array_filter($todos, fn($t) => !$t['completed']),
    'completed' => array_filter($todos, fn($t) => $t['completed']),
    default => $todos
};

$csrfToken = create_csrf_token();
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List - My Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-full">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My To-Do List</h1>
            <button onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center gap-2">
                <i class="fas fa-plus"></i> Add Task
            </button>
        </div>

        <!-- Filter Tabs -->
        <div class="flex gap-4 mb-6 border-b border-gray-200">
            <a href="?filter=all" class="<?= $filter === 'all' ? 'border-b-4 border-indigo-600 text-indigo-600' : 'text-gray-600' ?> pb-2 px-1 font-medium">All</a>
            <a href="?filter=pending" class="<?= $filter === 'pending' ? 'border-b-4 border-indigo-600 text-indigo-600' : 'text-gray-600' ?> pb-2 px-1 font-medium">Pending</a>
            <a href="?filter=completed" class="<?= $filter === 'completed' ? 'border-b-4 border-indigo-600 text-indigo-600' : 'text-gray-600' ?> pb-2 px-1 font-medium">Completed</a>
        </div>

        <!-- Task List -->
        <div id="todoList" class="space-y-4">
            <?php if (empty($filteredTodos)): ?>
                <p class="text-center text-gray-500 py-8">No tasks yet. Add one above!</p>
            <?php else: ?>
                <?php foreach ($filteredTodos as $todo): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-start gap-4" data-id="<?= $todo['id'] ?>">
                        <input type="checkbox" <?= $todo['completed'] ? 'checked' : '' ?> 
                               onchange="toggleComplete(<?= $todo['id'] ?>, this.checked)"
                               class="mt-1 w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                        <div class="flex-1">
                            <h3 class="font-medium text-lg <?= $todo['completed'] ? 'line-through text-gray-500' : 'text-gray-900' ?>">
                                <?= htmlspecialchars($todo['title']) ?>
                            </h3>
                            <?php if (!empty($todo['description'])): ?>
                                <p class="text-gray-600 mt-1"><?= nl2br(htmlspecialchars($todo['description'])) ?></p>
                            <?php endif; ?>
                            <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-500">
                                <span>Created: <?= date('M j, Y', strtotime($todo['created_at'])) ?></span>
                                <?php if (!empty($todo['opt_due_date'])): ?>
                                    <span>Due: <?= date('M j, Y', strtotime($todo['opt_due_date'])) ?></span>
                                <?php endif; ?>
                                <?php if ($todo['completed'] && !empty($todo['completed_at'])): ?>
                                    <span class="text-green-600">Completed: <?= date('M j, Y g:i A', strtotime($todo['completed_at'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="openEditModal(<?= $todo['id'] ?>, '<?= addslashes(htmlspecialchars($todo['title'])) ?>', '<?= addslashes(htmlspecialchars($todo['description'] ?? '')) ?>', '<?= $todo['opt_due_date'] ?? '' ?>')" 
                                    class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteTask(<?= $todo['id'] ?>)" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <!-- Add/Edit Modal -->
    <div id="taskModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-lg">
            <h2 id="modalTitle" class="text-2xl font-bold mb-4"></h2>
            <form id="taskForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" id="action" name="action" value="add_task">
                <input type="hidden" id="task_id" name="task_id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                    <input type="text" id="title" name="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border"></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700">Due Date (optional)</label>
                    <input type="date" id="due_date" name="opt_due_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border">
                </div>

                <div class="flex gap-4">
                    <button type="button" onclick="submitTask()" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
                        Save Task
                    </button>
                    <button type="button" onclick="closeModal()" class="bg-gray-300 text-gray-800 px-6 py-2 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Task';
            document.getElementById('action').value = 'add_task';
            document.getElementById('task_id').value = '';
            document.getElementById('title').value = '';
            document.getElementById('description').value = '';
            document.getElementById('due_date').value = '';
            document.getElementById('taskModal').classList.remove('hidden');
        }

        function openEditModal(id, title, description, dueDate) {
            document.getElementById('modalTitle').textContent = 'Edit Task';
            document.getElementById('action').value = 'edit_task';
            document.getElementById('task_id').value = id;
            document.getElementById('title').value = title;
            document.getElementById('description').value = description;
            document.getElementById('due_date').value = dueDate;
            document.getElementById('taskModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('taskModal').classList.add('hidden');
        }

        async function submitTask() {
            const formData = new FormData(document.getElementById('taskForm'));
            const response = await fetch('/pages/todo_action.php', {
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

        async function toggleComplete(id, completed) {
            const formData = new FormData();
            formData.append('csrf_token', '<?= $csrfToken ?>');
            formData.append('action', 'toggle_complete');
            formData.append('task_id', id);
            formData.append('completed', completed ? '1' : '0');

            const response = await fetch('/pages/todo_action.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                location.reload();
            }
        }

        async function deleteTask(id) {
            if (!confirm('Delete this task permanently?')) return;

            const formData = new FormData();
            formData.append('csrf_token', '<?= $csrfToken ?>');
            formData.append('action', 'delete_task');
            formData.append('task_id', id);

            const response = await fetch('/pages/todo_action.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                document.querySelector(`[data-id="${id}"]`).remove();
            }
        }
    </script>
</body>
</html>