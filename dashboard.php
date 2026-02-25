<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/templates/header.php';

/* =========================
   STATISTICHE BASE
========================= */

/* Utenti online */
$stmt = $radiusDb->query("
    SELECT COUNT(*) AS total
    FROM radacct
    WHERE acctstoptime IS NULL
");
$online_users = $stmt->fetch()['total'];

/* Login success */
$stmt = $radiusDb->query("
    SELECT COUNT(*) AS total
    FROM radpostauth
    WHERE reply='Access-Accept'
");
$login_ok = $stmt->fetch()['total'];

/* Login reject */
$stmt = $radiusDb->query("
    SELECT COUNT(*) AS total
    FROM radpostauth
    WHERE reply='Access-Reject'
");
$login_fail = $stmt->fetch()['total'];


/* =========================
   STATISTICHE 7 GIORNI
========================= */

$days = [];
$successDays = [];
$failDays = [];
$trafficDays = [];
$trafficValues = [];

for ($i = 6; $i >= 0; $i--) {

    $date = date('Y-m-d', strtotime("-$i days"));
    $days[] = $date;

    /* Login Success */
    $stmt = $radiusDb->prepare("
        SELECT COUNT(*) total
        FROM radpostauth
        WHERE reply='Access-Accept'
        AND DATE(authdate)=?
    ");
    $stmt->execute([$date]);
    $successDays[] = $stmt->fetch()['total'];

    /* Login Fail */
    $stmt = $radiusDb->prepare("
        SELECT COUNT(*) total
        FROM radpostauth
        WHERE reply='Access-Reject'
        AND DATE(authdate)=?
    ");
    $stmt->execute([$date]);
    $failDays[] = $stmt->fetch()['total'];

    /* Traffico */
    $stmt = $radiusDb->prepare("
        SELECT SUM(acctinputoctets + acctoutputoctets) AS traffic
        FROM radacct
        WHERE DATE(acctstarttime)=?
    ");
    $stmt->execute([$date]);

    $traffic = $stmt->fetch()['traffic'] ?? 0;

    $trafficDays[] = $date;
    $trafficValues[] = round($traffic / 1048576, 2); // MB
}

   $stmt = $radiusDb->query("
    SELECT username,
           callingstationid,
           framedipaddress
    FROM radacct
    WHERE acctstoptime IS NULL
");

$onlineUsers = $stmt->fetchAll();

?>

<h3 class="mb-4">
    <?php echo APP_NAME; ?> Dashboard
</h3>

<!-- =========================
     CARDS STATISTICHE
========================= -->

<div class="row">

    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center">
                <h6>Utenti Online</h6>
                <h2><?php echo $online_users; ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center">
                <h6>Login Success</h6>
                <h2 class="text-success"><?php echo $login_ok; ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center">
                <h6>Login Fail</h6>
                <h2 class="text-danger"><?php echo $login_fail; ?></h2>
            </div>
        </div>
    </div>

</div>


<!-- =========================
     GRAFICI
========================= -->

<div class="row mt-4">

    <div class="col-md-6">
        <div class="card shadow-sm p-3">
            <h6 class="mb-3">Login ultimi 7 giorni</h6>
            <canvas id="loginChart"></canvas>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm p-3">
            <h6 class="mb-3">Traffico ultimi 7 giorni (MB)</h6>
            <canvas id="trafficChart"></canvas>
        </div>
    </div>

</div>

<div class="card shadow-sm p-3 mt-4">

<h5>Utenti Online</h5>

<table class="table table-hover">

<thead class="table-dark">
<tr>
<th>Username</th>
<th>IP</th>
<th>MAC</th>
<th>Azioni</th>
</tr>
</thead>

<tbody id="onlineUsersBody">

<?php foreach($onlineUsers as $u): ?>
<tr>
<td><?php echo htmlspecialchars($u['username']); ?></td>
<td><?php echo $u['framedipaddress']; ?></td>
<td><?php echo $u['callingstationid']; ?></td>
<td>

<a class="btn btn-danger btn-sm"
href="user_disconnect.php?u=<?php echo urlencode($u['username']); ?>">

Disconnect

</a>
</td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const loginChart = new Chart(
    document.getElementById('loginChart'),
    {
        type: 'line',
        data: {
            labels: <?php echo json_encode($days); ?>,
            datasets: [
                {
                    label: 'Success',
                    data: <?php echo json_encode($successDays); ?>,
                    borderWidth: 2
                },
                {
                    label: 'Reject',
                    data: <?php echo json_encode($failDays); ?>,
                    borderWidth: 2
                }
            ]
        }
    }
);

const trafficChart = new Chart(
    document.getElementById('trafficChart'),
    {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($trafficDays); ?>,
            datasets: [{
                label: 'Traffico MB',
                data: <?php echo json_encode($trafficValues); ?>,
                borderWidth: 1
            }]
        }
    }
);

</script>
<script src="js/rabind_refresh.js"></script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
