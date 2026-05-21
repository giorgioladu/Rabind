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
protectWriteOperations();
require_once __DIR__ . '/../lib/db.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Validazione ID
$id = $_POST['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: index.php?error=ID non valido");
    exit;
}

$id = (int) $id;

// Admin corrente
$currentAdminId = $_SESSION['admin_id'] ?? null;

// Blocco self-delete
if ($id === $currentAdminId) {
    header("Location: index.php?error=Non puoi eliminare te stesso");
    exit;
}

// DELETE
$stmt = $appDb->prepare("DELETE FROM admins WHERE id = ?");
$stmt->execute([$id]);

header("Location: index.php?msg=Admin eliminato");
exit;
?>