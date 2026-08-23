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
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo APP_NAME; ?> - Gestisci Materie</title>
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
        <h1 class="fw-bold mb-0"><i class="bi bi-book"></i> Gestisci Materie</h1>
        <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
    </div>

    <!-- Alert placeholder -->
    <div id="alert-container"></div>

    <!-- Card Modifica (hidden by default) -->
    <div class="card border-primary shadow-sm mb-4 d-none" id="edit-subject-card">
      <div class="card-header bg-primary text-white fw-bold">
        <i class="bi bi-pencil-square me-1"></i> Modifica Materia #<span id="edit-id-label"></span>
      </div>
      <div class="card-body">
        <form id="edit-subject-form" class="row g-3">
          <input type="hidden" name="id" id="edit-id-input">
          <div class="col-12 col-md-4">
            <label class="form-label fw-semibold">Materia</label>
            <input type="text" class="form-control" name="name" id="edit-name-input" required placeholder="Materia (es. Informatica)">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-semibold">Docente</label>
            <input type="text" class="form-control" name="teacher" id="edit-teacher-input" required placeholder="Docente (Cognome Nome)">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label fw-semibold">Laboratorio / Aula</label>
            <input type="text" class="form-control" name="room" id="edit-room-input" placeholder="Laboratorio (opzionale)">
          </div>
          <div class="col-12 text-end">
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Salva modifiche</button>
            <button type="button" class="btn btn-secondary ms-2" id="cancel-edit-btn"><i class="bi bi-x-lg"></i> Annulla</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Form Aggiunta -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-body-tertiary fw-bold">
        <i class="bi bi-plus-circle me-1"></i> Aggiungi Nuova Materia
      </div>
      <div class="card-body">
        <form id="add-subject-form" class="row g-3">
          <div class="col-12 col-md-4">
            <input type="text" class="form-control" name="name" id="add-name-input" placeholder="Materia (es. Informatica)" required>
          </div>
          <div class="col-12 col-md-4">
            <input type="text" class="form-control" name="teacher" id="add-teacher-input" placeholder="Docente (Cognome Nome)" required>
          </div>
          <div class="col-12 col-md-4">
            <input type="text" class="form-control" name="room" id="add-room-input" placeholder="Laboratorio (opzionale)">
          </div>
          <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Aggiungi Materia</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Elenco Materie -->
    <h2 class="h4 fw-bold mb-3"><i class="bi bi-journal-bookmark-fill me-1"></i> Elenco Materie e Docenti</h2>
    <div id="subjects-container">
        <div class="alert alert-secondary text-center my-4">Caricamento in corso...</div>
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
  document.addEventListener("DOMContentLoaded", function() {
      const container = document.getElementById("subjects-container");
      const addForm = document.getElementById("add-subject-form");
      const editCard = document.getElementById("edit-subject-card");
      const editForm = document.getElementById("edit-subject-form");
      const alertContainer = document.getElementById("alert-container");
      
      let allSubjects = []; // Local cache to populate edit form easily

      function showAlert(message, type = "danger") {
          alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
              ${message}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>`;
      }

      async function loadSubjects() {
          try {
              const res = await fetch("../api/admin/subjects.php");
              if (!res.ok) throw new Error("Errore nel caricamento delle materie.");
              allSubjects = await res.json();

              if (allSubjects.length === 0) {
                  container.innerHTML = '<div class="alert alert-secondary text-center my-4">Nessuna materia presente nel database.</div>';
                  return;
              }

              // Group by subject name
              const grouped = {};
              allSubjects.forEach(s => {
                  if (!grouped[s.name]) grouped[s.name] = [];
                  grouped[s.name].push(s);
              });

              let html = "";
              for (const subjectName in grouped) {
                  html += `<div class="card shadow-sm border-0 mb-3">
                      <div class="card-header bg-body-tertiary fw-bold text-primary-emphasis border-start border-primary border-4 fs-5">
                          ${subjectName}
                      </div>
                      <div class="card-body">
                          <div class="row g-3">`;
                  
                  grouped[subjectName].forEach(row => {
                      html += `<div class="col-12 col-sm-6 col-md-4 col-lg-3">
                          <div class="card h-100 border bg-body-tertiary">
                              <div class="card-body d-flex flex-column justify-content-between p-3">
                                  <div>
                                      <div class="fw-bold text-secondary mb-1">
                                          <i class="bi bi-person-badge me-1"></i> ${row.teacher}
                                      </div>
                                      ${row.room ? `<span class="badge border border-info text-info"><i class="bi bi-door-open me-1"></i> ${row.room}</span>` : ''}
                                  </div>
                                  <div class="mt-3 d-flex gap-2 justify-content-end">
                                      <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${row.id}">
                                          <i class="bi bi-pencil"></i> Modifica
                                      </button>
                                      <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${row.id}" data-teacher="${row.teacher}" data-name="${row.name}">
                                          <i class="bi bi-trash"></i> Elimina
                                      </button>
                                  </div>
                              </div>
                          </div>
                      </div>`;
                  });
                  
                  html += `</div></div></div>`;
              }
              container.innerHTML = html;
          } catch (e) {
              container.innerHTML = `<div class="alert alert-danger text-center my-4">${e.message}</div>`;
          }
      }

      // Add Subject
      addForm.addEventListener("submit", async function(e) {
          e.preventDefault();
          const name = document.getElementById("add-name-input").value.trim();
          const teacher = document.getElementById("add-teacher-input").value.trim();
          const room = document.getElementById("add-room-input").value.trim();

          try {
              const res = await fetch("../api/admin/subjects.php", {
                  method: "POST",
                  headers: {
                      "Content-Type": "application/json",
                      "X-CSRF-Token": CSRF_TOKEN
                  },
                  body: JSON.stringify({ name, teacher, room })
              });
              const data = await res.json();
              if (!res.ok) throw new Error(data.error || "Errore durante il salvataggio.");

              addForm.reset();
              showAlert("Materia aggiunta con successo!", "success");
              loadSubjects();
          } catch (e) {
              showAlert(e.message);
          }
      });

      // Show Edit Form
      container.addEventListener("click", function(e) {
          const btn = e.target.closest(".btn-edit");
          if (!btn) return;

          const id = parseInt(btn.getAttribute("data-id"));
          const subject = allSubjects.find(s => s.id === id);
          if (!subject) return;

          document.getElementById("edit-id-input").value = subject.id;
          document.getElementById("edit-id-label").innerText = subject.id;
          document.getElementById("edit-name-input").value = subject.name;
          document.getElementById("edit-teacher-input").value = subject.teacher;
          document.getElementById("edit-room-input").value = subject.room || "";

          editCard.classList.remove("d-none");
          editCard.scrollIntoView({ behavior: "smooth" });
      });

      // Cancel Edit
      document.getElementById("cancel-edit-btn").addEventListener("click", function() {
          editCard.classList.add("d-none");
          editForm.reset();
      });

      // Submit Edit
      editForm.addEventListener("submit", async function(e) {
          e.preventDefault();
          const id = parseInt(document.getElementById("edit-id-input").value);
          const name = document.getElementById("edit-name-input").value.trim();
          const teacher = document.getElementById("edit-teacher-input").value.trim();
          const room = document.getElementById("edit-room-input").value.trim();

          try {
              const res = await fetch("../api/admin/subjects.php", {
                  method: "PUT",
                  headers: {
                      "Content-Type": "application/json",
                      "X-CSRF-Token": CSRF_TOKEN
                  },
                  body: JSON.stringify({ id, name, teacher, room })
              });
              const data = await res.json();
              if (!res.ok) throw new Error(data.error || "Errore durante l'aggiornamento.");

              editCard.classList.add("d-none");
              editForm.reset();
              showAlert("Materia aggiornata con successo!", "success");
              loadSubjects();
          } catch (e) {
              showAlert(e.message);
          }
      });

      // Delete Subject
      container.addEventListener("click", async function(e) {
          const btn = e.target.closest(".btn-delete");
          if (!btn) return;

          const id = btn.getAttribute("data-id");
          const name = btn.getAttribute("data-name");
          const teacher = btn.getAttribute("data-teacher");

          if (!confirm(`Sei sicuro di voler eliminare l'insegnamento di ${name} per ${teacher}?`)) return;

          try {
              const res = await fetch(`../api/admin/subjects.php?id=${id}`, {
                  method: "DELETE",
                  headers: {
                      "X-CSRF-Token": CSRF_TOKEN
                  }
              });
              const data = await res.json();
              if (!res.ok) throw new Error(data.error || "Errore durante l'eliminazione.");

              showAlert("Materia eliminata con successo!", "success");
              loadSubjects();
          } catch (e) {
              showAlert(e.message);
          }
      });

      loadSubjects();
  });
  </script>
  <script src="../js/theme.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
