<?php
// File: includes/footer.php
// Purpose: Clean, responsive footer for all pages

declare(strict_types=1);
?>

</main>

<footer class="mt-auto bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6 text-center md:text-left">
            <div>
                <p class="text-gray-600 dark:text-gray-400 font-medium">© <?= date('Y') ?> My Account. All rights reserved.</p>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">Your personal productivity dashboard</p>
            </div>

            <div class="flex gap-6 text-sm">
                <a href="/pages/settings.php" class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">Settings</a>
                <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">Privacy</a>
                <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">Terms</a>
                <a href="https://github.com" target="_blank" class="text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">GitHub</a>
            </div>

            <div class="text-sm text-gray-500 dark:text-gray-400">
                Built with ❤️ using PHP & Tailwind
            </div>
        </div>
    </div>
</footer>

</body>
</html>