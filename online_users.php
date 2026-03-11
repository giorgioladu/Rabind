<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/menu.php';

// Carichiamo le sessioni dalla nuova vista
$stmt = $radiusDb->query("SELECT * FROM view_online_detailed ORDER BY acctstarttime DESC");
$online = $stmt->fetchAll();

// Funzione di utilità interna per i bytes (se non è già in un file globale)
function formatBytes($b) {
    if ($b < 1024) return $b . " B";
    if ($b < 1048576) return round($b / 1024, 2) . " KB";
    if ($b < 1073741824) return round($b / 1048576, 2) . " MB";
    return round($b / 1073741824, 2) . " GB";
}
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">🟢 Utenti Online <span class="badge bg-success ms-2"><?= count($online) ?></span></h4>
            <p class="text-muted small">Monitoraggio in tempo reale delle sessioni attive sui NAS.</p>
        </div>
        <div class="d-flex gap-2">
            <input type="text" id="userSearch" class="form-control" placeholder="Cerca utente, IP o MAC...">
           <button class="btn btn-sm btn-outline-primary" onclick="location.reload()" title="Aggiorna lista utenti">
    <i class="bi bi-arrow-clockwise"></i> Aggiorna</button>
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="onlineTable">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th>Utente</th>
                        <th>Indirizzo IP</th>
                        <th>Dispositivo (MAC)</th>
                        <th>Router / NAS</th>
                        <th>Profilo</th>
                        <th>Uptime</th>
                        <th>Traffico Sessione</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($online as $u): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-primary"><?= htmlspecialchars($u['username']) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;">Inizio: <?= $u['acctstarttime'] ?></div>
                        </td>
                        <td><code class="fw-bold"><?= $u['framedipaddress'] ?></code></td>
                        <td><small class="text-muted"><?= $u['callingstationid'] ?></small></td>
                        <td><span class="badge bg-dark"><?= htmlspecialchars($u['nas_name'] ?? 'Unknown') ?></span></td>
                        <td><span class="badge bg-info text-white"><?= htmlspecialchars($u['profile'] ?? 'Default') ?></span></td>
                        <td><i class="bi bi-clock-history me-1"></i><?= $u['uptime'] ?></td>
                        <td>
                            <div class="small">
                                <i class="bi bi-arrow-down text-success"></i> <?= formatBytes($u['session_outputoctets']) ?> <br>
                                <i class="bi bi-arrow-up text-primary"></i> <?= formatBytes($u['session_inputoctets']) ?>
                            </div>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="kickUser('<?= urlencode($u['username']) ?>', '<?= $u['nas_name'] ?>')">
                                <i class="bi bi-lightning-fill"></i> Disconnetti
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($online)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">Nessun utente connesso al momento.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<script>
// Ricerca real-time nella tabella
document.getElementById('userSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#onlineTable tbody tr');
    
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Funzione Kick (Placeholder per la tua logica di disconnessione)
function kickUser(username, nas) {
    if(confirm(`Vuoi forzare la disconnessione di ${decodeURIComponent(username)} dal router ${nas}?`)) {
        window.location.href = `user_disconnect.php?u=${username}`;
    }
}

// Auto-refresh ogni 60 secondi
setTimeout(() => { location.reload(); }, 60000);
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
