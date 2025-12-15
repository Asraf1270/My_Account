<?php
// File: includes/header.php
// Purpose: Unified responsive header for the entire app (desktop + mobile)
// Features:
// - Logo + App name
// - Mobile hamburger menu
// - Desktop horizontal navigation
// - User avatar dropdown (profile, settings, logout)
// - Dark mode aware (uses Tailwind dark classes)
// - Applies user theme from settings

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/settings_handler.php';

$isLoggedIn = is_logged_in();
$userId = $isLoggedIn ? (int)$_SESSION['user_id'] : null;

$bodyClass = 'bg-gray-50 dark:bg-gray-900';
$navClass = 'bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700';

if ($isLoggedIn) {
    $settings = load_user_settings($userId);
    if ($settings['theme'] === 'dark') {
        $bodyClass = 'dark bg-gray-900';
    }

    $users = load_json(__DIR__ . '/../data/users.json');
    $currentUser = $users[$userId] ?? null;
    $profile = load_json(__DIR__ . "/../data/profiles/profile_{$userId}.json");

    $avatarPath = "/data/users/{$userId}/uploads/profile_thumb.jpg";
    $avatarExists = file_exists(__DIR__ . '/../' . $avatarPath);
}

$navItems = [
    ['name' => 'Dashboard', 'url' => '/pages/dashboard.php', 'icon' => 'fa-home'],
    ['name' => 'Notes', 'url' => '/pages/notes.php', 'icon' => 'fa-sticky-note'],
    ['name' => 'To-Do', 'url' => '/pages/todo.php', 'icon' => 'fa-tasks'],
    ['name' => 'Bookmarks', 'url' => '/pages/links.php', 'icon' => 'fa-bookmark'],
    ['name' => 'Expenses', 'url' => '/pages/expense.php', 'icon' => 'fa-chart-pie'],
    ['name' => 'Files', 'url' => '/pages/uploads.php', 'icon' => 'fa-folder-open'],
    ['name' => 'Profile', 'url' => '/pages/profile.php', 'icon' => 'fa-user'],
    ['name' => 'Settings', 'url' => '/pages/settings.php', 'icon' => 'fa-cog'],
];

if ($isLoggedIn && ($currentUser['role'] ?? '') === 'admin') {
    $navItems[] = ['name' => 'Admin', 'url' => '/admin/index.php', 'icon' => 'fa-shield-alt'];
}
?>

<!DOCTYPE html>
<html lang="en" class="<?= $bodyClass ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</head>
<body class="<?= $bodyClass ?> text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">

<header class="<?= $navClass ?>">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <!-- Logo + Mobile menu button -->
            <div class="flex items-center">
                <button id="mobileMenuButton" class="lg:hidden p-2 rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <a href="/pages/dashboard.php" class="ml-4 lg:ml-0 flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-xl">
                        MA
                    </div>
                    <span class="font-bold text-xl text-indigo-600 dark:text-indigo-400 hidden sm:block">My Account</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden lg:flex items-center gap-8">
                <?php foreach ($navItems as $item): ?>
                    <a href="<?= $item['url'] ?>"
                       class="flex items-center gap-2 text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-medium <?= ($_SERVER['REQUEST_URI'] ?? '') === $item['url'] ? 'text-indigo-600 dark:text-indigo-400' : '' ?>">
                        <i class="fas <?= $item['icon'] ?>"></i>
                        <?= htmlspecialchars($item['name']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- User Dropdown -->
            <?php if ($isLoggedIn): ?>
                <div class="relative">
                    <button id="userDropdownButton" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <?php if ($avatarExists): ?>
                            <img src="<?= $avatarPath ?>?t=<?= filemtime(__DIR__ . '/../' . $avatarPath) ?>"
                                 alt="Avatar" class="w-9 h-9 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-9 h-9 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold">
                                <?= strtoupper(substr($currentUser['username'] ?? 'U', 0, 2)) ?>
                            </div>
                        <?php endif; ?>
                        <span class="hidden sm:block font-medium text-gray-800 dark:text-gray-200">
                            <?= htmlspecialchars($profile['full_name'] ?? $currentUser['username'] ?? 'User') ?>
                        </span>
                        <i class="fas fa-chevron-down text-sm text-gray-500"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="userDropdown" class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 hidden z-50">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <p class="font-medium"><?= htmlspecialchars($currentUser['username']) ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($currentUser['email']) ?></p>
                        </div>
                        <a href="/pages/profile.php" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="/pages/settings.php" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <a href="/admin/index.php" class="block px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 text-purple-600">
                                <i class="fas fa-shield-alt"></i> Admin Panel
                            </a>
                        <?php endif; ?>
                        <hr class="border-gray-200 dark:border-gray-700">
                        <a href="/pages/logout.php" class="block px-4 py-3 hover:bg-red-50 dark:hover:bg-red-900/30 text-red-600 flex items-center gap-3">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex gap-4">
                    <a href="/pages/login.php" class="text-indigo-600 hover:underline">Login</a>
                    <a href="/pages/register.php" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Slide-in Menu -->
    <div id="mobileMenu" class="fixed inset-y-0 left-0 w-64 bg-white dark:bg-gray-800 shadow-xl transform -translate-x-full transition-transform duration-300 z-50 lg:hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center">
                <span class="font-bold text-xl text-indigo-600 dark:text-indigo-400">My Account</span>
                <button id="closeMobileMenu" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <nav class="p-4 space-y-2">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= $item['url'] ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/30 <?= (($_SERVER['REQUEST_URI'] ?? '') === $item['url']) ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600' : 'text-gray-700 dark:text-gray-300' ?>">
                    <i class="fas <?= $item['icon'] ?> w-5"></i>
                    <?= htmlspecialchars($item['name']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Overlay for mobile menu -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40 lg:hidden"></div>
</header>

<script>
    // Mobile menu toggle
    document.getElementById('mobileMenuButton').addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.remove('-translate-x-full');
        document.getElementById('mobileOverlay').classList.remove('hidden');
    });

    document.getElementById('closeMobileMenu').addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.add('-translate-x-full');
        document.getElementById('mobileOverlay').classList.add('hidden');
    });

    document.getElementById('mobileOverlay').addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.add('-translate-x-full');
        document.getElementById('mobileOverlay').classList.add('hidden');
    });

    // User dropdown
    const dropdownButton = document.getElementById('userDropdownButton');
    const dropdown = document.getElementById('userDropdown');

    dropdownButton.addEventListener('click', () => {
        dropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!dropdownButton.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
</script>

<!-- Main content will start here in pages -->
<main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">