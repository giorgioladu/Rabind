<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';

$wifi_ssid = SITE_WIFI_SSID;
$wifi_password = SITE_WIFI_PASSWORD;

$users = [];

if (isset($_GET['users'])) {

    $list = explode(",", $_GET['users']);

    $placeholders = implode(',', array_fill(0, count($list), '?'));

    $stmt = $radiusDb->prepare("
        SELECT username, value AS password
        FROM radcheck
        WHERE username IN ($placeholders)
        AND attribute='Cleartext-Password'
    ");

    $stmt->execute($list);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Stampa credenziali</title>

<style>

@page {
    size: A4;
    margin: 1cm;
}

body{
    font-family: Arial;
}

.container{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:20px;
}

.card{
    border:2px dashed #333;
    padding:20px;
    text-align:center;
    page-break-inside: avoid;
}

.wifi{
    background:#f5f5f5;
    padding:10px;
    margin:10px 0;
}

button{
    padding:10px 20px;
    font-size:16px;
}

@media print{
    button{
        display:none;
    }
}

</style>

</head>

<body>

<h2 style="text-align:center">
RaBind - Credenziali Accesso
</h2>

<div class="container">

<?php foreach($users as $u): ?>

<div class="card">
 <h4>🌐 Personal Wifi Vouchers</h4>
<div class="wifi">
<p>

Username: <strong><?= htmlspecialchars($u['username']) ?></strong><br>
🔑 Password:  <b><?= htmlspecialchars($u['password']) ?> </b><br><br><br>
📶 WiFi: <b><?= $wifi_ssid ?></b><br>
🔑 Password WiFi: <b><?= $wifi_password ?></b><br><br>

<span class="note">
✔ Valid for one device.<br>
If you want to extend time, please contact reception.
</span>

</p>
</div>

</div>

<?php endforeach; ?>

</div>

<br>

<center>
<button onclick="window.print()">🖨 Stampa</button>
</center>

</body>
</html>