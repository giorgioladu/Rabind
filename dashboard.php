<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/menu.php';

// 1. Fetch Stats Generali (Dalle Viste)
$dailyStats = $radiusDb->query("SELECT * FROM view_daily_stats ORDER BY stat_day ASC")->fetchAll();
$profileStats = $radiusDb->query("SELECT * FROM view_profile_stats")->fetchAll();
$onlineUsers = $radiusDb->query("SELECT * FROM view_active_sessions ORDER BY acctstarttime DESC")->fetchAll();

// 2. Calcolo Traffico Totale Mensile (Tutti gli utenti)
// Sommiamo i dati della vista daily_stats per il mese corrente
$currentMonthTraffic = $radiusDb->query("
    SELECT
        SUM(download_mb) as total_down,
        SUM(upload_mb) as total_up
    FROM view_daily_stats
    WHERE DATE_FORMAT(stat_day, '%Y-%m') = DATE_FORMAT(CURRENT_DATE, '%Y-%m')
")->fetch();

$monthlyTotal = (float)($currentMonthTraffic['total_down'] + $currentMonthTraffic['total_up']);

// 3. Preparazione dati per i grafici
$labels = []; $successData = []; $failData = []; $downData = []; $upData = [];
foreach($dailyStats as $s) {
    $labels[] = date('d/m', strtotime($s['stat_day']));
    $successData[] = (int)$s['logins_success'];
    $failData[] = (int)$s['logins_fail'];
    $downData[] = (float)$s['download_mb'];
    $upData[] = (float)$s['upload_mb'];
}

$groupLabels = array_column($profileStats, 'groupname');
$groupCounts = array_column($profileStats, 'total_users');
?>

<script src="js/user_modal.js"></script>

<div class="container-fluid mt-4">
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold">Online Now</h6>
                    <h2 class="mb-0"><?= count($onlineUsers) ?></h2>
                    <i class="bi bi-people position-absolute top-50 end-0 translate-middle-y me-3 opacity-50 fs-1"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold">Logins (24h)</h6>
                    <h2 class="mb-0"><?= !empty($successData) ? end($successData) : 0 ?></h2>
                    <i class="bi bi-check-circle position-absolute top-50 end-0 translate-middle-y me-3 opacity-50 fs-1"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold">Traffic (Today)</h6>
                    <h2 class="mb-0"><?= round(((!empty($downData) ? end($downData) : 0) + (!empty($upData) ? end($upData) : 0)), 2) ?> <small>MB</small></h2>
                    <i class="bi bi-speedometer2 position-absolute top-50 end-0 translate-middle-y me-3 opacity-50 fs-1"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold">Total Month Traffic</h6>
                    <h2 class="mb-0">
                        <?= $monthlyTotal > 1024 ? round($monthlyTotal/1024, 2) . ' <small>GB</small>' : round($monthlyTotal, 2) . ' <small>MB</small>' ?>
                    </h2>
                    <i class="bi bi-cloud-download position-absolute top-50 end-0 translate-middle-y me-3 opacity-50 fs-1"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Analisi Traffico (Download vs Upload)</div>
                <div class="card-body"><canvas id="trafficChart" height="100"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Distribuzione Profili</div>
                <div class="card-body"><canvas id="profileChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span>Utenti Online (Ultimi 8)</span>
                    <a href="online_users.php" class="btn btn-sm btn-outline-primary">Vedi Tutti</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Username</th>
                                <th>IP Address</th>
                                <th>MAC / Device</th>
                                <th>Durata</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach(array_slice($onlineUsers, 0, 8) as $u): ?>
                            <tr>
                                <td class="fw-bold">
                                    <a href="javascript:void(0)" onclick="showUserDetails('<?= htmlspecialchars($u['username']) ?>')">
                                        <?= htmlspecialchars($u['username']) ?: '----' ?>
                                    </a>
                                </td>
                                <td><span class="text-primary"><?= $u['framedipaddress'] ?></span></td>
                                <td><code><?= $u['callingstationid'] ?></code></td>
                                <td><?= $u['duration'] ?></td>
                                <td><a href="user_disconnect.php?u=<?= urlencode($u['username']) ?>" class="btn btn-xs btn-danger">Kick</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg shadow-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Dettagli Sessione Utente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Caricamento dati...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Grafico Traffico
new Chart(document.getElementById('trafficChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            { label: 'Download MB', data: <?= json_encode($downData) ?>, backgroundColor: '#A8EB20' },
            { label: 'Upload MB', data: <?= json_encode($upData) ?>, backgroundColor: '#F25EC8' }
        ]
    },
    options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true } } }
});

// Grafico Profili
new Chart(document.getElementById('profileChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($groupLabels) ?>,
        datasets: [{
            data: <?= json_encode($groupCounts) ?>,
            backgroundColor: ['#FFADAD', '#FFD6A5', '#FDFFB6', '#CAFFBF', '#9BF6FF', '#A0C4FF'],
            borderWidth: 2
        }]
    },
    options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>