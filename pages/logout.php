<?php
// Purpose: Logout script to destroy session and redirect.
// Place: project/pages/logout.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

logout_user();
header('Location: /pages/login.php');
exit;