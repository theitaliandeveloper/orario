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
if (!isset($_SESSION['admin']) || $_SESSION['auth_type'] != 'local') {
    header("Location: login.php");
    exit;
}
if (schema_update_required($conn) && MANDATORY_SCHEMA_UPDATE) {
    header("Location: migrate.php?backto=password.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Cambia Password</title>
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
        <h1 class="fw-bold mb-0"><i class="bi bi-key"></i> Cambia Password</h1>
        <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <!-- Alert container -->
            <div id="alert-container"></div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form id="password-form">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="old_password">Password attuale</label>
                            <input type="password" class="form-control" id="old_password" name="old_password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="new_password">Nuova password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="confirm_password">Conferma nuova password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2"><i class="bi bi-check-lg me-1"></i> Cambia password</button>
                    </form>
                </div>
            </div>
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
    const passwordForm = document.getElementById("password-form");
    const oldPasswordInput = document.getElementById("old_password");
    const newPasswordInput = document.getElementById("new_password");
    const confirmPasswordInput = document.getElementById("confirm_password");
    const alertContainer = document.getElementById("alert-container");

    function showAlert(message, type = "success") {
        alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    }

    passwordForm.addEventListener("submit", async function(e) {
        e.preventDefault();

        const old_password = oldPasswordInput.value;
        const new_password = newPasswordInput.value;
        const confirm_password = confirmPasswordInput.value;

        if (new_password !== confirm_password) {
            showAlert("Le nuove password non coincidono.", "danger");
            return;
        }

        try {
            const res = await fetch("../api/admin/password.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": CSRF_TOKEN
                },
                body: JSON.stringify({ old_password, new_password, confirm_password }),
                signal: AbortSignal.timeout(3000)
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.error || "Errore durante il cambio password.");

            showAlert("Password cambiata con successo!", "success");
            passwordForm.reset();

        } catch (e) {
            showAlert(e.message, "danger");
        }
    });
});
</script>
<script src="../js/theme.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
