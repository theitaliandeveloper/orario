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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die("Token CSRF non valido."); }
    $name = $_POST['name'];
    if (!empty($name)) { 
        $stmt = $conn->prepare("INSERT INTO classes (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
    }
    header("Location: classes.php"); exit;
}

if (isset($_GET['delete'])) {
    if (!verify_csrf_token($_GET['csrf_token'] ?? '')) { die("Token CSRF non valido."); }
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM classes WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: classes.php"); exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Gestisci Classi</title>
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
        <h1 class="fw-bold mb-0"><i class="bi bi-building"></i> Gestisci Classi</h1>
        <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
    </div>

    <!-- Card Inserimento -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-body-tertiary fw-bold">
        <i class="bi bi-plus-circle me-1"></i> Aggiungi Nuova Classe
      </div>
      <div class="card-body">
        <form method="POST" class="row g-3 align-items-center">
          <?php echo csrf_field(); ?>
          <div class="col-12 col-sm-8 col-md-9">
            <input type="text" class="form-control" name="name" placeholder="Nome Classe (es. 1A, 3BIN, 5AINF)" required>
          </div>
          <div class="col-12 col-sm-4 col-md-3">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Aggiungi</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Tabella Classi -->
    <div class="card shadow-sm border-0">
      <div class="card-header bg-body-tertiary fw-bold">
        <i class="bi bi-list-task me-1"></i> Elenco Classi Registrate
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table">
              <tr>
                <th style="width: 80px;" class="text-center">ID</th>
                <th>Nome Classe</th>
                <th style="width: 120px;" class="text-center">Azione</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $csrf_token = generate_csrf_token();
              $res = $conn->query("SELECT * FROM classes ORDER BY name ASC");
              if ($res->num_rows === 0) {
                echo '<tr><td colspan="3" class="text-center text-muted py-4">Nessuna classe presente nel database.</td></tr>';
              }
              while($row=$res->fetch_assoc()){
                echo "<tr>
                        <td class='text-center fw-bold'>{$row['id']}</td>
                        <td class='fw-semibold text-primary-emphasis'>" . htmlspecialchars($row['name']) . "</td>
                        <td class='text-center'>
                          <a href='classes.php?delete={$row['id']}&csrf_token={$csrf_token}' 
                             class='btn btn-sm btn-outline-danger' 
                             onclick='return confirm(\"Sei sicuro di voler eliminare questa classe?\")'>
                            <i class='bi bi-trash'></i> Elimina
                          </a>
                        </td>
                      </tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
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
