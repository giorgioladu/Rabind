<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';

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

/* Cancella MAC binding */
$stmt = $radiusDb->prepare("
    DELETE FROM radcheck
    WHERE username=?
    AND attribute='Calling-Station-Id'
");

$stmt->execute([$username]);


/* opzione futura: loggare l'operazione
*/


header("Location: users.php?success=reset_mac");
exit;
