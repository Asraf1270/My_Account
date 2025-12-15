<?php
// File: pages/links.php
// Purpose: Bookmarks manager - add, list, edit, delete, filter by category/search

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$userId = (int)$_SESSION['user_id'];
$linksFile = __DIR__ . "/../data/users/{$userId}/links.json";

$links = load_json($linksFile);
usort($links, fn($a, $b) => strtotime($b['added_at']) - strtotime($a['added_at']));

// Extract categories
$categories = array_unique(array_filter(array_column($links, 'category')));
sort($categories);

// Filters
$search = trim($_GET['search'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');

$filteredLinks = array_filter($links, function($link) use ($search, $categoryFilter) {
    $matchesSearch = empty($search) ||
        stripos($link['title'], $search) !== false ||
        stripos($link['url'], $search) !== false ||
        stripos($link['description'] ?? '', $search) !== false;
    $matchesCategory = empty($categoryFilter) || $link['category'] === $categoryFilter;
    return $matchesSearch && $matchesCategory;
});

$csrfToken = create_csrf_token();
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarks - My Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-full">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Bookmarks</h1>
            <button onclick="openAddModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center gap-2">
                <i class="fas fa-plus"></i> Add Bookmark
            </button>
        </div>

        <!-- Search & Category Filter -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="flex flex-col md:flex-row gap-4">
                <form class="flex-1" method="GET">
                    <div class="relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search bookmarks..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-indigo-500">
                        <button type="submit" class="absolute right-3 top-2.5 text-gray-500">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <?php if (!empty($categoryFilter)): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter) ?>">
                    <?php endif; ?>
                </form>

                <select onchange="if(this.value) window.location='?category='+encodeURIComponent(this.value)+'&search=<?= urlencode($search) ?>'"
                        class="px-4 py-2 border border-gray-300 rounded-md">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Bookmarks Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($filteredLinks)): ?>
                <p class="col-span-full text-center text-gray-500 py-8">No bookmarks yet. Add your first one!</p>
            <?php else: ?>
                <?php foreach ($filteredLinks as $link): ?>
                    <?php
                    $faviconPath = "/data/users/{$userId}/uploads/favicon_{$link['id']}.ico";
                    $faviconExists = file_exists(__DIR__ . "/../{$faviconPath}");
                    ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <?php if ($faviconExists): ?>
                                    <img src="<?= $faviconPath ?>?t=<?= filemtime(__DIR__ . '/../' . $faviconPath) ?>"
                                         alt="Favicon" class="w-10 h-10 rounded">
                                <?php else: ?>
                                    <div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-globe text-gray-500"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" class="font-semibold text-indigo-600 hover:underline truncate block">
                                    <?= htmlspecialchars($link['title']) ?>
                                </a>
                                <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($link['url']) ?></p>
                                <?php if (!empty($link['description'])): ?>
                                    <p class="text-sm text-gray-600 mt-2 line-clamp-2"><?= htmlspecialchars($link['description']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($link['category'])): ?>
                                    <span class="inline-block mt-3 px-3 py-1 bg-indigo-100 text-indigo-800 text-xs rounded-full">
                                        <?= htmlspecialchars($link['category']) ?>
                                    </span>
                                <?php endif; ?>
                                <p class="text-xs text-gray-400 mt-3">Added: <?= date('M j, Y', strtotime($link['added_at'])) ?></p>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-4">
                            <button onclick="openEditModal(<?= $link['id'] ?>, '<?= addslashes(htmlspecialchars($link['url'])) ?>', '<?= addslashes(htmlspecialchars($link['title'])) ?>', '<?= addslashes(htmlspecialchars($link['description'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($link['category'] ?? '')) ?>')"
                                    class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteLink(<?= $link['id'] ?>)" class="text-red-600 hover:text-red-800">
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
    <div id="linkModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-lg">
            <h2 id="modalTitle" class="text-2xl font-bold mb-4"></h2>
            <form id="linkForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" id="action" name="action" value="add_link">
                <input type="hidden" id="link_id" name="link_id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">URL <span class="text-red-500">*</span></label>
                    <input type="url" id="url" name="url" required placeholder="https://example.com"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" id="title" name="title" placeholder="Will be fetched if empty"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border"></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <input type="text" id="category" name="category" list="categorySuggestions"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 px-3 py-2 border">
                    <datalist id="categorySuggestions">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="flex gap-4">
                    <button type="button" onclick="submitLink()" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">
                        Save Bookmark
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
            document.getElementById('modalTitle').textContent = 'Add New Bookmark';
            document.getElementById('action').value = 'add_link';
            document.getElementById('link_id').value = '';
            document.getElementById('url').value = '';
            document.getElementById('title').value = '';
            document.getElementById('description').value = '';
            document.getElementById('category').value = '';
            document.getElementById('linkModal').classList.remove('hidden');
        }

        function openEditModal(id, url, title, description, category) {
            document.getElementById('modalTitle').textContent = 'Edit Bookmark';
            document.getElementById('action').value = 'edit_link';
            document.getElementById('link_id').value = id;
            document.getElementById('url').value = url;
            document.getElementById('title').value = title;
            document.getElementById('description').value = description;
            document.getElementById('category').value = category;
            document.getElementById('linkModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('linkModal').classList.add('hidden');
        }

        async function submitLink() {
            const formData = new FormData(document.getElementById('linkForm'));
            const response = await fetch('/pages/links_action.php', {
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

        async function deleteLink(id) {
            if (!confirm('Delete this bookmark permanently?')) return;

            const formData = new FormData();
            formData.append('csrf_token', '<?= $csrfToken ?>');
            formData.append('action', 'delete_link');
            formData.append('link_id', id);

            const response = await fetch('/pages/links_action.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                document.querySelector(`[data-id="${id}"]`).closest('.grid > div').remove();
            }
        }
    </script>
</body>
</html>