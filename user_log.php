<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/menu.php';

$username = $_GET['u'] ?? null;
if (!$username) { header("Location: users.php"); exit; }

// 1. Dati Anagrafici (App DB)
$stmt = $appDb->prepare("SELECT * FROM rabind_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

// 2. Configurazione Tecnica (Radius radcheck)
$stmt = $radiusDb->prepare("SELECT attribute, value FROM radcheck WHERE username = ?");
$stmt->execute([$username]);
$attributes = $stmt->fetchAll();

// 3. Storico Sessioni (Radius radacct)
$stmt = $radiusDb->prepare("
    SELECT acctstarttime, acctstoptime, framedipaddress, callingstationid,
           acctsessiontime, acctinputoctets, acctoutputoctets, acctterminatecause
    FROM radacct
    WHERE username = ?
    ORDER BY acctstarttime DESC LIMIT 100
");
$stmt->execute([$username]);
$sessions = $stmt->fetchAll();

// 4. Login Falliti (Corretto senza callingstationid)
$stmt = $radiusDb->prepare("
    SELECT authdate, reply
    FROM radpostauth
    WHERE username = ? AND reply = 'Access-Reject'
    ORDER BY authdate DESC LIMIT 50
");
$stmt->execute([$username]);
$failedLogins = $stmt->fetchAll();

// Calcolo statistiche rapide
$totalSessions = count($sessions);
$totalTraffic = 0;
foreach($sessions as $s) { $totalTraffic += ($s['acctinputoctets'] + $s['acctoutputoctets']); }
?>

<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Dettaglio Utente: <span class="text-primary"><?= htmlspecialchars($username) ?></span></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0" style="font-size: 0.85rem;">
                    <li class="breadcrumb-item"><a href="users.php">Utenti</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($username) ?></li>
                </ol>
            </nav>
        </div>
        <div class="btn-group">
            <a href="export_user_sessions.php?u=<?= urlencode($username) ?>" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-spreadsheet"></i> Esporta CSV
            </a>
            <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <div class="card bg-light border-0 shadow-sm p-2 text-center">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Stato Account</small>
                <div class="fw-bold <?= ($user['type'] == 'active') ? 'text-success' : 'text-danger' ?>">
                    <?= strtoupper($user['type']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light border-0 shadow-sm p-2 text-center">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Sessioni (Recenti)</small>
                <div class="fw-bold"><?= $totalSessions ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light border-0 shadow-sm p-2 text-center">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Traffico Totale</small>
                <div class="fw-bold text-primary"><?= round($totalTraffic / 1048576, 2) ?> MB</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light border-0 shadow-sm p-2 text-center">
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Errori Login</small>
                <div class="fw-bold text-danger"><?= count($failedLogins) ?></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs border-bottom-0" id="userTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold" id="sessions-tab" data-bs-toggle="tab" data-bs-target="#sessions" type="button">
                        <i class="bi bi-clock-history"></i> Sessioni
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-danger" id="failed-tab" data-bs-toggle="tab" data-bs-target="#failed" type="button">
                        <i class="bi bi-shield-exclamation"></i> Login Falliti
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="config-tab" data-bs-toggle="tab" data-bs-target="#config" type="button">
                        <i class="bi bi-gear"></i> Configurazione
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="userTabContent">

                <div class="tab-pane fade show active" id="sessions" role="tabpanel">
                    <div class="p-2 border-bottom bg-light d-flex justify-content-between">
                        <small class="text-muted">Ultime 100 connessioni registrate</small>
                        <input type="text" id="dateSearch" class="form-control form-control-sm" style="max-width: 200px;" placeholder="Cerca data...">
                    </div>
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0" id="sessionTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Inizio / Fine</th>
                                    <th>Dati Dispositivo</th>
                                    <th>Durata</th>
                                    <th>Traffico (DL/UL)</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php foreach($sessions as $s): ?>
                                <tr>
                                    <td class="session-date">
                                        <span class="fw-bold text-dark"><?= date('d/m/y H:i', strtotime($s['acctstarttime'])) ?></span><br>
                                        <small><?= $s['acctstoptime'] ? date('d/m/y H:i', strtotime($s['acctstoptime'])) : '<span class="badge bg-success">Attiva</span>' ?></small>
                                    </td>
                                    <td>
                                        <span class="text-primary fw-bold"><?= $s['framedipaddress'] ?></span><br>
                                        <span class="text-muted" style="font-size: 0.75rem;"><?= $s['callingstationid'] ?></span>
                                    </td>
                                    <td><?= floor($s['acctsessiontime'] / 60) ?> min</td>
                                    <td>
                                        <div class="text-success"><i class="bi bi-arrow-down-circle"></i> <?= round($s['acctoutputoctets'] / 1048576, 2) ?> MB</div>
                                        <div class="text-info"><i class="bi bi-arrow-up-circle"></i> <?= round($s['acctinputoctets'] / 1048576, 2) ?> MB</div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="failed" role="tabpanel">
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr><th>Data e Ora</th><th>Risposta Radius</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($failedLogins as $f): ?>
                                <tr class="table-danger border-white">
                                    <td class="small fw-bold"><?= date('d/m/Y H:i:s', strtotime($f['authdate'])) ?></td>
                                    <td><span class="badge bg-danger"><?= htmlspecialchars($f['reply']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($failedLogins)): ?>
                                    <tr><td colspan="2" class="text-center py-5 text-muted">Nessun tentativo fallito registrato.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="config" role="tabpanel">
                    <div class="row g-0">
                        <div class="col-md-6 border-end p-3">
                            <h6><i class="bi bi-info-circle text-primary"></i> Note e Anagrafica</h6>
                            <p class="small text-muted mb-1">Creato il: <?= $user['created_at'] ?></p>
                            <div class="bg-light p-2 rounded border small">
                                <?= nl2br(htmlspecialchars($user['notes'] ?: 'Nessuna nota presente.')) ?>
                            </div>
                        </div>
                        <div class="col-md-6 p-3">
                            <h6><i class="bi bi-cpu text-dark"></i> Parametri Tecnici (Radius)</h6>
                            <table class="table table-sm table-bordered small">
                                <?php foreach($attributes as $attr): ?>
                                <tr>
                                    <td class="bg-light fw-bold"><?= $attr['attribute'] ?></td>
                                    <td><code><?= $attr['value'] ?></code></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtro rapido per data
    const searchInput = document.getElementById('dateSearch');
    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#sessionTable tbody tr');
        rows.forEach(row => {
            const dateCell = row.querySelector('.session-date');
            row.style.display = dateCell.textContent.toLowerCase().includes(filter) ? "" : "none";
        });
    });
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>