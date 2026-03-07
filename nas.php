<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/menu.php';

/* =========================
   LOGICA RELOAD (nasreload)
   ========================= */
if (isset($_POST['trigger_reload'])) {
    // Inserisce il timestamp attuale per segnalare il reload
    $stmt = $radiusDb->prepare("INSERT INTO nasreload (nasreloadtime) VALUES (NOW())");
    $stmt->execute();
    $msg = "Segnale di ricaricamento inviato con successo!";
}

/* =========================
   LOGICA DELETE
   ========================= */
if (isset($_GET['delete'])) {
    $stmt = $radiusDb->prepare("DELETE FROM nas WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: nas.php?deleted=1");
    exit;
}

/* =========================
   LOGICA INSERT / UPDATE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['trigger_reload'])) {
    $id          = $_POST['id'] ?? null;
    $nasname     = $_POST['nasname'];     // IP o FQDN
    $shortname   = $_POST['shortname'];   // Nome mnemonico
    $type        = $_POST['type'] ?? 'other';
    $secret      = $_POST['secret'];      // Password Radius
    $description = $_POST['description'] ?? '';

    if ($id) {
        $stmt = $radiusDb->prepare("UPDATE nas SET nasname=?, shortname=?, type=?, secret=?, description=? WHERE id=?");
        $stmt->execute([$nasname, $shortname, $type, $secret, $description, $id]);
    } else {
        $stmt = $radiusDb->prepare("INSERT INTO nas (nasname, shortname, type, secret, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nasname, $shortname, $type, $secret, $description]);
    }
    header("Location: nas.php?success=1");
    exit;
}

/* CARICAMENTO DATI */
$nas_list = $radiusDb->query("SELECT * FROM nas ORDER BY id DESC")->fetchAll();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>📡 Gestione NAS (Network Access Servers)</h4>
        <div class="btn-group">
            <form method="post" class="d-inline">
                <button type="submit" name="trigger_reload" class="btn btn-warning shadow-sm">
                    <i class="bi bi-arrow-clockwise"></i> Forza Reload NAS
                </button>
            </form>
            <button class="btn btn-primary shadow-sm" onclick="openNasEdit({})">
                <i class="bi bi-plus-lg"></i> Aggiungi NAS
            </button>
        </div>
    </div>

    <?php if(isset($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>IP / Hostname</th>
                        <th>Nome Breve</th>
                        <th>Secret</th>
                        <th>Descrizione</th>
                        <th class="text-end">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nas_list as $n): ?>
                    <tr>
                        <td><?= $n['id'] ?></td>
                        <td><strong class="text-primary"><?= htmlspecialchars($n['nasname']) ?></strong></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($n['shortname']) ?></span></td>
                        <td><code><?= htmlspecialchars($n['secret']) ?></code></td>
                        <td class="small text-muted"><?= htmlspecialchars($n['description']) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" onclick='openNasEdit(<?= json_encode($n) ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="?delete=<?= $n['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Rimuovere questo NAS? Il router non potrà più autenticare gli utenti.')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($nas_list)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Nessun NAS configurato.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="nasModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Configurazione NAS</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="nas-id">
        
        <div class="mb-3">
            <label class="form-label fw-bold">NAS IP / Hostname</label>
            <input type="text" name="nasname" id="nas-name" class="form-control" placeholder="es: 192.168.1.1" required>
            <div class="form-text">L'indirizzo IP del router MikroTik.</div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Nome Breve (identificativo)</label>
            <input type="text" name="shortname" id="nas-short" class="form-control" placeholder="es: MK-Sede-Centrale" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Shared Secret</label>
            <div class="input-group">
                <input type="text" name="secret" id="nas-secret" class="form-control" required>
                <button class="btn btn-outline-secondary" type="button" onclick="generateSecret()">Gen</button>
            </div>
            <div class="form-text">Deve coincidere con la password impostata nel menu RADIUS di WinBox.</div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Tipo NAS</label>
            <select name="type" id="nas-type" class="form-select">
                <option value="other">Generic</option>
                <option value="mikrotik">MikroTik</option>
                <option value="cisco">Cisco</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Descrizione</label>
            <textarea name="description" id="nas-desc" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="submit" class="btn btn-primary w-100">Salva Dispositivo</button>
      </div>
    </form>
  </div>
</div>

<script>
function openNasEdit(data) {
    document.getElementById('nas-id').value = data.id || '';
    document.getElementById('nas-name').value = data.nasname || '';
    document.getElementById('nas-short').value = data.shortname || '';
    document.getElementById('nas-secret').value = data.secret || '';
    document.getElementById('nas-type').value = data.type || 'other';
    document.getElementById('nas-desc').value = data.description || '';

    var modal = new bootstrap.Modal(document.getElementById('nasModal'));
    modal.show();
}

function generateSecret() {
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    let secret = "";
    for (let i = 0; i < 12; i++) {
        secret += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('nas-secret').value = secret;
}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
