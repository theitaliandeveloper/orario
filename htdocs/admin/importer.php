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
require_once __DIR__ . "/../lib/schema.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$now = time();
if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) {
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
$_SESSION['discard_after'] = $now + SESSION_LIFETIME;
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
else if (!defined('API_URL') || API_URL == "") { header("Location: index.php"); exit; }
if (schema_update_required($conn) && MANDATORY_SCHEMA_UPDATE) {
    header("Location: migrate.php?backto=importer.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Importa Orario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="../css/fonts.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script>
        const CSRF_TOKEN = "<?php echo generate_csrf_token(); ?>";
    </script>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-md bg-primary shadow-sm rounded-bottom mb-4 px-3 text-light">
      <div class="container-fluid">
          <a class="navbar-brand fw-bold text-reset" href="index.php">
              <i class="bi bi-clock"></i>&nbsp;
              <?php echo APP_NAME; ?> <?php echo YEAR; ?> - Admin
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
        <h1 class="fw-bold mb-0"><i class="bi bi-cloud-arrow-down"></i> Importa Orario da Sistema Esterno</h1>
        <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
    </div>

    <!-- Alert placeholder -->
    <div id="alert-container"></div>

    <div class="alert alert-warning shadow-sm mb-4" role="alert">
        <h5 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle me-1"></i> Attenzione!</h5>
        L'importazione cancellerà l'orario esistente della classe selezionata 
        e lo sostituirà con i dati importati dal sistema esterno. 
        Verranno create automaticamente le materie mancanti.
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body-tertiary fw-bold fs-5">
            <i class="bi bi-gear-fill me-1"></i> Configura Importazione
        </div>
        <div class="card-body p-4">
            <form id="import-form" class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="classe_id" class="form-label fw-semibold">Classe di destinazione *</label>
                    <select name="classe_id" id="classe_id" class="form-select" required>
                        <option value="">-- Seleziona classe --</option>
                    </select>
                    <div class="form-text text-muted">Classe nel tuo database dove importare l'orario</div>
                </div>

                <div class="col-12 col-md-6">
                    <label for="classe_codice" class="form-label fw-semibold">Codice classe sorgente *</label>
                    <input type="text" name="classe_codice" id="classe_codice" 
                           class="form-control" placeholder="es: 1A, 2B, 3BIN..." required>
                    <div class="form-text text-muted">Codice della classe nel sistema esterno</div>
                </div>

                <div class="col-12 mt-4 text-end">
                    <button type="submit" id="btn-submit" class="btn btn-warning text-dark fw-bold px-4 py-2">
                        <i class="bi bi-cloud-arrow-down-fill me-1"></i> Importa Orario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-body-tertiary fw-bold">
            <i class="bi bi-question-circle me-1"></i> Guida all'Importazione e Note Tecniche
        </div>
        <div class="card-body p-4">
            <h5 class="fw-bold text-primary mb-3">Come funziona l'importazione</h5>
            <ol class="mb-4">
                <li>Assicurati che il server Node.js sia avviato (<code>node server.js</code>)</li>
                <li>Seleziona la classe di destinazione nel tuo database</li>
                <li>Inserisci il codice della classe nel sistema esterno (es: 3BIN, 1A, 5ACM)</li>
                <li>Clicca su "Importa Orario"</li>
                <li>Il sistema cancellerà l'orario esistente e importerà i nuovi dati</li>
            </ol>

            <h5 class="fw-bold text-primary mb-3">Note tecniche</h5>
            <ul class="mb-0">
                <li>Ogni slot orario viene inserito UNA SOLA VOLTA nella tabella timetable</li>
                <li>Le informazioni su docenti e aule dall'API vengono salvate nel nuovo modello normalizzato (slot, lezioni, docenti e aule).</li>
                <li>Le materie vengono create automaticamente se non esistono già</li>
                <li>Gli slot vuoti nell'orario vengono saltati</li>
            </ul>
        </div>
    </div>
</div>

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

<script>
document.addEventListener("DOMContentLoaded", async function() {
    const classSelect = document.getElementById("classe_id");
    const importForm = document.getElementById("import-form");
    const btnSubmit = document.getElementById("btn-submit");
    const alertContainer = document.getElementById("alert-container");

    function showAlert(message, type = "success") {
        alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="closeAlert()"></button>
        </div>`;
    }

    function closeAlert() {
        alertContainer.innerHTML = "";
    }

    // Load classes dropdown
    try {
        const res = await fetch("../api/admin/classes.php",{ signal: AbortSignal.timeout(3000) });
        if (!res.ok) throw new Error("Errore nel caricamento delle classi.");
        const classes = await res.json();
        
        classes.forEach(c => {
            const opt = document.createElement("option");
            opt.value = c.id;
            opt.innerText = c.name;
            classSelect.appendChild(opt);
        });
    } catch (e) {
        showAlert(e.message, "danger");
    }

    // Handle Form Submit
    importForm.addEventListener("submit", async function(e) {
        e.preventDefault();
        
        const classe_id = parseInt(classSelect.value);
        const classe_codice = document.getElementById("classe_codice").value.trim();

        if (!classe_id || !classe_codice) return;

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Importazione in corso...`;
        closeAlert();

        try {
            const res = await fetch("../api/admin/importer.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": CSRF_TOKEN
                },
                body: JSON.stringify({ classe_id, classe_codice }),
                signal: AbortSignal.timeout(10000)
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.error || "Errore durante l'importazione.");

            let msg = `<strong>Importazione completata con successo!</strong><br>`;
            msg += `- Inserite ${data.inserimenti} ore di lezione.<br>`;
            if (data.materie_create_count > 0) {
                msg += `- Create ${data.materie_create_count} nuove materie.<br>`;
            }
            msg += `- Importati ${data.docenti_importati?.length || 0} docenti e ${data.laboratori_importati?.length || 0} laboratori.`;
            showAlert(msg, "success");
            importForm.reset();

        } catch (e) {
            showAlert(e.message, "danger");
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = `<i class="bi bi-cloud-arrow-down-fill me-1"></i> Importa Orario`;
        }
    });
});
</script>
<script src="../js/theme.js"></script>
</body>
</html>
