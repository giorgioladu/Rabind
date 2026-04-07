<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth(); // Verifica accesso admin
require_once __DIR__ . '/lib/db.php'; // Connessione DB

// --- 1. GESTIONE FILTRI ---
$userFilter = $_GET['u'] ?? '';
$dateFilter = $_GET['d'] ?? '';

// Costruzione clausole WHERE dinamiche
$whereAuth = "WHERE 1=1";
$whereAcct = "WHERE 1=1";
$paramsAuth = [];
$paramsAcct = [];

if ($userFilter) {
    $whereAuth .= " AND username LIKE ?";
    $whereAcct .= " AND username LIKE ?";
    $paramsAuth[] = "%$userFilter%";
    $paramsAcct[] = "%$userFilter%";
}

if ($dateFilter) {
    $whereAuth .= " AND DATE(authdate) = ?";
    $whereAcct .= " AND DATE(acctstarttime) = ?";
    $paramsAuth[] = $dateFilter;
    $paramsAcct[] = $dateFilter;
}

// --- 2. QUERY CON FILTRI ---

// Login Falliti (Limitati a 100 per leggibilità)
$stmt = $radiusDb->prepare("
    SELECT username, authdate, reply
    FROM radpostauth
    $whereAuth AND reply LIKE '%Reject%'
    ORDER BY authdate DESC
    LIMIT 100
");
$stmt->execute($paramsAuth);
$failed = $stmt->fetchAll();

// Top Traffico (Basato sui filtri)
$stmt = $radiusDb->prepare("
    SELECT username, SUM(acctinputoctets + acctoutputoctets) AS total_traffic
    FROM radacct
    $whereAcct
    GROUP BY username
    ORDER BY total_traffic DESC
    LIMIT 10
");
$stmt->execute($paramsAcct);
$topTraffic = $stmt->fetchAll();

// Top Tentativi (Basato sui filtri)
$stmt = $radiusDb->prepare("
    SELECT username, COUNT(*) AS attempts
    FROM radpostauth
    $whereAuth
    GROUP BY username
    ORDER BY attempts DESC
    LIMIT 10
");
$stmt->execute($paramsAuth);
$topAuth = $stmt->fetchAll();



require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/menu.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>📊 Analisi Logs & Statistiche</h4>
        <a href="logs.php" class="btn btn-sm btn-outline-secondary">Reset Filtri</a>
    </div>

    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="small fw-bold">Cerca Utente</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="u" class="form-control" value="<?= htmlspecialchars($userFilter) ?>" placeholder="Username...">
                    </div>
                </div>
                <div class="col-md-5">
                    <label class="small fw-bold">Filtra per Data</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="date" name="d" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-search"></i> Filtra
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white fw-bold"><i class="bi bi-graph-up-arrow text-success"></i> Top Consumo Traffico</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Username</th><th class="text-end">Totale</th></tr></thead>
                        <tbody>
                            <?php foreach($topTraffic as $t): ?>
                            <tr>
                                <td>
                                    <a href="user_log.php?u=<?= urlencode($t['username']) ?>" class="fw-bold text-decoration-none">
                                        <i class="bi bi-search small"></i> <?= htmlspecialchars($t['username']) ?>
                                    </a>
                                </td>
                                <td class="text-end fw-bold"><?= formatBytes($t['total_traffic']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-white fw-bold"><i class="bi bi-exclamation-triangle text-warning"></i> Top Tentativi (Auth)</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Username</th><th class="text-end">Tentativi</th></tr></thead>
                        <tbody>
                            <?php foreach($topAuth as $a): ?>
                            <tr>
                                <td>
                                    <a href="user_log.php?u=<?= urlencode($a['username']) ?>" class="fw-bold text-decoration-none text-warning">
                                        <i class="bi bi-person-badge"></i> <?= htmlspecialchars($a['username']) ?>
                                    </a>
                                </td>
                                <td class="text-end fw-bold"><?= $a['attempts'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-danger text-white fw-bold">
                    <i class="bi bi-shield-x"></i> Ultimi Login Falliti (Reject)
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr><th>User</th><th>Data/Ora</th><th>Motivo</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($failed as $f): ?>
                            <tr>
                                <td class="fw-bold">
                                    <a href="user_log.php?u=<?= urlencode($f['username']) ?>" class="text-danger text-decoration-none">
                                        <?= htmlspecialchars($f['username']) ?>
                                    </a>
                                </td>
                                <td class="small"><?= $f['authdate'] ?></td>
                                <td><span class="badge bg-light text-danger"><?= htmlspecialchars($f['reply']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($failed)): ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted">Nessun log trovato per i filtri selezionati.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>