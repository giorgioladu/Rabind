<?php
//ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);

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
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="users.php">Utenti</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($username) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">Informazioni Account</div>
                <div class="card-body">
                    <p><strong>Stato:</strong> <span class="badge <?= ($user['type'] == 'active') ? 'bg-success' : 'bg-danger' ?>"><?= strtoupper($user['type']) ?></span></p>
                    <p><strong>Creato il:</strong> <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></p>
                    <p><strong>Note:</strong><span class="text-muted"><?= htmlspecialchars($user['notes'] ?: 'Nessuna nota') ?></span></p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">Parametri Radius (radcheck)</div>
                <ul class="list-group list-group-flush">
                    <?php foreach($attributes as $attr): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <small class="fw-bold"><?= $attr['attribute'] ?></small>
                            <span class="badge bg-light text-dark border"><?= $attr['value'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

			<div class="col-md-8">
				<div class="card shadow-sm border-0">
					<div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
						<span>Ultime Sessioni</span>
						
						<div class="input-group input-group-sm" style="max-width: 250px;">
							<span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
							<input type="text" id="dateSearch" class="form-control" placeholder="Cerca data (es. 06/03)...">
						</div>
					</div>
					
					<div class="table-responsive">
						<table class="table table-sm table-hover mb-0" id="sessionTable">
							<thead class="table-light small">
								<tr>
									<th>Periodo (Inizio / Fine)</th>
									<th>Dispositivo</th>
									<th>Durata</th>
									<th>Traffico</th>
								</tr>
							</thead>
							<tbody class="small">
								<?php foreach($sessions as $s): ?>
								<tr>
									<td class="session-date">
										<strong><?= date('d/m/Y H:i', strtotime($s['acctstarttime'])) ?></strong><br>
										<small class="text-muted">
											<?= $s['acctstoptime'] ? date('d/m/Y H:i', strtotime($s['acctstoptime'])) : '<span class="text-success fw-bold">Attiva</span>' ?>
										</small>
									</td>
									<td>
										<span class="text-primary"><?= $s['framedipaddress'] ?></span><br>
										<small class="text-muted"><?= $s['callingstationid'] ?></small>
									</td>
									<td>
										<?php 
											$min = floor($s['acctsessiontime'] / 60);
											echo $min > 0 ? $min . " min" : $s['acctsessiontime'] . " sec";
										?>
									</td>
									<td>
										<div class="text-success"><i class="bi bi-cloud-arrow-down"></i> <?= round($s['acctoutputoctets'] / 1048576, 2) ?> MB</div>
										<div class="text-info"><i class="bi bi-cloud-arrow-up"></i> <?= round($s['acctinputoctets'] / 1048576, 2) ?> MB</div>
									</td>
								</tr>
								<?php endforeach; ?>
								
								<?php if(empty($sessions)): ?>
									<tr id="noResults"><td colspan="4" class="text-center py-4 text-muted">Nessuna sessione trovata.</td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
   
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('dateSearch');
    const tableRows = document.querySelectorAll('#sessionTable tbody tr:not(#noResults)');
    const noResultsRow = document.getElementById('noResults');

    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        let visibleCount = 0;

        tableRows.forEach(row => {
            // Cerchiamo il testo solo all'interno della cella della data (la prima td)
            const dateCell = row.querySelector('.session-date');
            const textValue = dateCell ? dateCell.textContent || dateCell.innerText : "";

            if (textValue.toLowerCase().indexOf(filter) > -1) {
                row.style.display = "";
                visibleCount++;
            } else {
                row.style.display = "none";
            }
        });

        // Gestione messaggio "Nessun risultato"
        if (noResultsRow) {
            noResultsRow.style.display = (visibleCount === 0) ? "" : "none";
        }
    });
});
</script>
<?php require_once __DIR__ . '/templates/footer.php'; ?>
