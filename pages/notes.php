<?php
// File: pages/notes.php
// Purpose: List user notes with search/tag filters, modals for create/edit/delete, Markdown preview

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/markdown.php';

require_login();

$userId = (int)$_SESSION['user_id'];
$notesFile = __DIR__ . "/../data/users/{$userId}/notes.json";

$notes = load_json($notesFile);
usort($notes, fn($a, $b) => strtotime($b['updated_at']) - strtotime($a['updated_at']));

// Collect unique tags with counts
$allTags = [];
foreach ($notes as $note) {
    foreach ($note['tags'] ?? [] as $tag) {
        $allTags[$tag] = ($allTags[$tag] ?? 0) + 1;
    }
}
ksort($allTags);

// Filters
$search = trim($_GET['search'] ?? '');
$filterTag = trim($_GET['tag'] ?? '');

$filteredNotes = array_filter($notes, function($note) use ($search, $filterTag) {
    $matchesSearch = empty($search) || stripos($note['title'], $search) !== false || stripos($note['content_markdown'], $search) !== false;
    $matchesTag = empty($filterTag) || in_array($filterTag, $note['tags'] ?? []);
    return $matchesSearch && $matchesTag;
});

$csrfToken = create_csrf_token();
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes - My Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked@4.0.0/marked.min.js"></script> <!-- For client-side preview if needed, but we'll use server -->
</head>
<body class="bg-gray-50 min-h-full">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">My Notes</h1>
            <button onclick="openModal('create')" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                <i class="fas fa-plus mr-2"></i> New Note
            </button>
        </div>

        <!-- Search and Tags -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <form class="flex-1" method="GET">
                    <div class="relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search notes..." 
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-indigo-500">
                        <button type="submit" class="absolute right-3 top-2.5 text-gray-500">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                <div class="flex flex-wrap gap-2">
                    <span class="text-sm font-medium text-gray-700 mr-2">Tags:</span>
                    <?php foreach ($allTags as $tag => $count): ?>
                        <a href="?tag=<?= urlencode($tag) ?>&search=<?= urlencode($search) ?>"
                           class="px-3 py-1 bg-gray-200 rounded-full text-sm hover:bg-indigo-200 <?= $filterTag === $tag ? 'bg-indigo-500 text-white' : '' ?>">
                            <?= htmlspecialchars($tag) ?> (<?= $count ?>)
                        </a>
                    <?php endforeach; ?>
                    <?php if ($filterTag): ?>
                        <a href="?search=<?= urlencode($search) ?>" class="px-3 py-1 bg-red-200 rounded-full text-sm hover:bg-red-300">Clear Tag</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notes List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($filteredNotes)): ?>
                <p class="col-span-full text-center text-gray-500">No notes found. Create one!</p>
            <?php else: ?>
                <?php foreach ($filteredNotes as $note): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($note['title']) ?></h3>
                        <div class="text-gray-600 mb-4 prose max-w-none" style="overflow: hidden; max-height: 150px;">
                            <?= render_markdown(substr($note['content_markdown'], 0, 300) . '...') ?>
                        </div>
                        <div class="flex flex-wrap gap-1 mb-4">
                            <?php foreach ($note['tags'] ?? [] as $tag): ?>
                                <span class="px-2 py-1 bg-indigo-100 text-indigo-800 text-xs rounded-full"><?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">Updated: <?= date('M j, Y g:i A', strtotime($note['updated_at'])) ?></p>
                        <div class="flex gap-2">
                            <button onclick="openModal('edit', <?= $note['id'] ?>, '<?= addslashes(htmlspecialchars($note['title'])) ?>', '<?= addslashes(htmlspecialchars($note['content_markdown'])) ?>', '<?= addslashes(implode(',', $note['tags'] ?? [])) ?>')" 
                                    class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="openModal('delete', <?= $note['id'] ?>)" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <!-- Modal -->
    <div id="noteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 id="modalTitle" class="text-2xl font-bold"></h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
            </div>
            <form id="noteForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" id="action" name="action">
                <input type="hidden" id="note_id" name="note_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" id="title" name="title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Tags (comma-separated)</label>
                    <input type="text" id="tags" name="tags" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Content (Markdown)</label>
                    <textarea id="content" name="content_markdown" rows="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border" required></textarea>
                </div>
                <div class="flex gap-4">
                    <button type="button" onclick="previewMarkdown()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        <i class="fas fa-eye mr-2"></i> Preview
                    </button>
                    <button type="button" onclick="submitNote()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        <i class="fas fa-save mr-2"></i> Save
                    </button>
                    <button type="button" onclick="closeModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400">Cancel</button>
                </div>
            </form>
            <div id="previewSection" class="mt-6 prose max-w-none hidden"></div>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
            <h2 class="text-2xl font-bold mb-4">Confirm Delete</h2>
            <p class="mb-6">Are you sure you want to delete this note?</p>
            <div class="flex gap-4">
                <button onclick="deleteNote()" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">Yes, Delete</button>
                <button onclick="closeModal()" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let currentAction = '';
        let currentNoteId = 0;

        function openModal(action, id = 0, title = '', content = '', tags = '') {
            currentAction = action;
            currentNoteId = id;
            document.getElementById('modalTitle').textContent = action === 'create' ? 'New Note' : (action === 'edit' ? 'Edit Note' : 'Delete Note');
            if (action === 'delete') {
                document.getElementById('deleteModal').classList.remove('hidden');
            } else {
                document.getElementById('action').value = action === 'create' ? 'create_note' : 'update_note';
                document.getElementById('note_id').value = id;
                document.getElementById('title').value = title;
                document.getElementById('content').value = content;
                document.getElementById('tags').value = tags;
                document.getElementById('noteModal').classList.remove('hidden');
                document.getElementById('previewSection').classList.add('hidden');
            }
        }

        function closeModal() {
            document.getElementById('noteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.add('hidden');
        }

        async function previewMarkdown() {
            const content = document.getElementById('content').value;
            const response = await fetch('/pages/notes_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=preview_markdown&content_markdown=${encodeURIComponent(content)}&csrf_token=<?= $csrfToken ?>`
            });
            const result = await response.json();
            if (result.success) {
                document.getElementById('previewSection').innerHTML = result.html;
                document.getElementById('previewSection').classList.remove('hidden');
            } else {
                alert(result.error);
            }
        }

        async function submitNote() {
            const formData = new FormData(document.getElementById('noteForm'));
            const response = await fetch('/pages/notes_action.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error);
            }
        }

        async function deleteNote() {
            const response = await fetch('/pages/notes_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_note&note_id=${currentNoteId}&csrf_token=<?= $csrfToken ?>`
            });
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert(result.error);
            }
        }
    </script>
</body>
</html>