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
 * Verifica se il token CSRF fornito corrisponde a quello in sessione
 */
function validateCsrfToken(?string $token): bool
{
    return $token !== null && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Blocca l'esecuzione se il token non è valido
 */
function requireCsrf(string $token): void
{
    if (!validateCsrfToken($token)) {
        die("Errore di sicurezza: Richiesta non autorizzata o sessione scaduta.");
    }
}

/**
 * Require authentication
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Protegge le operazioni di scrittura (POST) verificando il token CSRF.
 * Da chiamare solo nei file che elaborano dati.
 */
function protectWriteOperations(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die("Errore di sicurezza: Token non valido o sessione scaduta.");
        }
    }
}

/**
 * Force logout (session destroy)
 */
function logout(): void
{
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit;
}
