<?php
require_once __DIR__ . '/../lib/auth.php';
requireAuth();
require_once __DIR__ . '/../lib/db.php';

/**
 * Questa query fa una UNION tra le sessioni (radacct) e i tentativi di login (radpostauth)
 * per avere una cronologia completa di cosa è successo a ogni utente.
 */
$query = "
    (SELECT username, callingstationid, framedipaddress, acctstoptime as last_event, 'SESSION' as type, NULL as reply
     FROM radacct)
    UNION
    (SELECT username, '' as callingstationid, '' as framedipaddress, authdate as last_event, 'AUTH' as type, reply
     FROM radpostauth)
    ORDER BY last_event DESC LIMIT 10
";

$stmt = $radiusDb->query($query);
$rows = $stmt->fetchAll();

$users = [];

foreach($rows as $r){
    $username = $r['username'];

    // Se abbiamo già elaborato l'utente (che è il record più recente), saltiamo i vecchi
    if (isset($users[$username])) {
        continue;
    }

    $status = 'offline';
    $reason = '';

    if ($r['type'] === 'SESSION') {
        if ($r['last_event'] === null || $r['last_event'] == '0000-00-00 00:00:00') {
            $status = 'online';
        }
    } else {
        // Se l'ultimo evento è un AUTH ed è un Access-Reject
        if ($r['reply'] === 'Access-Reject') {
            $status = 'rejected';
            $reason = 'Limite raggiunto o Password errata';
        }
    }

    $users[$username] = [
        'username' => $username,
        'ip'       => $r['framedipaddress'] ?: '-',
        'mac'      => $r['callingstationid'] ?: '-',
        'status'   => $status,
        'reason'   => $reason,
        'last_log' => $r['last_event']
    ];
}

header('Content-Type: application/json');
echo json_encode(array_values($users));
