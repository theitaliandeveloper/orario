<?php
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

$message = null;
$messageType = 'info';
$legacyDetected = legacy_schema_detected($conn);
$normalizedDetected = normalized_schema_detected($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $message = 'Token CSRF non valido.';
        $messageType = 'danger';
    } elseif (!$legacyDetected || $normalizedDetected) {
        $message = 'Lo schema non è in uno stato migrabile. Aggiorna la pagina e verifica il database.';
        $messageType = 'danger';
    } else {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $legacyNames = [
            'classes' => 'classes_legacy',
            'subjects' => 'subjects_legacy',
            'timetable' => 'timetable_legacy'
        ];

        try {
            foreach ($legacyNames as $current => $backup) {
                if (schema_table_exists($conn, $backup)) {
                    throw new RuntimeException("La tabella {$backup} esiste già.");
                }
            }

            $conn->query('RENAME TABLE classes TO classes_legacy, subjects TO subjects_legacy, timetable TO timetable_legacy');

            $schemaPath = __DIR__ . '/../../schema.sql';
            $migrationPath = __DIR__ . '/../../migration_legacy_to_new.sql';
            if (!is_readable($schemaPath) || !is_readable($migrationPath)) {
                throw new RuntimeException('File SQL di migrazione non disponibili.');
            }

            $schemaSql = file_get_contents($schemaPath);
            $schemaSql = preg_replace('/CREATE DATABASE IF NOT EXISTS school_timetable.*?;\s*/is', '', $schemaSql);
            $schemaSql = preg_replace('/USE school_timetable\s*;\s*/i', '', $schemaSql);
            if ($schemaSql === null || !$conn->multi_query($schemaSql)) {
                throw new RuntimeException('Impossibile creare il nuovo schema.');
            }
            while ($conn->more_results() && $conn->next_result()) {
            }

            $migrationSql = file_get_contents($migrationPath);
            if ($migrationSql === false || !$conn->multi_query($migrationSql)) {
                throw new RuntimeException('Impossibile migrare i dati legacy.');
            }
            while ($conn->more_results() && $conn->next_result()) {
            }

            $legacyDetected = false;
            $normalizedDetected = normalized_schema_detected($conn);
            $message = 'Migrazione completata. Le tabelle legacy sono state conservate come backup.';
            $messageType = 'success';
        } catch (Throwable $error) {
            $message = 'Migrazione non completata: ' . $error->getMessage() . '. Le tabelle legacy sono state conservate con il suffisso _legacy.';
            $messageType = 'danger';
        }
    }
}

if ($normalizedDetected && $message === null) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(APP_NAME); ?> - Migrazione database</title>
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
            <li class="nav-item">
                <a class="nav-link fw-bold text-reset" href="logout.php?csrf_token=<?php echo generate_csrf_token(); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </li>
        </ul>
    </div>
</nav>

<main class="container my-5" style="max-width: 760px;">
    <div class="card border-warning shadow-sm">
        <div class="card-header bg-warning-subtle fw-bold">
            <i class="bi bi-database-exclamation me-1"></i> Aggiornamento database richiesto
        </div>
        <div class="card-body p-4">
            <?php if ($message !== null): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($legacyDetected): ?>
                <h1 class="h3 fw-bold">Schema legacy rilevato</h1>
                <p>Il database utilizza ancora la struttura precedente. È necessario aggiornarlo prima di usare la piattaforma.</p>
                <ul>
                    <li>La migrazione usa il database configurato attualmente.</li>
                    <li>Non viene creato un nuovo database.</li>
                    <li>Le tabelle originali vengono conservate come <code>*_legacy</code>.</li>
                    <li>Durante la migrazione il sito deve rimanere non utilizzato dagli altri amministratori.</li>
                </ul>

                <p><strong>Attenzione:</strong> la migrazione non può essere annullata. Assicurati di avere un backup del database prima di procedere.</p>

                <form method="post" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="bi bi-arrow-up-circle me-1"></i> Avvia migrazione
                    </button>
                </form>
            <?php elseif ($normalizedDetected): ?>
                <h1 class="h3 fw-bold">Database già aggiornato</h1>
                <p>Il nuovo schema è già attivo. Puoi tornare alla dashboard.</p>
                <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
            <?php else: ?>
                <h1 class="h3 fw-bold">Schema non riconosciuto</h1>
                <p>Non è stato rilevato né lo schema legacy né quello nuovo. Controlla la configurazione del database.</p>
                <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
