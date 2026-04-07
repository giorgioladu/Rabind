<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';

if (!isset($_GET['u'])) {
    header("Location: users.php");
    exit;
}

$username = $_GET['u'];

// 1. Controlliamo lo stato attuale nel database dell'app
$stmt = $appDb->prepare("SELECT type FROM rabind_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php?error=notfound");
    exit;
}

if ($user['type'] === 'disabled') {
    /* ==================================================
       AZIONE: RIATTIVARE (da disabled -> enabled)
    ================================================== */

    // Rimuoviamo il blocco da FreeRADIUS
    $stmtRadius = $radiusDb->prepare("
        DELETE FROM radcheck
        WHERE username = ? AND attribute = 'Auth-Type' AND value = 'Reject'
    ");
    $stmtRadius->execute([$username]);

    // Aggiorniamo il database interno
    $stmtApp = $appDb->prepare("UPDATE rabind_users SET type='active' WHERE username=?");
    $stmtApp->execute([$username]);

} else {
    /* ==================================================
       AZIONE: DISATTIVARE (da enabled -> disabled)
    ================================================== */

    // Inseriamo il blocco in FreeRADIUS
    // Usiamo una logica "INSERT o IGNORE" per evitare errori se esiste già
    try {
        $stmtRadius = $radiusDb->prepare("
            INSERT INTO radcheck (username, attribute, op, value)
            VALUES (?, 'Auth-Type', ':=', 'Reject')
        ");
        $stmtRadius->execute([$username]);
    } catch (Exception $e) {
        // Se l'attributo esiste già ma magari con valore diverso, lo forziamo
        $stmtRadius = $radiusDb->prepare("
            UPDATE radcheck SET value='Reject'
            WHERE username=? AND attribute='Auth-Type'
        ");
        $stmtRadius->execute([$username]);
    }

    // Aggiorniamo il database interno
    $stmtApp = $appDb->prepare("UPDATE rabind_users SET type='disabled' WHERE username=?");
    $stmtApp->execute([$username]);
}

header("Location: users.php");
exit;