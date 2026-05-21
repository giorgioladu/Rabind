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

require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php'; // Assicurati che il percorso sia corretto rispetto alla tua struttura

// Se già loggato → dashboard
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// Gestione del messaggio di timeout se presente nell'URL
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = "Session expired due to inactivity. Please login again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Recupero dell'indirizzo IP del client
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    // Nel caso di IP multipli da proxy, prendiamo il primo
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }

    if ($username === '' || $password === '') {
        $error = "Insert username and password.";
    } else {

        // 1. Controllo Anti Brute Force
        if (isBruteForce($appDb, $username, $ip)) {
            $error = "Too many failed attempts. Please try again in 10 minutes.";
        } else {

            $stmt = $appDb->prepare("SELECT * FROM admins WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $admin = $stmt->fetch();

            // 2. Verifica credenziali
            if ($admin && password_verify($password, $admin['password'])) {

                $_SESSION['admin_logged'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['login_time'] = time();

                if (empty($_SESSION['csrf_token'])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }

                session_regenerate_id(true);

                header("Location: dashboard.php");
                exit;

            } else {
                // 3. Registrazione del fallimento se le credenziali sono errate
                registerLoginAttempt($appDb, $username, $ip);
                $error = "Bad USER or PASSWORD."; // Errore generico per non dare indizi sugli username esistenti
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - RaBind</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center align-items-center" style="height: 100vh;">
        <div class="col-md-4">

            <div class="card shadow-sm">
                <div class="card-body">

                    <h4 class="text-center mb-4"><?php echo htmlspecialchars(APP_NAME); ?></h4>
                    <p class="text-center text-muted small">
                       <?php echo htmlspecialchars(APP_TAGLINE); ?>
                    </p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off">

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($username ?? ''); ?>" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                Login
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>