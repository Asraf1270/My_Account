<?php
// File: includes/csrf.php
// Purpose: Secure CSRF protection helpers.
//          Automatically starts session if not already started.
//          Must be included AFTER declare(strict_types=1); in files that use it.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate (or reuse) a CSRF token and store it in session.
 * @return string The CSRF token
 */
function create_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token.
 * Regenerates a new token after successful verification (one-time use).
 * @param ?string $token Token from the form
 * @return bool True if valid
 */
function verify_csrf_token(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    $valid = hash_equals($_SESSION['csrf_token'], $token);

    // One-time token – invalidate old one and generate a fresh one
    unset($_SESSION['csrf_token']);
    create_csrf_token();

    return $valid;
}