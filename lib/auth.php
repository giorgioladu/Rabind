<?php
require_once __DIR__ . '/config.php';

/**
 * Check if admin is authenticated
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
}

/**
 * Require authentication
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        header("Location: /login.php");
        exit;
    }
}

/**
 * Force logout (session destroy)
 */
function logout(): void
{
    $_SESSION = [];
    session_destroy();
    header("Location: /login.php");
    exit;
}
