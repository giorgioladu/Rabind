<?php require_once __DIR__ . '/../lib/config.php'; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">

    <div class="container">

        <a class="navbar-brand" href="<?= BASE_URL ?>dashboard.php">
            <?php echo APP_NAME; ?>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#rabindMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="rabindMenu">

            <ul class="navbar-nav me-auto">

                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>dashboard.php">Dashboard</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>users.php">Utenti</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>rad_main.php">RadMain</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>logs.php">Log</a>
                </li>

            </ul>

            <ul class="navbar-nav">

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle fw-semibold"
                       href="#"
                       role="button"
                       data-bs-toggle="dropdown">

                        👤 <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?>

                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>admins/index.php">
                                Gestione Admin
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php">
                                Logout
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>

        </div>
    </div>
</nav>
