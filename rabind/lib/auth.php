<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }

    checkSessionTimeout();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Controlla il timeout della sessione (CORRETTO: rimosso 'private')
 */
function checkSessionTimeout(): void
{
    if (isset($_SESSION['login_time'])) {
        if ((time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
            session_destroy();
            header("Location: login.php?timeout=1");
            exit;
        }
        $_SESSION['login_time'] = time();
    }
}

/**
 * Verifica se l'IP o l'username sono temporaneamente bloccati per troppi tentativi falliti.
 * Soglia: max 5 tentativi negli ultimi 10 minuti.
 */
function isBruteForce(PDO $db, string $username, string $ip): bool
{
    $max_attempts = 5;
    $time_window = '10 MINUTE';

    $sql = "SELECT COUNT(*) FROM login_attempts
            WHERE (username = :username OR ip = :ip)
            AND attempt_time > NOW() - INTERVAL $time_window";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'username' => $username,
        'ip' => $ip
    ]);

    return ((int)$stmt->fetchColumn()) >= $max_attempts;
}

/**
 * Registra un tentativo di login fallito nel database e pulisce i vecchi record.
 */
function registerLoginAttempt(PDO $db, string $username, string $ip): void
{
    // Inserisce il tentativo fallito
    $stmt = $db->prepare("INSERT INTO login_attempts (username, ip) VALUES (:username, :ip)");
    $stmt->execute([
        'username' => $username,
        'ip' => $ip
    ]);

    // Garbage collector: cancella i record più vecchi di 24 ore per mantenere la tabella leggera
    if (rand(1, 100) === 1) { // 1% di probabilità ad ogni tentativo fallito per non appesantire la query
        $db->exec("DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL 1 DAY");
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