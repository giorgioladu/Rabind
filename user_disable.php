<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';

if (!isset($_GET['u'])) {
    header("Location: users.php");
    exit;
}

$username = $_GET['u'];

/* =========================
   Metodo FreeRADIUS Disabilita via Auth-Type Reject
========================= */

$stmt = $radiusDb->prepare("
    INSERT INTO radcheck (username, attribute, op, value)
    VALUES (?, 'Auth-Type', ':=', 'Reject')
");

try {
    $stmt->execute([$username]);
} catch (Exception $e) {
    /* Se esiste già → aggiorniamo */
    $stmt = $radiusDb->prepare("
        UPDATE radcheck
        SET value='Reject'
        WHERE username=?
        AND attribute='Auth-Type'
    ");
    $stmt->execute([$username]);
}

/* =========================
   Flag interno RaBind
========================= */

$stmt = $appDb->prepare("
    UPDATE rabind_users
    SET type='disabled'
    WHERE username=?
");

$stmt->execute([$username]);


header("Location: users.php");
exit;
