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
requireAuth();

require_once __DIR__ . '/lib/db.php';

// 2. Controllo CSRF necessario SOLO se i dati arrivano via POST (dal form principale)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf($_POST['csrf_token'] ?? null);
}

$wifi_ssid = SITE_WIFI_SSID;
$wifi_password = SITE_WIFI_PASSWORD;

$users = [];

// 3. Recupero dei dati: controlla prima in POST, poi in GET
$rawUsers = $_POST['users'] ?? $_GET['users'] ?? null;

// Recupero dei dati tramite POST
if ($rawUsers !== null) {
    // Gestisce sia l'array delle checkbox sia la vecchia stringa separata da virgole
    if (is_array($rawUsers)) {
        $list = $rawUsers;
    } else {
        $list = explode(",", $rawUsers);
    }

    if (!empty($list)) {
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

.btn-actions {
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
    margin: 0 10px;
    border-radius: 4px;
}

.btn-print {
    background-color: #212529;
    color: white;
    border: none;
}

.btn-back {
    background-color: #f8f9fa;
    color: #212529;
    border: 1px solid #ccc;
    text-decoration: none;
    display: inline-block;
}

@media print{
    .no-print{
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

<?php if (empty($users)): ?>
    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
        Nessun utente selezionato o trovato.
    </div>
<?php else: ?>
    <?php foreach($users as $u): ?>
    <div class="card">
     <h4>🌐 Personal Wifi Vouchers</h4>
    <div class="wifi">
    <p>
    Username: <strong><?= htmlspecialchars($u['username'] ?? '') ?></strong><br>
    🔑 Password:  <b><?= htmlspecialchars($u['password'] ?? '') ?> </b><br><br><br>
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
<?php endif; ?>

</div>

<br>

<center class="no-print">
    <a href="users.php" class="btn-actions btn-back">⬅ Torna a Utenti</a>
    <button onclick="window.print()" class="btn-actions btn-print">🖨 Stampa</button>
</center>

</body>
</html>