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
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/schema.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = 'info';
$legacyDetected = legacy_schema_detected($conn);
$normalizedDetected = normalized_schema_detected($conn);
$schemaVersion = get_schema_version($conn);
$versionNeedsUpdate = schema_update_required($conn);
$versionSupported = schema_version_supported($conn);
$backto = $_GET['backto'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $message = 'Token CSRF non valido.';
        $messageType = 'danger';
    } else if (!$versionNeedsUpdate) {
        $message = 'Nessuna migrazione disponibile per lo stato/versione attuale del database.';
        $messageType = 'warning';
    } else {
        try {
            if ($schemaVersion < 1) {
                $result = migrate_v1(
                    $conn,
                    dirname(__DIR__, 2),
                    static function (string $step): void {
                    }
                );
                $legacyDetected = false;
                $normalizedDetected = true;
                $schemaVersion = (int)$result['version'];
                $versionNeedsUpdate = false;
                $versionSupported = true;
                $message .= 'Migrazione alla v' . $schemaVersion . ' completata. Le tabelle legacy sono state conservate come backup.';
                $messageType = 'success';
            } else {
                throw new RuntimeException('Nessuna migrazione disponibile per lo stato/versione attuale del database.');
            }
        } catch (Throwable $error) {
            $message = 'Migrazione alla v' . CURRENT_SCHEMA_VERSION . ' non completata: ' . $error->getMessage();
            $messageType = 'danger';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(APP_NAME); ?> - Aggiornamento database</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/fonts.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>
<nav class="navbar navbar-expand-md bg-primary mb-4 px-3 text-light">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold text-reset"><i class="bi bi-clock"></i>&nbsp;<?php echo htmlspecialchars(APP_NAME); ?> - Admin</span>
        <ul class="navbar-nav ms-auto">
            <?php if (!$versionNeedsUpdate): ?>
                <li class="nav-item">
                    <a class="nav-link fw-bold text-reset" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link fw-bold text-reset" href="logout.php?csrf_token=<?php echo generate_csrf_token(); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </li>
        </ul>
    </div>
</nav>

<main class="container my-5" style="max-width: 760px;">
    <div class="card border-warning shadow-sm">
        <div class="card-header bg-warning-subtle fw-bold">
            <i class="bi bi-database-exclamation me-1"></i> Aggiornamento database
        </div>
        <div class="card-body p-4">
            <?php if ($message !== null && $message !== ''): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($versionNeedsUpdate): ?>
                <h1 class="h3 fw-bold">Aggiornamento database necessario!</h1>

                <p>Il database utilizza ancora la struttura precedente, per cui è necessario un aggiornamento per poter utilizzare la versione corrente della piattaforma.</p>
                <p>Nel database verranno applicati i seguenti aggiornamenti:</p>
                <ul>
                <?php
                for ($v = $schemaVersion === null ? 0 : $schemaVersion; $v < CURRENT_SCHEMA_VERSION; $v++) {
                    if ($schemaVersion === null || $v > $schemaVersion) {
                        echo '<li>' . htmlspecialchars(update_ops($v+1)) . '</li>';
                    }
                }
                ?>
                </ul>
                <p>Assicurati di avere un backup del database prima di procedere. Una volta iniziata l'aggiornamento, non sarà possibile annullare l'operazione.</p>
                <p class="mb-2"><strong>Versione rilevata:</strong> <?php echo $schemaVersion === null ? '0' : $schemaVersion; ?></p>
                <p class="mb-4"><strong>Versione richiesta:</strong> <?php echo CURRENT_SCHEMA_VERSION; ?></p>
                <form method="post" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="bi bi-arrow-repeat me-1"></i> Avvia aggiornamento
                    </button>
                </form>
            <?php elseif ($schemaVersion === CURRENT_SCHEMA_VERSION): ?>
                <h1 class="h3 fw-bold">Database aggiornato</h1>
                <p>Il database utilizza una versione della struttura supportata.</p>
                <details class="border rounded p-3 mb-4">
                    <summary class="fw-semibold">Changelog struttura database</summary>
                    <div class="mt-3">
                    <?php foreach (schema_changelog() as $version => $change): ?>
                        <?php if ($version <= $schemaVersion): ?>
                            <div class="mb-3">
                                <div class="fw-semibold">Versione <?php echo (int)$version; ?> - <?php echo htmlspecialchars($change['title']); ?></div>
                                <div class="text-body-secondary small"><?php echo htmlspecialchars($change['description']); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </div>
                </details>
                <?php if ($backto): ?>
                    <a href="<?php echo htmlspecialchars($backto); ?>" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna indietro</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <h1 class="h3 fw-bold">Aggiornamento non disponibile</h1>
                <p>Lo stato del database non corrisponde a una migrazione eseguibile.</p>
                <?php if ($backto): ?>
                    <a href="<?php echo htmlspecialchars($backto); ?>" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna indietro</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

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
</body>
</html>
