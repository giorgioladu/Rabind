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
 * Verifica se il token CSRF fornito corrisponde a quello in sessione.
 * Usa hash_equals per evitare timing attacks.
 */
function validateCsrfToken(?string $token): bool
{
    return $token !== null
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Blocca l'esecuzione se il token non è valido.
 */
function requireCsrf(?string $token): void
{
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        die("Errore di sicurezza: Richiesta non autorizzata o sessione scaduta.");
    }
}

/**
 * Protegge le operazioni di scrittura (POST) verificando il token CSRF.
 * Da chiamare solo nei file che elaborano dati.
 */
function protectWriteOperations(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
            http_response_code(403);
            die("Errore di sicurezza: Token non valido o sessione scaduta.");
        }
    }
}

/**
 * Require authentication.
 *
 * Garantisce anche che il token CSRF esista sempre in sessione,
 * indipendentemente da quando la sessione è stata creata.
 * In questo modo i template possono usare $_SESSION['csrf_token']
 * senza rischiare un Undefined index / errore 500.
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }

    // Genera il token se manca (es. sessioni pre-esistenti senza token,
    // o prima del login con token generato solo in login.php)
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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