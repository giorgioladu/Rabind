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

require_once __DIR__ . '/../lib/auth.php';
requireAuth(); // Blocca gli utenti non autenticati

require_once __DIR__ . '/../lib/db.php';

// Messaggi di feedback
$message = $_GET['msg'] ?? null;
$error   = $_GET['error'] ?? null;

// --- GESTIONE ELIMINAZIONE LOG VECCHI (PULIZIA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_purge'])) {
    // Sfrutta la funzione nativa di RaBind per verificare il token CSRF del form
    protectWriteOperations();

    $days = filter_var($_POST['purge_days'] ?? null, FILTER_VALIDATE_INT);

    if ($days === false || $days < 0) {
        header("Location: audit.php?error=" . urlencode("Intervallo di giorni non valido."));
        exit;
    }

    try {
        // Elimina i record antecedenti ai giorni selezionati
        $stmt = $appDb->prepare("DELETE FROM audit_log WHERE created_at < NOW() - INTERVAL ? DAY");
        $stmt->execute([$days]);
        $count = $stmt->rowCount();

        header("Location: audit.php?msg=" . urlencode("Pulizia completata! Eliminati $count log più vecchi di $days giorni."));
        exit;
    } catch (PDOException $e) {
        header("Location: audit.php?error=" . urlencode("Errore durante la pulizia: " . $e->getMessage()));
        exit;
    }
}

// --- RECUPERO AUDIT LOG ---
// Limitato agli ultimi 100 eventi per garantire performance fulminee
$stmt = $appDb->query("SELECT * FROM audit_log ORDER BY id DESC LIMIT 100");
$logs = $stmt->fetchAll();

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/menu.php';
?>

<div class="container-fluid px-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Registro delle Operazioni (Audit Log)</h2>

        <button class="btn btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#purgeBox">
            ⚙️ Ripulisci Registro
        </button>
    </div>
        <div class="card mb-4 shadow-sm">
        <div class="card-body bg-light">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-white">🔍</span>
                        <input type="text" id="auditSearchInput" class="form-control" placeholder="Cerca per Amministratore, Operazione (INSERT/UPDATE/DELETE), Tabella o contenuto del Payload...">
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <span class="text-muted small" id="searchCounter">Mostrati: 100 di 100 eventi</span>
                </div>
            </div>
        </div>
        </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="collapse mb-4" id="purgeBox">
        <div class="card card-body border-danger bg-light">
            <h5 class="text-danger">Manutenzione e Conservazione Log</h5>
            <p class="small text-muted">L'eliminazione dei log è un'azione definitiva. Seleziona l'anzianità dei record che desideri rimuovere:</p>

            <form method="post" class="row g-3 align-items-center" onsubmit="return confirm('Sei sicuro di voler eliminare definitivamente i log selezionati?');">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action_purge" value="1">

                <div class="col-auto">
                    <select name="purge_days" class="form-select" required>
                        <option value="7">Elimina log più vecchi di 7 giorni (1 settimana)</option>
                        <option value="30" selected>Elimina log più vecchi di 30 giorni (1 mese)</option>
                        <option value="90">Elimina log più vecchi di 90 giorni (3 mesi)</option>
                        <option value="0">Svuota interamente la tabella (Azzera tutto)</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-danger">Applica Epurazione</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span class="mb-0">Ultimi 100 movimenti registrati</span>
            <span class="badge bg-secondary">Tabella: audit_log</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr>
                        <th width="70">ID</th>
                        <th width="160">Data / Ora</th>
                        <th width="150">Amministratore</th>
                        <th width="140">IP Client</th>
                        <th width="110">Operazione</th>
                        <th width="150">Tabella Target</th>
                        <th>Dati Modificati (Payload)</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Nessuna operazione registrata nel sistema.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log):
                        // Badge colorati a seconda dell'azione SQL eseguita
                        $badgeClass = 'bg-secondary';
                        if ($log['action_type'] === 'INSERT') $badgeClass = 'bg-success';
                        if ($log['action_type'] === 'UPDATE') $badgeClass = 'bg-primary';
                        if ($log['action_type'] === 'DELETE') $badgeClass = 'bg-danger';

                        // Formattazione del JSON in modalità leggibile (Pretty Print)
                        $decodedPayload = json_decode($log['payload'], true);
                        $prettyPayload = ($decodedPayload)
                            ? json_encode($decodedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                            : htmlspecialchars($log['payload'] ?? '');
                    ?>
                        <tr>
                            <td><strong>#<?= $log['id'] ?></strong></td>
                            <td class="text-nowrap"><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
                            <td>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($log['admin_username'] ?? '') ?></span>
                                <?php if($log['admin_id']): ?>
                                    <small class="text-muted d-block">UID: <?= (int)$log['admin_id'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap"><code><?= htmlspecialchars($log['ip'] ?? '') ?></code></td>
                            <td>
                                <span class="badge <?= $badgeClass ?> d-block py-2"><?= $log['action_type'] ?></span>
                            </td>
                            <td><span class="font-monospace text-secondary">`<?= htmlspecialchars($log['target_table'] ?? '') ?>`</span></td>
                            <td>
                                <?php if ($prettyPayload && $prettyPayload !== '[]' && $prettyPayload !== '{}'): ?>
                                    <pre class="bg-light p-2 rounded border mb-0 text-dark small" style="max-height: 150px; overflow-y: auto; font-family: monospace; white-space: pre-wrap;"><code class="text-break"><?= htmlspecialchars($prettyPayload ?? '') ?></code></pre>
                                <?php else: ?>
                                    <span class="text-muted small"><em>Nessun parametro (Query diretta o vuota)</em></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('auditSearchInput');
    const tableRows = document.querySelectorAll('tbody tr');
    const counterSpan = document.getElementById('searchCounter');

    // Se la tabella è vuota (es. nessun log presente), usciamo per evitare errori
    if (tableRows.length === 1 && tableRows[0].querySelector('td').getAttribute('colspan')) {
        counterSpan.textContent = "Nessun evento da cercare";
        return;
    }

    const totalRows = tableRows.length;

    searchInput.addEventListener('input', function () {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        tableRows.forEach(row => {
            // Recuperiamo i testi dei campi specifici per la ricerca
            const admin = row.cells[2] ? row.cells[2].textContent.toLowerCase() : '';
            const action = row.cells[4] ? row.cells[4].textContent.toLowerCase() : '';
            const tableTarget = row.cells[5] ? row.cells[5].textContent.toLowerCase() : '';
            const payload = row.cells[6] ? row.cells[6].textContent.toLowerCase() : '';

            // Verifica se il termine cercato è presente in uno dei campi
            if (
                admin.includes(searchTerm) ||
                action.includes(searchTerm) ||
                tableTarget.includes(searchTerm) ||
                payload.includes(searchTerm)
            ) {
                row.style.display = ''; // Mostra la riga
                visibleCount++;
            } else {
                row.style.display = 'none'; // Nascondi la riga
            }
        });

        // Aggiorna il contatore dinamico in tempo reale
        counterSpan.textContent = `Mostrati: ${visibleCount} di ${totalRows} eventi`;
    });
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>