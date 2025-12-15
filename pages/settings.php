<?php
// File: pages/settings.php
// Purpose: User settings - theme, language, privacy, 2FA placeholder

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/settings_handler.php';

require_login();

$userId = (int)$_SESSION['user_id'];
$userSettingsFile = __DIR__ . "/../data/users/{$userId}/settings.json";

$settings = load_user_settings($userId);

$csrfToken = create_csrf_token();

// Handle form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $theme = $_POST['theme'] ?? 'light';
    $language = $_POST['language'] ?? 'en';
    $show_email = !empty($_POST['show_email']);
    $newsletter = !empty($_POST['newsletter']);

    if (!in_array($theme, ['light', 'dark'])) $theme = 'light';
    if (!in_array($language, ['en', 'bn'])) $language = 'en';

    $newSettings = [
        'theme' => $theme,
        'language' => $language,
        'privacy' => [
            'show_email' => $show_email,
            'newsletter' => $newsletter
        ],
        'two_factor_enabled' => $settings['two_factor_enabled'] ?? false, // placeholder
        'updated_at' => date('Y-m-d H:i:s')
    ];

    save_user_settings($userId, $newSettings);
    $settings = $newSettings; // refresh
    $message = 'Settings saved successfully!';
}

// Apply theme to body class
$bodyClass = $settings['theme'] === 'dark' ? 'dark bg-gray-900 text-white' : 'bg-gray-50';

// Simple language strings
$lang = $settings['language'] === 'bn' ? [
    'title' => 'সেটিংস',
    'appearance' => 'চেহারা',
    'theme' => 'থিম',
    'light' => 'হালকা',
    'dark' => 'অন্ধকার',
    'language' => 'ভাষা',
    'english' => 'ইংরেজি',
    'bangla' => 'বাংলা',
    'privacy' => 'গোপনীয়তা',
    'show_email' => 'প্রোফাইলে ইমেইল দেখান',
    'newsletter' => 'নিউজলেটার গ্রহণ করুন',
    'security' => 'নিরাপত্তা',
    '2fa' => 'টু-ফ্যাক্টর অথেনটিকেশন (2FA)',
    '2fa_desc' => 'আপনার অ্যাকাউন্টে অতিরিক্ত নিরাপত্তা যোগ করুন (Google Authenticator ব্যবহার করে)',
    'save' => 'সেটিংস সংরক্ষণ করুন'
] : [
    'title' => 'Settings',
    'appearance' => 'Appearance',
    'theme' => 'Theme',
    'light' => 'Light',
    'dark' => 'Dark',
    'language' => 'Language',
    'english' => 'English',
    'bangla' => 'Bangla',
    'privacy' => 'Privacy',
    'show_email' => 'Show email on profile',
    'newsletter' => 'Receive newsletter',
    'security' => 'Security',
    '2fa' => 'Two-Factor Authentication (2FA)',
    '2fa_desc' => 'Add an extra layer of security using Google Authenticator (TOTP)',
    'save' => 'Save Settings'
];
?>

<!DOCTYPE html>
<html lang="<?= $settings['language'] ?>" class="h-full <?= $bodyClass ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['title'] ?> - My Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</head>
<body class="min-h-full <?= $bodyClass ?>">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8"><?= $lang['title'] ?></h1>

        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-md">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <!-- Appearance -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4"><?= $lang['appearance'] ?></h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2"><?= $lang['theme'] ?></label>
                        <div class="flex gap-6">
                            <label class="flex items-center">
                                <input type="radio" name="theme" value="light" <?= $settings['theme'] === 'light' ? 'checked' : '' ?> class="mr-2">
                                <span><?= $lang['light'] ?></span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="theme" value="dark" <?= $settings['theme'] === 'dark' ? 'checked' : '' ?> class="mr-2">
                                <span><?= $lang['dark'] ?></span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2"><?= $lang['language'] ?></label>
                        <select name="language" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700">
                            <option value="en" <?= $settings['language'] === 'en' ? 'selected' : '' ?>><?= $lang['english'] ?></option>
                            <option value="bn" <?= $settings['language'] === 'bn' ? 'selected' : '' ?>><?= $lang['bangla'] ?></option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Privacy -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4"><?= $lang['privacy'] ?></h2>
                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="show_email" <?= ($settings['privacy']['show_email'] ?? false) ? 'checked' : '' ?> class="mr-3">
                        <span><?= $lang['show_email'] ?></span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="newsletter" <?= ($settings['privacy']['newsletter'] ?? false) ? 'checked' : '' ?> class="mr-3">
                        <span><?= $lang['newsletter'] ?></span>
                    </label>
                </div>
            </div>

            <!-- Security (2FA Placeholder) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4"><?= $lang['security'] ?></h2>
                <div class="space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-medium"><?= $lang['2fa'] ?></h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?= $lang['2fa_desc'] ?></p>
                        </div>
                        <div class="flex items-center">
                            <span class="mr-3 text-sm text-gray-500">Coming soon</span>
                            <button type="button" disabled class="bg-gray-300 text-gray-600 px-4 py-2 rounded-md cursor-not-allowed">
                                Enable
                            </button>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 bg-gray-100 dark:bg-gray-700 p-4 rounded">
                        <strong>How to implement TOTP 2FA (future):</strong><br>
                        1. Use <code>composer require pragmarx/google2fa</code><br>
                        2. On enable: generate secret → save encrypted in user settings → show QR code<br>
                        3. On login: after password, verify code with Google2FA::verifyKey()<br>
                        4. Add recovery codes as fallback.
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-md hover:bg-indigo-700 text-lg font-medium">
                    <?= $lang['save'] ?>
                </button>
            </div>
        </form>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>