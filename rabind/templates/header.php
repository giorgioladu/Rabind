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
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
       <!-- Bootstrap and JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="<?= BASE_URL ?>js/user_modal.js"></script>
<?php
/**
 * Formatta i byte in formato leggibile
 */
function formatBytes($bytes) {
    if ($bytes <= 0) return "0 B";
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
?>
<?php
/**
 * GESTIONE FEEDBACK in dashboard.php / online_users.php
 * ─────────────────────────────────────────────────────────────────────
 *
 * Legge il parametro ?success= o ?error= aggiunto dal redirect
 * di user_disconnect.php e mostra un alert Bootstrap.
 */

$feedbackMessages = [
    // successi
    'disconnected'      => ['success', 'Utente disconnesso con successo.'],
    'delete'      => ['success', 'Utente rimosso con successo.'],
    'reset_mac'      => ['success', 'Reset MAC eseguito con successo.'],
    'reset_traffic'      => ['success', 'Reset traffico eseguito con successo.'],
    // errori
    'invalid_user'      => ['danger',  'Username non valido.'],
    'missing_user'      => ['warning',  'Username non valido.'],
    'user_not_found'    => ['danger',  'Utente non trovato nel sistema.'],
    'user_exists'       => ['danger',  'Utente esistente nel sistema.'],
    'no_active_session' => ['warning', 'Nessuna sessione attiva per questo utente.'],
];

$feedbackKey = $_GET['success'] ?? $_GET['error'] ?? null;

if ($feedbackKey && isset($feedbackMessages[$feedbackKey])):
    [$type, $message] = $feedbackMessages[$feedbackKey];
?>
<div class="alert alert-<?= $type ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/menu.php'; ?>


<div class="container mt-4">
