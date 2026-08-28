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

if (!isset($_SESSION['admin']) || $_SESSION['auth_type'] != 'local' || $_SESSION['admin'] != 'admin') {
    header("Location: login.php");
    exit;
}
if (schema_update_required($conn) && MANDATORY_SCHEMA_UPDATE) {
    header("Location: migrate.php?backto=users.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Gestione Admin</title>
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
        <h1 class="fw-bold mb-0"><i class="bi bi-people"></i> Gestione Utenti Admin</h1>
        <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
    </div>

    <!-- Alert container -->
    <div id="alert-container"></div>

    <!-- Card Tabella Utenti -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body-tertiary fw-bold">
            <i class="bi bi-person-lines-fill me-1"></i> Amministratori Attivi
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table">
                        <tr>
                            <th style="width: 80px;" class="text-center">ID</th>
                            <th>Username</th>
                            <th style="width: 160px;" class="text-center">Azione</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body">
                        <tr><td colspan="3" class="text-center text-muted py-4">Caricamento in corso...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Card Aggiungi Utente -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-body-tertiary fw-bold">
            <i class="bi bi-person-plus-fill me-1"></i> Aggiungi nuovo amministratore
        </div>
        <div class="card-body p-4">
            <form id="add-user-form" class="row g-3">
                <div class="col-12 col-md-5">
                    <label class="form-label fw-semibold" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required placeholder="Nuovo username">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label fw-semibold" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Nuova password">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Aggiungi</button>
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
document.addEventListener("DOMContentLoaded", function() {
    const tbody = document.getElementById("users-table-body");
    const addForm = document.getElementById("add-user-form");
    const usernameInput = document.getElementById("username");
    const passwordInput = document.getElementById("password");
    const alertContainer = document.getElementById("alert-container");

    function showAlert(message, type = "danger") {
        alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    }

    async function loadUsers() {
        try {
            const res = await fetch("../api/admin/users.php", { signal: AbortSignal.timeout(3000) });
            if (!res.ok) throw new Error("Errore nel caricamento degli utenti.");
            const users = await res.json();
            
            let html = "";
            users.forEach(u => {
                html += `<tr>
                    <td class="text-center fw-bold">${u.id}</td>
                    <td class="fw-semibold text-primary-emphasis">
                        <i class="bi bi-person-fill me-1"></i> ${u.username}
                        ${u.id === 1 ? '<span class="badge bg-primary ms-2">Predefinito</span>' : ''}
                    </td>
                    <td class="text-center">
                        ${u.id !== 1 ? `
                            <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${u.id}" data-username="${u.username}">
                                <i class="bi bi-trash"></i> Elimina
                            </button>
                        ` : '<span class="badge bg-secondary-subtle text-secondary border">Protetto</span>'}
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger py-4">${e.message}</td></tr>`;
        }
    }

    // Add user
    addForm.addEventListener("submit", async function(e) {
        e.preventDefault();
        const username = usernameInput.value.trim();
        const password = passwordInput.value;
        if (!username || !password) return;

        try {
            const res = await fetch("../api/admin/users.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": CSRF_TOKEN
                },
                body: JSON.stringify({ username, password }),
                signal: AbortSignal.timeout(3000)
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.error || "Errore durante il salvataggio.");

            usernameInput.value = "";
            passwordInput.value = "";
            showAlert("Utente admin aggiunto con successo!", "success");
            loadUsers();
        } catch (e) {
            showAlert(e.message, "danger");
        }
    });

    // Delete user
    tbody.addEventListener("click", async function(e) {
        const btn = e.target.closest(".btn-delete");
        if (!btn) return;

        const id = btn.getAttribute("data-id");
        const username = btn.getAttribute("data-username");

        if (!confirm(`Vuoi davvero eliminare l'utente ${username}?`)) return;

        try {
            const res = await fetch(`../api/admin/users.php?id=${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-Token": CSRF_TOKEN
                },
                signal: AbortSignal.timeout(3000)
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.error || "Errore durante l'eliminazione.");

            showAlert("Utente admin rimosso con successo!", "success");
            loadUsers();
        } catch (e) {
            showAlert(e.message, "danger");
        }
    });

    loadUsers();
});
</script>
<script src="../js/theme.js"></script>
</body>
</html>
