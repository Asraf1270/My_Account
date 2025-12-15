<?php
// Purpose: User login page with form and backend processing.
// Place: project/pages/login.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$usersFile = __DIR__ . '/../data/users.json';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!verify_csrf_token(filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        // Sanitize inputs
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);

        if (!$username || !$password) {
            $errors[] = 'Username and password are required.';
        } else {
            $users = json_read($usersFile);
            $found = false;
            foreach ($users as $id => $user) {
                if ($user['username'] === $username && password_verify($password, $user['password_hash'])) {
                    login_user((int)$id);
                    header('Location: /pages/profile.php'); // Redirect to profile or dashboard
                    exit;
                }
            }
            if (!$found) {
                $errors[] = 'Invalid username or password.';
            }
        }
    }
}

$csrfToken = create_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="/assets/css/styles.css"> <!-- Assuming Tailwind CSS -->
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold">Login</h1>
        <?php if (!empty($errors)): ?>
            <ul class="text-red-500">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div>
                <label>Username:</label>
                <input type="text" name="username" class="border p-2 w-full" required>
            </div>
            <div>
                <label>Password:</label>
                <input type="password" name="password" class="border p-2 w-full" required>
            </div>
            <button type="submit" class="bg-blue-500 text-white p-2">Login</button>
        </form>
    </div>
</body>
</html>