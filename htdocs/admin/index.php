<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see https://www.gnu.org/licenses/.
*/
require __DIR__ . "/../lib/variables.php";
require_once __DIR__ . "/../lib/csrf.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$now = time();
if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) { // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
$_SESSION['discard_after'] = $now + SESSION_LIFETIME; // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="../css/fonts.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>
  <!-- Navbar -->
   <nav class="navbar navbar-expand-md bg-primary shadow-sm rounded-bottom mb-4 px-3 text-light">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-reset" href="index.php">
                <i class="bi bi-clock"></i>&nbsp;
                <?php echo APP_NAME; ?> <?php echo YEAR; ?>
                <?php if (DEV_MODE) echo " - SVILUPPO"; ?>
                <?php if (isset($_SESSION['admin']) && MAINTENANCE) echo " - MANUTENZIONE"; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-reset" href="../index.php">Torna al sito</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-reset" href="logout.php?csrf_token=<?php echo generate_csrf_token(); ?>">Logout</a>
                    </li>
                </ul>
            </div>

        </div>
    </nav>
    <!-- Contenuto Dashboard -->
    <div class="text-center">
        <h1 class="fw-bold mb-5">Benvenuto, <?php echo htmlspecialchars($_SESSION['admin']); ?>!</h1>
        <div class="mb-3">
            <a href="classes.php" class="btn btn-primary">Gestisci Classi</a>
            <a href="subjects.php" class="btn btn-primary">Gestisci Materie</a>
            <a href="timetable.php" class="btn btn-primary">Gestisci Orario</a>
        </div>
        <div class="mb-3">
            <?php
                if (defined(API_URL) || API_URL != "") {
                    echo '<a href="importer.php" class="btn btn-warning">Importa Orario</a>';
                }
            ?>
            <?php
                if ($_SESSION['auth_type'] === 'local') {
                    echo '<a href="password.php" class="btn btn-primary">Cambia Password</a>';
                }
            ?>
            <?php
                if ($_SESSION['auth_type'] === 'local' && $_SESSION['admin'] === 'admin') {
                    echo '<a href="users.php" class="btn btn-primary">Gestisci Utenti</a>';
                }
            ?>
        </div>
        <div class="mb-3">
            <a href="about.php" class="btn btn-info">Informazioni sulla piattaforma</a>
        </div>
    </div>
    <!-- Footer -->
    <footer class="text-center text-body-secondary small mt-5 mb-3">
        Copyright &copy; 2025-<?php echo date("Y"); ?>
        EmmeV. Rilasciato sotto
        <a href="https://git.vichingo455.com/emmev-code/orario/src/branch/stable/LICENSE.txt"
            target="_blank"
            class="fw-bold text-decoration-none">
            Licenza GNU AGPL 3.0
        </a>.
        <br>
        Codice sorgente disponibile su
        <a href="https://git.vichingo455.com/emmev-code/orario"
            target="_blank"
            class="fw-bold text-decoration-none">
            Gitea
        </a>.
    </footer>
    <script src="../js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
