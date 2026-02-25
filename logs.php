<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';

/* =========================
   LOGIN FALLITI
========================= */

$failed = $radiusDb->query("
    SELECT username, authdate, reply
    FROM radpostauth
    WHERE reply LIKE '%Reject%'
    ORDER BY authdate DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   UTENTI ONLINE
========================= */

$online = $radiusDb->query("
    SELECT username,
           framedipaddress,
           callingstationid,
           acctinputoctets,
           acctoutputoctets
    FROM radacct
    WHERE acctstoptime IS NULL
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Query Top Auth Attempts
========================= */
$topAuth = $radiusDb->query("
    SELECT username,
           COUNT(*) AS attempts
    FROM radpostauth
    GROUP BY username
    ORDER BY attempts DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Query Top Traffic
========================= */
$topTraffic = $radiusDb->query("
    SELECT username,
           SUM(acctinputoctets + acctoutputoctets) AS total_traffic
    FROM radacct
    GROUP BY username
    ORDER BY total_traffic DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Funzione traffico leggibile
========================= */

function formatBytes($bytes){

    if($bytes <= 0) return "0 B";

    $units = ['B','KB','MB','GB','TB'];

    $i = floor(log($bytes,1024));

    return round($bytes/pow(1024,$i),2).' '.$units[$i];
}

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/menu.php';
?>

<div class="container">

<h4>📊 Logs RADIUS</h4>

<hr>

<!-- ================= ONLINE USERS ================= -->

<h5 class="mt-5 text-success">🟢 Utenti Online</h5>
<table class="table table-bordered table-sm">

<thead class="table-dark">
<tr>
<th>User</th>
<th>IP</th>
<th>MAC</th>
<th>Download</th>
<th>Upload</th>
<th>Totale</th>
</tr>
</thead>

<tbody>

<?php foreach($online as $u): ?>

<?php
$total = $u['acctinputoctets'] + $u['acctoutputoctets'];
?>

<tr>
<td><?= htmlspecialchars($u['username']) ?></td>
<td><?= $u['framedipaddress'] ?></td>
<td><?= $u['callingstationid'] ?></td>
<td><?= formatBytes($u['acctinputoctets']) ?></td>
<td><?= formatBytes($u['acctoutputoctets']) ?></td>
<td><?= formatBytes($total) ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<br><br>
<!-- ================= Top Traffic ================= -->

<h5 class="mt-5">🔥 Top Consumo Traffico</h5>

<table class="table table-striped table-sm">

<thead class="table-dark">
<tr>
<th>User</th>
<th>Traffico Totale</th>
</tr>
</thead>

<tbody>

<?php foreach($topTraffic as $t): ?>

<tr>
<td><?= htmlspecialchars($t['username']) ?></td>
<td><?= formatBytes($t['total_traffic']) ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<br><br>
<!-- ================= Top Login ================= -->
<h5 class="mt-5">⚠️ Top Tentativi Login</h5>

<table class="table table-striped table-sm">
<thead class="table-dark">
<tr>
<th>User</th>
<th>Tentativi</th>
</tr>
</thead>

<tbody>

<?php foreach($topAuth as $a): ?>

<tr>
<td><?= htmlspecialchars($a['username']) ?></td>
<td><?= $a['attempts'] ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<br><br>
<!-- ================= FAILED LOGINS ================= -->
<h5 class="mt-4 text-danger">❌ Login Falliti</h5>

<table class="table table-striped table-sm">
<thead>
<tr>
<th>User</th>
<th>Time</th>
</tr>
</thead>

<tbody>

<?php foreach($failed as $f): ?>
<tr>
<td><?= htmlspecialchars($f['username']) ?></td>
<td><?= $f['authdate'] ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>