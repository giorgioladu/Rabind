<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth(); //
require_once __DIR__ . '/lib/db.php'; //

$username = $_GET['u'] ?? null;
if (!$username) { die("Username mancante."); }

// Fetch di tutte le sessioni senza il limite di 100 per l'export
$stmt = $radiusDb->prepare("
    SELECT acctstarttime, acctstoptime, framedipaddress, callingstationid,
           acctsessiontime, acctinputoctets, acctoutputoctets, acctterminatecause
    FROM radacct
    WHERE username = ?
    ORDER BY acctstarttime DESC
");
$stmt->execute([$username]);
$sessions = $stmt->fetchAll();

// Header per forzare il download del file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=sessioni_' . $username . '_' . date('Ymd') . '.csv');

// Apertura dello stream di output
$output = fopen('php://output', 'w');

// Intestazioni delle colonne CSV
fputcsv($output, ['Inizio', 'Fine', 'IP Address', 'MAC Address', 'Secondi', 'Download (Bytes)', 'Upload (Bytes)', 'Causa Chiusura']);

// Scrittura dei dati
foreach ($sessions as $s) {
    fputcsv($output, [
        $s['acctstarttime'],
        $s['acctstoptime'] ?? 'ATTIVA',
        $s['framedipaddress'],
        $s['callingstationid'],
        $s['acctsessiontime'],
        $s['acctoutputoctets'], // Radius output = User Download
        $s['acctinputoctets'],  // Radius input = User Upload
        $s['acctterminatecause']
    ]);
}

fclose($output);
exit;
?>