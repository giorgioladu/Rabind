<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';

// Controllo del metodo della richiesta: se GET riporta un errore
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("<h3>Errore: Metodo non consentito</h3>");
}


requireCsrf($_POST['csrf_token'] ?? null); // Funzione in auth.php[cite: 3]

$wifi_ssid = SITE_WIFI_SSID;
$wifi_password = SITE_WIFI_PASSWORD;

$users = [];

// Recupero dei dati tramite POST
if (isset($_POST['users'])) {
    // Gestisce sia l'array delle checkbox sia la vecchia stringa separata da virgole
    if (is_array($_POST['users'])) {
        $list = $_POST['users'];
    } else {
        $list = explode(",", $_POST['users']);
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
<?php endif; ?>

</div>

<br>

<center class="no-print">
    <a href="users.php" class="btn-actions btn-back">⬅ Torna a Utenti</a>
    <button onclick="window.print()" class="btn-actions btn-print">🖨 Stampa</button>
</center>

</body>
</html>