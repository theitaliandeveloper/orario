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
require_once __DIR__ . "/../lib/db.php";
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
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }

// Recupera le statistiche dal database
$classesCount = 0;
if ($res = $conn->query("SELECT COUNT(*) as cnt FROM classes")) {
    $classesCount = $res->fetch_assoc()['cnt'];
}

$subjectsCount = 0;
if ($res = $conn->query("SELECT COUNT(*) as cnt FROM subjects")) {
    $subjectsCount = $res->fetch_assoc()['cnt'];
}

$timetableCount = 0;
if ($res = $conn->query("SELECT COUNT(*) as cnt FROM timetable")) {
    $timetableCount = $res->fetch_assoc()['cnt'];
}

if ($_SESSION['auth_type'] == 'local') {
    $adminsCount = 0;
    if ($res = $conn->query("SELECT COUNT(*) as cnt FROM admin")) {
        $adminsCount = $res->fetch_assoc()['cnt'];
    }
}

// Info sul server DB
$dbVersion = $conn->server_info;
// Calculate DB size in MB
$dbSizeResult = $conn->query("SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
$dbSizeMB = $dbSizeResult->fetch_assoc()['size_mb'] ?? 0;

// Telemetria PHP
$memoryLimit = ini_get('memory_limit');
$uploadMaxSize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$extensions = get_loaded_extensions();
natcasesort($extensions);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Informazioni sulla piattaforma</title>
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
              <?php echo APP_NAME; ?> <?php echo YEAR; ?> - Admin
              <?php if (DEV_MODE) echo " - SVILUPPO"; ?>
              <?php if (isset($_SESSION['admin']) && MAINTENANCE) echo " - MANUTENZIONE"; ?>
          </a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
              <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
              <ul class="navbar-nav">
                  <li class="nav-item">
                      <a class="nav-link fw-bold text-reset" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link fw-bold text-reset" href="logout.php?csrf_token=<?php echo generate_csrf_token(); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
                  </li>
              </ul>
          </div>
      </div>
  </nav>

<div class="container my-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="fw-bold mb-0"><i class="bi bi-info-circle"></i> Informazioni sulla piattaforma</h1>
        <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
    </div>

    <p class="lead text-secondary text-center mb-4">
        Benvenuto nella pagina informativa di <strong><?php echo APP_NAME; ?></strong>. Qui puoi monitorare lo stato del database e i dettagli dell'ambiente di esecuzione.
    </p>

    <!-- Card Statistiche DB -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body-tertiary fw-bold fs-5">
            <i class="bi bi-database me-1 text-primary"></i> Statistiche del Database
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table">
                        <tr>
                            <th style="width: 25%;">Elemento</th>
                            <th style="width: 20%;" class="text-center">Conteggio Attuale</th>
                            <th>Descrizione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold"><i class="bi bi-building me-2 text-primary"></i>Classi</td>
                            <td class="text-center"><span class="badge bg-primary fs-6"><?php echo $classesCount; ?></span></td>
                            <td>Classi scolastiche registrate per le quali è possibile definire l'orario.</td>
                        </tr>
                        <tr>
                            <td class="fw-bold"><i class="bi bi-book me-2 text-primary"></i>Docenti / Materie</td>
                            <td class="text-center"><span class="badge bg-primary fs-6"><?php echo $subjectsCount; ?></span></td>
                            <td>Accoppiamenti di docenti, materie e relativi laboratori inseriti.</td>
                        </tr>
                        <tr>
                            <td class="fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Ore Programmate</td>
                            <td class="text-center"><span class="badge bg-primary fs-6"><?php echo $timetableCount; ?></span></td>
                            <td>Totale delle ore settimanali pianificate e salvate nell'orario generale.</td>
                        </tr>
                        <?php if ($_SESSION['auth_type'] == 'local'): ?>
                        <tr>
                            <td class="fw-bold"><i class="bi bi-people me-2 text-primary"></i>Utenti Admin</td>
                            <td class="text-center"><span class="badge bg-primary fs-6"><?php echo $adminsCount; ?></span></td>
                            <td>Utenti abilitati ad accedere alla dashboard di gestione di questa istanza.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Card Dettagli Ambiente -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body-tertiary fw-bold fs-5">
            <i class="bi bi-cpu me-1 text-primary"></i> Dettagli Ambiente e Server
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table">
                        <tr>
                            <th style="width: 35%;">Parametro</th>
                            <th>Valore Rilevato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-arrow-repeat"></i> Versione Piattaforma</td>
                            <td>
                                <?php if (VERSION == "dev"): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-code-slash"></i> Sviluppo</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars(VERSION); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-tools"></i> Modalità Manutenzione</td>
                            <td>
                                <?php if (MAINTENANCE): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill"></i> Attivata (in manutenzione)</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Disattivata (normale)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-terminal"></i> Versione di PHP</td>
                            <td><code>PHP <?php echo PHP_VERSION; ?><?php if (PHP_DEBUG){echo " (Debug)";} ?></code></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-hdd-stack"></i> Sistema Operativo</td>
                            <td><small class="text-body-secondary"><?php echo php_uname(); ?></small></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-database"></i> Versione Database</td>
                            <td><code><?php echo htmlspecialchars($dbVersion); ?></code></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-device-hdd"></i> Dimensione Database</td>
                            <td><span class="badge bg-secondary"><?php echo round($dbSizeMB, 2); ?> MB</span></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-memory"></i> Limite Memoria PHP</td>
                            <td><code><?php echo htmlspecialchars($memoryLimit ?: 'N/D'); ?></code></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-hourglass-split"></i> Timeout sessione</td>
                            <td><code><?php echo SESSION_LIFETIME; ?> secondi</code></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold"><i class="bi bi-terminal-plus"></i> Estensioni attive</td>
                            <td><small class="text-body-secondary" style="font-size: 0.85em; line-height: 1.4;"><?php echo htmlspecialchars(implode(', ', $extensions)); ?></small></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Card Licenza -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-body-tertiary fw-bold fs-5">
            <i class="bi bi-patch-check me-1 text-primary"></i> Licenza e Progetto
        </div>
        <div class="card-body p-4">
            <p class="mb-0 text-body-secondary">
                Questa piattaforma è rilasciata sotto i termini della licenza <strong>GNU Affero General Public License versione 3.0 (AGPL-3.0)</strong>. 
                Ciò significa che puoi liberamente studiare, modificare e distribuire il codice sorgente, a patto che ogni modifica apportata 
                venga resa pubblica e condivisa sotto la medesima licenza qualora il servizio sia reso disponibile in rete.
            </p>
        </div>
    </div>
</div>

<footer class="text-center text-body-secondary small mt-5 mb-3">
    Copyright &copy; 2025-<?php echo date("Y"); ?> EmmeV. Rilasciato sotto
    <a href="https://git.vichingo455.com/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" class="fw-bold text-decoration-none">Licenza GNU AGPL 3.0</a>.
    <br>
    Codice sorgente disponibile su <a href="https://git.vichingo455.com/emmev-code/orario" target="_blank" class="fw-bold text-decoration-none">Gitea</a>.
</footer>
<script src="../js/theme.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>

