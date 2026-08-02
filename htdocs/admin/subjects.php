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
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

// FIX: Usa prepared statements per sicurezza
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name']) && !isset($_POST['update'])) {
  if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die("Token CSRF non valido."); }
  $name = $_POST['name'];
  $teacher = $_POST['teacher'];
  $room = $_POST['room'];

  if (!empty($name)) {
    $stmt = $conn->prepare("INSERT INTO subjects (name, teacher, room) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $teacher, $room);
    $stmt->execute();
    $stmt->close();
  }
  header("Location: subjects.php");
  exit;
}

// FIX: Aggiunto redirect dopo update
if (isset($_POST['update'])) {
  if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die("Token CSRF non valido."); }
  $id = intval($_POST['id']);
  $name = $_POST['name'];
  $teacher = $_POST['teacher'];
  $room = $_POST['room'];

  $stmt = $conn->prepare("UPDATE subjects SET name=?, teacher=?, room=? WHERE id=?");
  $stmt->bind_param("sssi", $name, $teacher, $room, $id);
  $stmt->execute();
  $stmt->close();

  header("Location: subjects.php");
  exit;
}

// FIX: Usa prepared statement anche per delete
if (isset($_GET['delete'])) {
  if (!verify_csrf_token($_GET['csrf_token'] ?? '')) { die("Token CSRF non valido."); }
  $id = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM subjects WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
  header("Location: subjects.php");
  exit;
}
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
        <h1 class="fw-bold mb-0"><i class="bi bi-book"></i> Gestisci Materie</h1>
        <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
    </div>

    <?php
    // --- FORM MODIFICA CONDIZIONALE ---
    if (isset($_GET['edit'])) {
      $id = intval($_GET['edit']);
      $stmt = $conn->prepare("SELECT * FROM subjects WHERE id=?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $res = $stmt->get_result();

      if ($res->num_rows > 0) {
        $subject = $res->fetch_assoc();
    ?>
        <div class="card border-primary shadow-sm mb-4">
          <div class="card-header bg-primary text-white fw-bold">
            <i class="bi bi-pencil-square me-1"></i> Modifica Materia #<?php echo $subject['id']; ?>
          </div>
          <div class="card-body">
            <form method="post" action="subjects.php" class="row g-3">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="id" value="<?php echo $subject['id']; ?>">
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Materia</label>
                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($subject['name']); ?>" required placeholder="Materia (es. Informatica)">
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Docente</label>
                <input type="text" class="form-control" name="teacher" value="<?php echo htmlspecialchars($subject['teacher']); ?>" required placeholder="Docente (Cognome Nome)">
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Laboratorio / Aula</label>
                <input type="text" class="form-control" name="room" value="<?php echo htmlspecialchars($subject['room']); ?>" placeholder="Laboratorio (opzionale)">
              </div>
              <div class="col-12 text-end">
                <button type="submit" name="update" class="btn btn-success"><i class="bi bi-check-lg"></i> Salva modifiche</button>
                <a class="btn btn-secondary ms-2" href="subjects.php"><i class="bi bi-x-lg"></i> Annulla</a>
              </div>
            </form>
          </div>
        </div>
    <?php
      }
      $stmt->close();
    }
    ?>

    <!-- Form Aggiunta -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-body-tertiary fw-bold">
        <i class="bi bi-plus-circle me-1"></i> Aggiungi Nuova Materia
      </div>
      <div class="card-body">
        <form method="POST" class="row g-3">
          <?php echo csrf_field(); ?>
          <div class="col-12 col-md-4">
            <input type="text" class="form-control" name="name" placeholder="Materia (es. Informatica)" required>
          </div>
          <div class="col-12 col-md-4">
            <input type="text" class="form-control" name="teacher" placeholder="Docente (Cognome Nome)" required>
          </div>
          <div class="col-12 col-md-4">
            <input type="text" class="form-control" name="room" placeholder="Laboratorio (opzionale)">
          </div>
          <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Aggiungi Materia</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Elenco Materie -->
    <h2 class="h4 fw-bold mb-3"><i class="bi bi-journal-bookmark-fill me-1"></i> Elenco Materie e Docenti</h2>

    <?php
    $csrf_token = generate_csrf_token();
    $res = $conn->query("SELECT * FROM subjects ORDER BY name ASC, teacher ASC");

    $current_subject = "";
    $first_iteration = true;

    if ($res->num_rows === 0) {
      echo '<div class="alert alert-secondary text-center my-4">Nessuna materia presente nel database.</div>';
    }

    while ($row = $res->fetch_assoc()) {
      if ($row['name'] !== $current_subject) {
        if (!$first_iteration) {
          echo '</div></div></div>'; // Chiude row e card-body e card
        }
        $current_subject = $row['name'];
        echo '<div class="card shadow-sm border-0 mb-3">';
        echo '<div class="card-header bg-body-tertiary fw-bold text-primary-emphasis border-start border-primary border-4 fs-5">';
        echo htmlspecialchars($current_subject);
        echo '</div>';
        echo '<div class="card-body"><div class="row g-3">';
        $first_iteration = false;
      }
    ?>
      <div class="col-12 col-sm-6 col-md-4 col-lg-3">
        <div class="card h-100 border bg-body-tertiary">
          <div class="card-body d-flex flex-column justify-content-between p-3">
            <div>
              <div class="fw-bold text-secondary mb-1"><i class="bi bi-person-badge me-1"></i> <?php echo htmlspecialchars($row['teacher']); ?></div>
              <?php if (!empty($row['room'])): ?>
                <span class="badge border border-info text-info"><i class="bi bi-door-open me-1"></i> <?php echo htmlspecialchars($row['room']); ?></span>
              <?php endif; ?>
            </div>
            <div class="mt-3 d-flex gap-2 justify-content-end">
              <a href="subjects.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Modifica</a>
              <a href="subjects.php?delete=<?php echo $row['id']; ?>&csrf_token=<?php echo $csrf_token; ?>"
                onclick="return confirm('Eliminare questo docente?')"
                class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Elimina</a>
            </div>
          </div>
        </div>
      </div>
    <?php }
    if (!$first_iteration) echo '</div></div></div>';
    ?>
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
