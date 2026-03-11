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

// 2. Preparazione dati per i grafici
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

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold">Online Now</h6>
                    <h2 class="mb-0"><?= count($onlineUsers) ?></h2>
                    <i class="bi bi-people position-absolute top-50 end-0 translate-middle-y me-3 opacity-50 fs-1"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold">Logins (24h)</h6>
                    <h2 class="mb-0"><?= end($successData) ?></h2>
                    <i class="bi bi-check-circle position-absolute top-50 end-0 translate-middle-y me-3 opacity-50 fs-1"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold">Rejects (24h)</h6>
                    <h2 class="mb-0"><?= end($failData) ?></h2>
                    <i class="bi bi-x-circle position-absolute top-50 end-0 translate-middle-y me-3 opacity-50 fs-1"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-dark text-white shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-uppercase small fw-bold">Total Traffic (Today)</h6>
                    <h2 class="mb-0"><?= round(end($downData) + end($upData), 2) ?> <small>MB</small></h2>
                    <i class="bi bi-hdd-network position-absolute top-50 end-0 translate-middle-y me-3 opacity-50 fs-1"></i>
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
                    <span>Utenti Attualmente Online</span>
                    <a href="online_users.php" class="btn btn-sm btn-outline-primary">Vedi Tutti</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th>Username</th>
                                <th>IP Address</th>
                                <th>MAC / Device</th>
                                <th>NAS (Router)</th>
                                <th>Durata</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach(array_slice($onlineUsers, 0, 8) as $u): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($u['username']) ?></td>
                                <td><span class="text-primary"><?= $u['framedipaddress'] ?></span></td>
                                <td><code><?= $u['callingstationid'] ?></code></td>
                                <td><?= $u['nasipaddress'] ?></td>
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

<script>
// Grafico Traffico (Stacked Bar)
new Chart(document.getElementById('trafficChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            { label: 'Download MB', data: <?= json_encode($downData) ?>, backgroundColor: '#4e73df' },
            { label: 'Upload MB', data: <?= json_encode($upData) ?>, backgroundColor: '#1cc88a' }
        ]
    },
    options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true } } }
});

// Grafico Profili (Doughnut)
new Chart(document.getElementById('profileChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($groupLabels) ?>,
        datasets: [{
            data: <?= json_encode($groupCounts) ?>,
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
        }]
    },
    options: { cutout: '70%' }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
