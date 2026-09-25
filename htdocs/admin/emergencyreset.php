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
require_once __DIR__ . "/../lib/db.php";
require_once __DIR__ . "/../lib/schema.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('FACTORY_RESET') || FACTORY_RESET !== true) {
    header("Location: index.php");
    exit;
}

$message = "";
$messageType = 'info';
$resetCompleted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $message = 'Token CSRF non valido.';
        $messageType = 'danger';
    } elseif (($_POST['confirmation'] ?? '') !== 'RESET') {
        $message = 'Per confermare, inserisci RESET nel campo richiesto.';
        $messageType = 'warning';
    } else {
        try {
            $conn->begin_transaction();
            factory_reset_app_settings($conn);
            $conn->commit();
            $resetCompleted = true;
            $message = 'Impostazioni ripristinate. Effettua nuovamente l\'accesso con le credenziali locali.';
            $messageType = 'success';
            session_unset();
            session_destroy();
            header('Location: login.php?reset=1');
            exit;
        } catch (Throwable $error) {
            if ($conn->in_transaction) {
                $conn->rollback();
            }
            error_log('Errore durante il factory reset: ' . $error->getMessage());
            $message = DEV_MODE
                ? 'Errore durante il reset: ' . $error->getMessage()
                : 'Errore durante il reset alle impostazioni di fabbrica.';
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(app_setting('APP_NAME')); ?> - Reset impostazioni</title>
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
                <?php echo htmlspecialchars(app_setting('APP_NAME')); ?> <?php echo htmlspecialchars(app_setting('YEAR')); ?> - Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-reset" href="../index.php"><i class="bi bi-house"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-reset" href="logout.php?csrf_token=<?php echo generate_csrf_token(); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenuto reset -->
    <main class="container my-5" style="max-width: 760px;">
        <div class="card border-danger shadow-sm">
            <div class="card-header bg-danger text-white fw-bold">
                <i class="bi bi-exclamation-triangle me-1"></i> Reset impostazioni di emergenza
            </div>
            <div class="card-body p-4">
                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                <p>Questa operazione ripristina tutte le preferenze della piattaforma ai valori predefiniti.</p>
                <p class="fw-bold text-danger">Non verranno eliminati gli utenti, le classi, le materie o gli orari.</p>
                <ul>
                    <li>l'autenticazione verrà impostata su <strong>Local</strong>;</li>
                    <li>le credenziali OIDC e il testo dell'annuncio verranno cancellati;</li>
                    <li>la sessione amministrativa verrà chiusa al termine dell'operazione.</li>
                </ul>
                <form method="post" class="mt-4">
                    <?php echo csrf_field(); ?>
                    <label class="form-label fw-bold" for="confirmation">Scrivi RESET per confermare</label>
                    <input class="form-control mb-3" type="text" id="confirmation" name="confirmation" autocomplete="off" required>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="index.php" class="btn btn-outline-secondary">Annulla</a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Ripristina impostazioni
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="text-center text-body-secondary small mt-3 mb-3">
        Copyright &copy; 2025-<?php echo date("Y"); ?> EmmeV. Rilasciato sotto <a href="https://git.vichingo455.com/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" class="fw-bold text-decoration-none">Licenza GNU AGPL 3.0</a>.
        <br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.com/emmev-code/orario" target="_blank" class="fw-bold text-decoration-none">Gitea</a>.
    </footer>
    <script src="../js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
