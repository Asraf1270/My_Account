<?php
// Purpose: User registration page with form and backend processing.
// Place: project/pages/register.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$usersFile = __DIR__ . '/../data/users.json';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!verify_csrf_token(filter_input(INPUT_POST, 'csrf_token', FILTER_DEFAULT))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        // Sanitize and validate inputs
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);
        $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_DEFAULT);

        if (!$username || strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        if (!$email) {
            $errors[] = 'Invalid email address.';
        }
        if (!$password || !$confirm_password) {
            $errors[] = 'Passwords are required.';
        } elseif ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        } elseif (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[a-zA-Z]/', $password)) {
            $errors[] = 'Password must be at least 8 characters, with at least 1 number and 1 letter.';
        }

        if (empty($errors)) {
            // Atomic check and write
            $handle = fopen($usersFile, 'c+');
            if ($handle === false) {
                $errors[] = 'Unable to access users file.';
            } else {
                flock($handle, LOCK_EX);
                $content = fread($handle, filesize($usersFile) ?: 0);
                $users = json_decode($content, true) ?: [];
                $usernameExists = false;
                $emailExists = false;
                $maxId = 0;
                foreach ($users as $id => $user) {
                    if ($user['username'] === $username) {
                        $usernameExists = true;
                    }
                    if ($user['email'] === $email) {
                        $emailExists = true;
                    }
                    $maxId = max($maxId, (int)$id);
                }
                if ($usernameExists) {
                    $errors[] = 'Username already exists.';
                }
                if ($emailExists) {
                    $errors[] = 'Email already exists.';
                }
                if (empty($errors)) {
                    $newId = $maxId + 1;
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $users[$newId] = [
                        'id' => $newId,
                        'username' => $username,
                        'email' => $email,
                        'password_hash' => $hashedPassword,
                        'role' => 'user',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    ftruncate($handle, 0);
                    rewind($handle);
                    fwrite($handle, json_encode($users, JSON_PRETTY_PRINT));
                    $success = true;

                    // Create user folders
                    $userDir = __DIR__ . '/../data/users/' . $newId;
                    $uploadsDir = $userDir . '/uploads';
                    if (!is_dir($uploadsDir)) {
                        mkdir($uploadsDir, 0755, true);
                    }

                    // Create profile JSON (empty)
                    $profilesDir = __DIR__ . '/../data/profiles';
                    if (!is_dir($profilesDir)) {
                        mkdir($profilesDir, 0755, true);
                    }
                    $profileFile = $profilesDir . '/profile_' . $newId . '.json';
                    file_put_contents($profileFile, json_encode([
                        'user_id' => $newId,
                        'full_name' => '',
                        'bio' => '',
                        'avatar' => ''
                    ], JSON_PRETTY_PRINT));
                }
                flock($handle, LOCK_UN);
                fclose($handle);
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
    <title>Register</title>
    <link rel="stylesheet" href="/assets/css/styles.css"> <!-- Assuming Tailwind CSS -->
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold">Register</h1>
        <?php if (!empty($errors)): ?>
            <ul class="text-red-500">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="text-green-500">Registration successful! <a href="/pages/login.php">Login</a></p>
        <?php else: ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div>
                    <label>Username:</label>
                    <input type="text" name="username" class="border p-2 w-full" required>
                </div>
                <div>
                    <label>Email:</label>
                    <input type="email" name="email" class="border p-2 w-full" required>
                </div>
                <div>
                    <label>Password:</label>
                    <input type="password" name="password" class="border p-2 w-full" required>
                </div>
                <div>
                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password" class="border p-2 w-full" required>
                </div>
                <button type="submit" class="bg-blue-500 text-white p-2">Register</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>