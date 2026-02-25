<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';

if (!isset($_GET['u'])) {
    header("Location: users.php");
    exit;
}

$username = $_GET['u'];

/* Cancella MAC binding */
$stmt = $radiusDb->prepare("
    DELETE FROM radcheck
    WHERE username=?
    AND attribute='Calling-Station-Id'
");

$stmt->execute([$username]);


/* opzione futura: loggare l'operazione
*/


header("Location: users.php");
exit;
