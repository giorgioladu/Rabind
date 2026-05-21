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

require_once __DIR__ . '/../lib/auth.php';
requireAuth();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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


/* =====================
   log sessioni
===================== */

$stmt = $radiusDb->prepare("
            SELECT
            acctstarttime,
            acctstoptime,
            acctsessiontime,
            acctinputoctets,
            acctoutputoctets,
            callingstationid,
            framedipaddress
        FROM radacct
        WHERE username = ?
        ORDER BY acctstarttime DESC
        LIMIT 20
");

$stmt->execute([$username]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);


header('Content-Type: application/json');

echo json_encode([
    "traffic"=>$trafficData,
    "failed"=>$failedData,
    "macs"=>$macData,
    "sessions"=> $sessions
]);
?>
