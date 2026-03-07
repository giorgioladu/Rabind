<?php
require_once __DIR__ . '/lib/auth.php';
requireAuth();
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/menu.php';
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold"><i class="bi bi-gear-wide-connected text-primary"></i> RADIUS Core Configuration</h2>
            <p class="text-muted">Gestisci i nodi di accesso, le regole di validazione e i profili di risposta del server.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 hover-shadow">
                <div class="card-body text-center p-4">
                    <div class="display-4 text-primary mb-3">
                        <i class="bi bi-router"></i>
                    </div>
                    <h5 class="card-title fw-bold">NAS Management</h5>
                    <p class="card-text text-muted small">Configura i router MikroTik (Network Access Servers) autorizzati a comunicare con il Radius.</p>
                    <a href="nas.php" class="btn btn-outline-primary w-100 mt-2">Gestisci NAS</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 hover-shadow">
                <div class="card-body text-center p-4">
                    <div class="display-4 text-success mb-3">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h5 class="card-title fw-bold">Group Check</h5>
                    <p class="card-text text-muted small">Imposta le condizioni di accesso: scadenze, limiti di dispositivi contemporanei e permessi globali.</p>
                    <a href="radgroupcheck.php" class="btn btn-outline-success w-100 mt-2">Regole di Controllo</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 hover-shadow">
                <div class="card-body text-center p-4">
                    <div class="display-4 text-info mb-3">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <h5 class="card-title fw-bold">Group Reply</h5>
                    <p class="card-text text-muted small">Definisci cosa "riceve" l'utente: limiti di banda (Rate-Limit), traffico totale e parametri DHCP.</p>
                    <a href="radgroupreply.php" class="btn btn-outline-info w-100 mt-2">Parametri Profili</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-12">
            <div class="alert alert-light border-0 shadow-sm d-flex align-items-center">
                <i class="bi bi-info-circle-fill text-primary me-3 fs-4"></i>
                <div>
                    <strong>Consiglio Rapido:</strong> Ricorda che dopo aver aggiunto un nuovo NAS nella sezione dedicata, è consigliabile eseguire il <strong>Reload</strong> dalla pagina stessa per rendere attive le modifiche senza riavviare il servizio.
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-shadow { transition: all 0.3s ease; }
    .hover-shadow:hover { 
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
</style>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
