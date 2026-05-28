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
include("../lib/db.php");
session_start();
$now = time();
if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) { // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
    session_unset();
    session_destroy();
    session_start();
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
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../favicon.svg" type="image/svg+xml">
</head>
<body>
  <!-- Navbar -->
  <div class="navbar">
    <div class="logo"><?php echo APP_NAME; ?> - Admin Dashboard<?php if (DEV_MODE){echo " - SVILUPPO";}?></div>
    <div class="links">
      <a href="index.php">Dashboard</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>
  <div class="admin-container">
    <h1>Informazioni sulla piattaforma</h1>
    <a href="index.php" class="back-link">⬅ Torna al Dashboard</a>

    <p style="font-size: 1.1em; color: #555; text-align: center; margin-bottom: 25px;">
        Benvenuto nella pagina informativa di <strong><?php echo APP_NAME; ?></strong>. Qui puoi monitorare lo stato del database e i dettagli dell'ambiente di esecuzione.
    </p>

    <h3 style="color: #2c3e50; border-left: 5px solid #1f618d; padding-left: 10px; margin-top: 30px; margin-bottom: 15px;">
        Statistiche del Database
    </h3>
    <div class="table-container">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th style="text-align: left;">Elemento</th>
                    <th style="text-align: left; width: 20%;">Conteggio Attuale</th>
                    <th style="text-align: left;">Descrizione</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td data-label="Elemento"><strong>Classi</strong></td>
                    <td data-label="Conteggio Attuale"><?php echo $classesCount; ?></td>
                    <td data-label="Descrizione">Classi scolastiche registrate per le quali è possibile definire l'orario.</td>
                </tr>
                <tr>
                    <td data-label="Elemento"><strong>Docenti / Materie</strong></td>
                    <td data-label="Conteggio Attuale"><?php echo $subjectsCount; ?></td>
                    <td data-label="Descrizione">Accoppiamenti di docenti, materie e relativi laboratori inseriti.</td>
                </tr>
                <tr>
                    <td data-label="Elemento"><strong>Ore Programmate</strong></td>
                    <td data-label="Conteggio Attuale"><?php echo $timetableCount; ?></td>
                    <td data-label="Descrizione">Totale delle ore settimanali pianificate e salvate nell'orario generale.</td>
                </tr>
                <?php if ($_SESSION['auth_type'] == 'local'): ?>
                <tr>
                    <td data-label="Elemento"><strong>Utenti</strong></td>
                    <td data-label="Conteggio Attuale"><?php echo $adminsCount; ?></td>
                    <td data-label="Descrizione">Utenti abilitati ad accedere alla dashboard di gestione di questa istanza.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <h3 style="color: #2c3e50; border-left: 5px solid #1f618d; padding-left: 10px; margin-top: 30px; margin-bottom: 15px;">
        Dettagli Ambiente e Server
    </h3>
    <div class="table-container">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 35%;">Parametro</th>
                    <th style="text-align: left;">Valore Rilevato</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td data-label="Parametro"><strong>Versione Piattaforma</strong></td>
                    <td data-label="Valore Rilevato"><?php if (VERSION == "dev") {echo "Sviluppo";} else {echo htmlspecialchars(VERSION);} ?></td>
                </tr>
                <tr>
                    <td data-label="Parametro"><strong>Versione di PHP</strong></td>
                    <td data-label="Valore Rilevato"><?php echo PHP_VERSION; ?><?php if (PHP_DEBUG){echo " (Debug)";} ?></td>
                </tr>
                <tr>
                    <td data-label="Parametro"><strong>Sistema Operativo</strong></td>
                    <td data-label="Valore Rilevato"><?php echo php_uname(); ?></td>
                </tr>
                <tr>
                    <td data-label="Parametro"><strong>Versione Database</strong></td>
                    <td data-label="Valore Rilevato"><?php echo htmlspecialchars($dbVersion); ?></td>
                </tr>
                <tr>
                    <td data-label="Parametro"><strong>Dimensione Database</strong></td>
                    <td data-label="Valore Rilevato"><?php echo round($dbSizeMB, 2); ?> MB</td>
                </tr>
                <tr>
                    <td data-label="Parametro"><strong>Limite Memoria PHP</strong></td>
                    <td data-label="Valore Rilevato"><?php echo htmlspecialchars($memoryLimit ?: 'N/D'); ?></td>
                </tr>
                <tr>
                    <td data-label="Parametro"><strong>Estensioni attive</strong></td>
                    <td data-label="Valore Rilevato" style="text-align: left; font-size: 0.9em; line-height: 1.4;"><?php echo htmlspecialchars(implode(', ', $extensions)); ?></td>
                </tr>
                <tr>
                    <td data-label="Parametro"><strong>Timeout sessione</strong></td>
                    <td data-label="Valore Rilevato" style="text-align: left; font-size: 0.9em; line-height: 1.4;"><?php echo SESSION_LIFETIME; ?> secondi</td>
                </tr>
            </tbody>
        </table>
    </div>

    <h3 style="color: #2c3e50; border-left: 5px solid #1f618d; padding-left: 10px; margin-top: 30px; margin-bottom: 15px;">
        Licenza e Progetto
    </h3>
    <p style="line-height: 1.6; color: #444; margin-bottom: 30px;">
        Questa piattaforma è rilasciata sotto i termini della licenza <strong>GNU Affero General Public License versione 3.0 (AGPL-3.0)</strong>. 
        Ciò significa che puoi liberamente studiare, modificare e distribuire il codice sorgente, a patto che ogni modifica apportata 
        venga resa pubblica e condivisa sotto la medesima licenza qualora il servizio sia reso disponibile in rete.
    </p>
    <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
    <p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.qzz.io/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.qzz.io/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
    </p>
  </div>
</body>
</html>

