<?php

require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';

if (!isset($_GET['u'])) {
    die("Utente non specificato");
}

$username = $_GET['u'];

$stmt = $appDb->prepare("
    DELETE FROM radius.radacct
    WHERE UserName = ?
");

$stmt->execute([$username]);

header("Location: users.php");
exit;
?>
