<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/coa.php';

if(!isset($_GET['u'])){
    header("Location: dashboard.php");
    exit;
}

$username = $_GET['u'];

$stmt = $radiusDb->prepare("
    SELECT acctsessionid, framedipaddress
    FROM radacct
    WHERE username = ?
    AND acctstoptime IS NULL
    LIMIT 1
");
$stmt->execute([$username]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

$coa = "User-Name=$username, Acct-Session-Id={$session['acctsessionid']},Framed-IP-Address={$session['framedipaddress']}";

radiusDisconnect(
    RADIUS_NAS_IP,
    RADIUS_NAS_PORT,
    RADIUS_SECRET,
    $coa
);

header("Location: dashboard.php");
exit;

?>
