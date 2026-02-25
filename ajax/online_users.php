<?php
require_once __DIR__ . '/../lib/auth.php';
requireAuth();

require_once __DIR__ . '/../lib/db.php';

$stmt = $radiusDb->query("
    SELECT username,
           callingstationid,
           framedipaddress,
           acctstoptime
    FROM radacct
    ORDER BY acctstarttime DESC
");

$rows = $stmt->fetchAll();

/* Creiamo una lista utenti unici con stato */
$users = [];

foreach($rows as $r){

    $users[$r['username']] = [
        'username' => $r['username'],
        'ip' => $r['framedipaddress'],
        'mac' => $r['callingstationid'],
        'online' => ($r['acctstoptime'] === null)
    ];
}

header('Content-Type: application/json');
echo json_encode(array_values($users));

?>
