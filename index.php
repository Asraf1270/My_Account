<?php
// File: index.php
// Purpose: Main entry point of the application
// - If logged in → redirect to dashboard
// - If not logged in → show beautiful landing page with features, login/register links

declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If user is already logged in, send them straight to dashboard
if (is_logged_in()) {
    header('Location: /pages/dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50 dark:bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Your Personal Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</head>
<body class="h-full flex flex-col">

    <!-- Header (same as logged-in but simplified for guests) -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="/" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-xl">
                        MA
                    </div>
                    <span class="font-bold text-xl text-indigo-600 dark:text-indigo-400">My Account</span>
                </a>
                <div class="flex gap-4">
                    <a href="/pages/login.php" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Login</a>
                    <a href="/pages/register.php" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 font-medium">Get Started</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="flex-1 bg-gradient-to-b from-indigo-50 to-white dark:from-gray-900 dark:to-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
            <h1 class="text-5xl sm:text-6xl font-bold text-gray-900 dark:text-white mb-6">
                All Your Personal Tools<br>in One Place
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-300 mb-10 max-w-3xl mx-auto">
                Notes, tasks, bookmarks, expenses, files, and more — securely organized and accessible anywhere.
                Simple. Private. Yours.
            </p>
            <div class="flex flex-col sm:flex-row gap-6 justify-center">
                <a href="/pages/register.php" class="inline-flex items-center justify-center px-8 py-4 bg-indigo-600 text-white text-lg font-semibold rounded-lg hover:bg-indigo-700 shadow-lg">
                    <i class="fas fa-user-plus mr-3"></i> Create Free Account
                </a>
                <a href="/pages/login.php" class="inline-flex items-center justify-center px-8 py-4 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-lg font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                    <i class="fas fa-sign-in-alt mr-3"></i> Login
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white dark:bg-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold text-center text-gray-900 dark:text-white mb-12">Everything You Need</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                <div class="text-center">
                    <div class="w-20 h-20 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-sticky-note text-3xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3">Smart Notes</h3>
                    <p class="text-gray-600 dark:text-gray-400">Write with Markdown, add tags, search instantly</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-tasks text-3xl text-green-600"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3">To-Do Lists</h3>
                    <p class="text-gray-600 dark:text-gray-400">Organize tasks, set due dates, track progress</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-bookmark text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3">Bookmarks</h3>
                    <p class="text-gray-600 dark:text-gray-400">Save links with auto titles & favicons</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-chart-pie text-3xl text-red-600"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3">Expense Tracker</h3>
                    <p class="text-gray-600 dark:text-gray-400">Track income & spending with monthly insights</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-cloud-upload-alt text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3">File Manager</h3>
                    <p class="text-gray-600 dark:text-gray-400">Securely upload images, PDFs, documents</p>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-sun text-3xl text-orange-600"></i>
                    </div>
                    <h3 class="text-2xl font-semibold mb-3">Dark Mode & More</h3>
                    <p class="text-gray-600 dark:text-gray-400">Customize theme, language, and preferences</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-100 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 text-center text-gray-600 dark:text-gray-400">
            <p class="font-medium">© <?= date('Y') ?> My Account. Built with PHP & Tailwind.</p>
            <p class="text-sm mt-2">Your data stays yours — private, secure, and offline-first.</p>
        </div>
    </footer>

</body>
</html>