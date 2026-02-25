<?php require_once __DIR__ . '/../lib/config.php'; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">

    <div class="container">

        <a class="navbar-brand" href="/dashboard.php">
            <?php echo APP_NAME; ?>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#rabindMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="rabindMenu">

            <ul class="navbar-nav me-auto">

                <li class="nav-item">
                    <a class="nav-link" href="/dashboard.php">Dashboard</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/users.php">Utenti</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/logs.php">Log</a>
                </li>

            </ul>

            <ul class="navbar-nav">

                <li class="nav-item">
                    <span class="nav-link">
                        <?php echo $_SESSION['admin_username'] ?? ''; ?>
                    </span>
                </li>

                <li class="nav-item">
                    <a class="nav-link text-danger" href="/logout.php">
                        Logout
                    </a>
                </li>

            </ul>

        </div>
    </div>
</nav>
