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
requireAuth();

require_once __DIR__ . '/../lib/db.php';

require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../templates/menu.php';

// Messaggi errore
$error = $_GET['error'] ?? null;
?>

<div class="container">
    <h2>Nuovo Amministratore</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="save.php">

        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-group">
            <label>Username</label>
            <input type="text"
                   name="username"
                   class="form-control"
                   required
                   maxlength="50">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password"
                   name="password"
                   class="form-control"
                   required>
        </div>

        <br>

        <button type="submit" class="btn btn-primary">Salva</button>
        <a href="index.php" class="btn btn-secondary">Annulla</a>
    </form>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>