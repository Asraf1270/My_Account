<?php
// Purpose: Script to seed an admin user via CLI or HTTP (insecure for production; use CLI).
// Place: project/seed_admin.php
// Usage: CLI: php seed_admin.php username email password [role=admin]
//        HTTP: Access with GET params ?username=...&email=...&password=...&role=admin (not recommended)

declare(strict_types=1);

$usersFile = __DIR__ . '/data/users.json';
$dataDir = __DIR__ . '/data';
$profilesDir = $dataDir . '/profiles';
$usersUploadDir = $dataDir . '/users';

// Create directories if needed
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
if (!is_dir($profilesDir)) mkdir($profilesDir, 0755, true);
if (!is_dir($usersUploadDir)) mkdir($usersUploadDir, 0755, true);

$isCli = php_sapi_name() === 'cli';

if ($isCli) {
    if ($argc < 4) {
        die("Usage: php seed_admin.php username email password [role=admin]\n");
    }
    $username = $argv[1];
    $email = $argv[2];
    $password = $argv[3];
    $role = $argv[4] ?? 'admin';
} else {
    $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);
    $password = filter_input(INPUT_GET, 'password', FILTER_DEFAULT);
    $role = filter_input(INPUT_GET, 'role', FILTER_DEFAULT) ?? 'admin';
}

if (!$username || !$email || !$password) {
    die("Missing parameters.\n");
}

// Atomic write
$handle = fopen($usersFile, 'c+');
if ($handle === false) {
    die("Unable to access users file.\n");
}
flock($handle, LOCK_EX);
$content = fread($handle, filesize($usersFile) ?: 0);
$users = json_decode($content, true) ?: [];
$maxId = 0;
foreach ($users as $id => $user) {
    $maxId = max($maxId, (int)$id);
}
$newId = max(1000, $maxId + 1); // Start from 1000 if empty
$users[$newId] = [
    'id' => $newId,
    'username' => $username,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role' => $role,
    'created_at' => date('Y-m-d H:i:s')
];
ftruncate($handle, 0);
rewind($handle);
fwrite($handle, json_encode($users, JSON_PRETTY_PRINT));
flock($handle, LOCK_UN);
fclose($handle);

// Create user folders and profile
$userDir = $usersUploadDir . '/' . $newId . '/uploads';
if (!is_dir($userDir)) mkdir($userDir, 0755, true);
$profileFile = $profilesDir . '/profile_' . $newId . '.json';
file_put_contents($profileFile, json_encode([
    'user_id' => $newId,
    'full_name' => '',
    'bio' => '',
    'avatar' => ''
], JSON_PRETTY_PRINT));

echo "Admin user created with ID $newId.\n";