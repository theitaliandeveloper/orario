<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.
*/
require_once __DIR__ . "/../lib/db.php";
require_once __DIR__ . "/../lib/csrf.php";
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

$initial_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Gestisci Orario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="../css/fonts.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        .subject-row select { min-width: 140px; }
    </style>
    <script>
        const CSRF_TOKEN = "<?php echo generate_csrf_token(); ?>";
        const INITIAL_CLASS_ID = <?php echo $initial_class_id; ?>;
    </script>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-md bg-primary shadow-sm rounded-bottom mb-4 px-3 text-light">
      <div class="container-fluid">
          <a class="navbar-brand fw-bold text-reset" href="index.php">
              <i class="bi bi-clock"></i>&nbsp;
              <?php echo APP_NAME; ?> <?php echo YEAR; ?> - Admin
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

<div class="container-fluid px-4 my-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="fw-bold mb-0"><i class="bi bi-calendar3"></i> Gestisci Orario</h1>
        <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
    </div>

    <!-- Alert container -->
    <div id="alert-container"></div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body-tertiary fw-bold">
            <i class="bi bi-funnel me-1"></i> Editor Classe
        </div>
        <div class="card-body">
            <div class="row align-items-center g-3 mb-4">
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="class_id_select" class="form-label fw-bold mb-0">Seleziona la classe:</label>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <select id="class_id_select" name="class_id" class="form-select" required>
                        <option value="" disabled selected>-- Scegli una classe --</option>
                    </select>
                </div>
            </div>

            <form id="timetable-form" class="d-none">
                <hr class="my-4">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center mb-4">
                        <thead class="table-primary">
                            <tr>
                                <th style="width: 100px;">Ora</th>
                                <th>Lunedì</th>
                                <th>Martedì</th>
                                <th>Mercoledì</th>
                                <th>Giovedì</th>
                                <th>Venerdì</th>
                                <th>Sabato</th>
                            </tr>
                        </thead>
                        <tbody id="timetable-grid">
                            <!-- JS Will Render Grid here -->
                        </tbody>
                    </table>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="bi bi-floppy me-1"></i> Salva orario</button>
                </div>
            </form>
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
    const classSelect = document.getElementById("class_id_select");
    const timetableForm = document.getElementById("timetable-form");
    const timetableGrid = document.getElementById("timetable-grid");
    const alertContainer = document.getElementById("alert-container");

    const days = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
    
    let subjects = [];

    function showAlert(message, type = "success") {
        alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    }

    // Load initial data (classes and subjects)
    try {
        const [classesRes, subjectsRes] = await Promise.all([
            fetch("../api/admin/classes.php"),
            fetch("../api/admin/subjects.php")
        ]);

        if (!classesRes.ok || !subjectsRes.ok) throw new Error("Errore nel caricamento delle configurazioni.");

        const classes = await classesRes.json();
        subjects = await subjectsRes.json();

        classes.forEach(c => {
            const opt = document.createElement("option");
            opt.value = c.id;
            opt.innerText = c.name;
            classSelect.appendChild(opt);
        });

        if (INITIAL_CLASS_ID > 0) {
            classSelect.value = INITIAL_CLASS_ID;
            loadClassTimetable(INITIAL_CLASS_ID);
        }

    } catch (e) {
        showAlert(e.message, "danger");
    }

    // Class selection changed
    classSelect.addEventListener("change", function() {
        const classId = this.value;
        if (classId) {
            loadClassTimetable(classId);
            // Optional: update URL query parameter quietly
            history.pushState(null, "", `timetable.php?class_id=${classId}`);
        } else {
            timetableForm.classList.add("d-none");
        }
    });

    async function loadClassTimetable(classId) {
        try {
            timetableForm.classList.add("d-none");
            const res = await fetch(`../api/admin/timetable.php?class_id=${classId}`);
            if (!res.ok) throw new Error("Errore nel caricamento dell'orario.");
            const preselected = await res.json(); // Array of {day, hour, subject_id}

            // Build grid
            let html = "";
            for (let hour = 1; hour <= 6; hour++) {
                html += `<tr>`;
                html += `<td class="fw-bold bg-body-tertiary">${hour}ª ora</td>`;
                days.forEach(day => {
                    const cellSubjects = preselected.filter(item => item.day === day && item.hour === hour);
                    
                    // We must have at least one slot select element
                    const slots = cellSubjects.length > 0 ? cellSubjects.map(s => s.subject_id) : [0];

                    html += `<td class="p-2">
                        <div class="subject-container" data-day="${day}" data-hour="${hour}">`;
                    
                    slots.forEach(subId => {
                        html += buildSelectRow(subId);
                    });

                    html += `<button type="button" class="btn btn-sm btn-outline-success add-subject py-0 px-2 mt-1" title="Aggiungi compresenza / materia">+</button>
                        </div>
                    </td>`;
                });
                html += `</tr>`;
            }

            timetableGrid.innerHTML = html;
            timetableForm.classList.remove("d-none");

        } catch (e) {
            showAlert(e.message, "danger");
        }
    }

    function buildSelectRow(selectedId = 0) {
        let html = `<div class="subject-row d-flex align-items-center justify-content-center gap-1 mb-1">
            <select class="form-select form-select-sm">
                <option value="">-- Nessuna --</option>`;
        subjects.forEach(s => {
            const label = s.name + (s.teacher ? ` (${s.teacher})` : "") + (s.room ? ` (${s.room})` : "");
            const sel = s.id === selectedId ? "selected" : "";
            html += `<option value="${s.id}" ${sel}>${escapeHtml(label)}</option>`;
        });
        html += `</select>
            <button type="button" class="btn btn-sm btn-outline-danger remove-subject py-0 px-2" title="Rimuovi materia">−</button>
        </div>`;
        return html;
    }

    function escapeHtml(str) {
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Dynamic grid interactions
    document.addEventListener("click", function(e) {
        if (e.target.classList.contains("add-subject")) {
            const container = e.target.closest(".subject-container");
            const tempDiv = document.createElement("div");
            tempDiv.innerHTML = buildSelectRow(0);
            container.insertBefore(tempDiv.firstChild, e.target);
        }

        if (e.target.classList.contains("remove-subject")) {
            const container = e.target.closest(".subject-container");
            const rows = container.querySelectorAll(".subject-row");
            if (rows.length > 1) {
                e.target.closest(".subject-row").remove();
            } else {
                rows[0].querySelector("select").value = "";
            }
        }
    });

    // Form Submit (Save Timetable)
    timetableForm.addEventListener("submit", async function(e) {
        e.preventDefault();
        const classId = classSelect.value;
        if (!classId) return;

        const assignments = [];
        document.querySelectorAll(".subject-container").forEach(container => {
            const day = container.getAttribute("data-day");
            const hour = parseInt(container.getAttribute("data-hour"));
            container.querySelectorAll(".subject-row select").forEach(select => {
                const val = parseInt(select.value);
                if (val) {
                    assignments.push({ day, hour, subject_id: val });
                }
            });
        });

        try {
            const res = await fetch("../api/admin/timetable.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": CSRF_TOKEN
                },
                body: JSON.stringify({ class_id: classId, assignments })
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.error || "Errore durante il salvataggio.");

            showAlert("Orario salvato con successo!", "success");
            loadClassTimetable(classId);
        } catch (e) {
            showAlert(e.message, "danger");
        }
    });
});
</script>
<script src="../js/theme.js"></script>
</body>
</html>
