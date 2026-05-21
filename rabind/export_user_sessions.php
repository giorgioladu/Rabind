<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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