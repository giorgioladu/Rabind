<?php
require_once __DIR__ . '/../lib/auth.php';
requireAuth();

require_once __DIR__ . '/../lib/db.php';

if(!isset($_GET['u'])){
    exit;
}

$username = $_GET['u'];

/* =====================
   Traffico storico
===================== */

$traffic = $radiusDb->prepare("
    SELECT
        acctstarttime,
        acctinputoctets,
        acctoutputoctets
    FROM radacct
    WHERE username = ?
    ORDER BY acctstarttime DESC
    LIMIT 20
");

$traffic->execute([$username]);
$trafficData = $traffic->fetchAll(PDO::FETCH_ASSOC);


/* =====================
   Login falliti
===================== */

$failed = $radiusDb->prepare("
    SELECT authdate, reply
    FROM radpostauth
    WHERE username = ?
    AND reply LIKE '%Reject%'
    ORDER BY authdate DESC
    LIMIT 20
");

$failed->execute([$username]);
$failedData = $failed->fetchAll(PDO::FETCH_ASSOC);


/* =====================
   MAC devices
===================== */

$macs = $radiusDb->prepare("
    SELECT DISTINCT callingstationid
    FROM radacct
    WHERE username = ?
");

$macs->execute([$username]);
$macData = $macs->fetchAll(PDO::FETCH_ASSOC);


header('Content-Type: application/json');

echo json_encode([
    "traffic"=>$trafficData,
    "failed"=>$failedData,
    "macs"=>$macData
]);
?>