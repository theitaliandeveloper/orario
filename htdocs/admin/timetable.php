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

// --- Recupera tutte le materie ---
$subjects = [];
$res = $conn->query("SELECT * FROM subjects ORDER BY name ASC");
while ($r = $res->fetch_assoc()) {
    $label = $r['name'];
    if (!empty($r['teacher'])) $label .= " ({$r['teacher']})";
    if (!empty($r['room']))    $label .= " ({$r['room']})";
    $subjects[] = ['id' => $r['id'], 'label' => $label];
}

// --- Salvataggio orario ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['class_id']) && isset($_POST['subject'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die("Token CSRF non valido."); }
    $class_id = intval($_POST['class_id']);
    if ($class_id > 0) {
        // Cancella solo l'orario di questa classe
        $stmt_del = $conn->prepare("DELETE FROM timetable WHERE class_id=?");
        $stmt_del->bind_param("i", $class_id);
        $stmt_del->execute();

        $stmt_ins = $conn->prepare("INSERT INTO timetable (class_id, day, hour, subject_id) VALUES (?, ?, ?, ?)");
        
        foreach ($_POST['subject'] as $day => $hours) {
            foreach ($hours as $hour => $sub_ids) {
                foreach ($sub_ids as $subject_id) {
                    $subject_id = intval($subject_id);
                    if (!empty($subject_id)) {
                        $stmt_ins->bind_param("isii", $class_id, $day, $hour, $subject_id);
                        $stmt_ins->execute();
                    }
                }
            }
        }

        header("Location: timetable.php?class_id=$class_id&saved=1");
        exit;
    }
}

// --- Selezione classe corrente ---
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// --- Precaricamento dati orario ---
$preselectedData = [];
if ($class_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM timetable WHERE class_id=?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $preselectedData[$r['day']][$r['hour']][] = $r['subject_id'];
    }
}
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

<div class="container-fluid px-4 my-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="fw-bold mb-0"><i class="bi bi-calendar3"></i> Gestisci Orario</h1>
        <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Orario salvato con successo!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body-tertiary fw-bold">
            <i class="bi bi-funnel me-1"></i> Editor Classe
        </div>
        <div class="card-body">
            <form method="POST" autocomplete="off">
                <?php echo csrf_field(); ?>
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="class_id_select" class="form-label fw-bold mb-0">Seleziona la classe:</label>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <select id="class_id_select" name="class_id" class="form-select" required onchange="window.location='timetable.php?class_id='+this.value;">
                            <option value="" disabled <?= $class_id === 0 ? 'selected' : '' ?>>-- Scegli una classe --</option>
                            <?php
                            $res = $conn->query("SELECT * FROM classes ORDER BY name ASC");
                            while ($r = $res->fetch_assoc()) {
                                $selected = ($class_id == $r['id']) ? 'selected' : '';
                                echo "<option value='{$r['id']}' $selected>{$r['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <?php if ($class_id > 0): ?> 
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
                        <tbody>
                            <?php
                            $days = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
                            for ($hour = 1; $hour <= 6; $hour++) {
                                echo "<tr>";
                                echo "<td class='fw-bold bg-body-tertiary'>{$hour}ª ora</td>";
                                foreach ($days as $day) {
                                    $preselected = $preselectedData[$day][$hour] ?? [''];
                                    echo "<td class='p-2'>";
                                    echo "<div class='subject-container' data-day='$day' data-hour='$hour'>";
                                    foreach ($preselected as $subject_id) {
                                        echo "<div class='subject-row d-flex align-items-center justify-content-center gap-1 mb-1'>";
                                        echo "<select name='subject[$day][$hour][]' class='form-select form-select-sm'>";
                                        echo "<option value=''>-- Nessuna --</option>";
                                        foreach ($subjects as $s) {
                                            $sel = ($subject_id == $s['id']) ? 'selected' : '';
                                            echo "<option value='{$s['id']}' $sel>" . htmlspecialchars($s['label']) . "</option>";
                                        }
                                        echo "</select>";
                                        echo "<button type='button' class='btn btn-sm btn-outline-danger remove-subject py-0 px-2' title='Rimuovi materia'>−</button>";
                                        echo "</div>";
                                    }
                                    echo "<button type='button' class='btn btn-sm btn-outline-success add-subject py-0 px-2 mt-1' title='Aggiungi compresenza / materia'>+</button>";
                                    echo "</div>";
                                    echo "</td>";
                                }
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="bi bi-floppy me-1"></i> Salva orario</button>
                </div>
                <?php endif; ?>
            </form>
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

<script>
document.addEventListener('click', function(e){
    if(e.target.classList.contains('add-subject')){
        const container = e.target.closest('.subject-container');
        const firstRow = container.querySelector('.subject-row');
        const clone = firstRow.cloneNode(true);
        clone.querySelector('select').value = '';
        container.insertBefore(clone, e.target);
    }

    if(e.target.classList.contains('remove-subject')){
        const container = e.target.closest('.subject-container');
        const rows = container.querySelectorAll('.subject-row');
        if(rows.length > 1){
            e.target.closest('.subject-row').remove();
        } else {
            rows[0].querySelector('select').value = '';
        }
    }
});
</script>
</body>
</html>
