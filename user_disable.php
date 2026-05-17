<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/coa.php';

// 1. Forza metodo POST e valida il CSRF[cite: 6]
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit;
}
requireCsrf($_POST['csrf_token'] ?? null); // Funzione in auth.php[cite: 3]

// 2. Prendi lo username da POST invece che da GET
$username = $_POST['u'] ?? '';
if (!$username) {
    header("Location: users.php?error=missing_user");
    exit;
}

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
        $stmt = $radiusDb->prepare("SELECT acctsessionid, framedipaddress FROM radacct WHERE username = ? AND acctstoptime IS NULL LIMIT 1");
        $stmt->execute([$username]);
        $session = $stmt->fetch();

        if ($session) {
            $coa = sprintf("User-Name=%s, Acct-Session-Id=%s, Framed-IP-Address=%s", $username, $session['acctsessionid'], $session['framedipaddress']);
            radiusDisconnect(RADIUS_NAS_IP, RADIUS_NAS_PORT, RADIUS_SECRET, $coa);
        }

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