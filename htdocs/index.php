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
require_once __DIR__ . "/lib/db.php";
require_once __DIR__ . "/lib/csrf.php";
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
if (!isset($_SESSION['admin']) && MAINTENANCE) {
  header("Location: manutenzione.php");
  exit;
}
?>
<!DOCTYPE html>
<html>

<head>
  <title><?php echo APP_NAME; ?> - A.S. <?php echo YEAR; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!--<link rel="stylesheet" href="css/home.css">-->
  <!--<link rel="stylesheet" href="css/navbar.css">-->
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>

<body>
  <nav class="navbar navbar-expand-md navbar-dark bg-dark shadow-sm rounded-bottom mb-4 px-3">
    <div class="container-fluid">

      <a class="navbar-brand fw-bold" href="index.php">
        <?php echo APP_NAME; ?> <?php echo YEAR; ?>
        <?php if (DEV_MODE) echo " - SVILUPPO"; ?>
        <?php if (isset($_SESSION['admin']) && MAINTENANCE) echo " - MANUTENZIONE"; ?>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link fw-bold" href="index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-bold" href="admin/index.php">Admin</a>
          </li>
        </ul>
      </div>

    </div>
  </nav>
  <h1 class="text-center mb-4">
    <?php echo APP_NAME; ?> - A.S. <?php echo YEAR; ?>
  </h1>

  <?php
  if (MAINTENANCE) {
  ?>
    <div class="alert alert-warning text-center" role="alert">
      <strong>Attenzione!</strong> Modalità di manutenzione attiva.
    </div>
  <?php
  }
  ?>

  <div class="row justify-content-center mb-4">
    <div class="col-12 col-md-6 col-lg-5">
      <input
        type="text"
        id="searchBox"
        class="form-control"
        placeholder="Cerca classe, docente o laboratorio..."
        autocomplete="off">
    </div>
  </div>

  <h2 class="mb-3">Classi</h2>

  <div class="row g-3">

    <?php
    $years = [1 => "Prime", 2 => "Seconde", 3 => "Terze", 4 => "Quarte", 5 => "Quinte"];

    foreach ($years as $year => $label) {

      echo '<div class="col-12 col-sm-6 col-md-4 col-lg">';
      echo '<div class="card h-100">';
      echo '<div class="card-body">';
      echo "<h5 class='card-title'>$label</h5>";
      echo '<div class="list-group list-group-flush">';

      $likeYear = $year . '%';
      $stmt = $conn->prepare("SELECT * FROM classes WHERE name LIKE ? ORDER BY name");
      $stmt->bind_param("s", $likeYear);
      $stmt->execute();
      $res = $stmt->get_result();

      while ($row = $res->fetch_assoc()) {
        echo "<a href='studenti.php?class_id={$row['id']}' class='list-group-item list-group-item-action'>";
        echo htmlspecialchars($row['name']);
        echo "</a>";
      }

      echo '</div>';
      echo '</div>';
      echo '</div>';
      echo '</div>';
    }
    ?>

  </div>

  <!-- Sezione Docenti -->
  <h2 class="mb-3">Docenti</h2>

  <div class="row g-3">
    <?php
    $res = $conn->query("SELECT DISTINCT teacher FROM subjects ORDER BY teacher");

    while ($row = $res->fetch_assoc()) {
      if ($row['teacher'] != "No Lezione" && $row['teacher'] != "sconosciuto") {

        $teacher_name = htmlspecialchars($row['teacher']);

        echo "<div class='col-12 col-sm-6 col-md-4 col-lg-3'>";
        echo "  <div class='card h-100 shadow-sm'>";
        echo "    <div class='card-body'>";
        echo "      <h5 class='card-title'>$teacher_name</h5>";
        echo "      <a href='docenti.php?teacher=" . urlencode($teacher_name) . "' class='btn btn-outline-primary btn-sm'>";
        echo "          Visualizza orario";
        echo "      </a>";
        echo "    </div>";
        echo "  </div>";
        echo "</div>";
      }
    }
    ?>
  </div>


  <!-- Sezione Laboratori -->
  <h2 class="mb-3 mt-4">Laboratori</h2>

  <div class="row g-3">
    <?php
    $res = $conn->query("SELECT DISTINCT room FROM subjects WHERE room IS NOT NULL AND room != '' ORDER BY room");

    while ($row = $res->fetch_assoc()) {

      $room_name = htmlspecialchars($row['room']);

      echo "<div class='col-12 col-sm-6 col-md-4 col-lg-3'>";
      echo "  <div class='card h-100 shadow-sm'>";
      echo "    <div class='card-body'>";
      echo "      <h5 class='card-title'>$room_name</h5>";
      echo "      <a href='laboratori.php?room=" . urlencode($room_name) . "' class='btn btn-outline-primary btn-sm'>";
      echo "          Visualizza orario";
      echo "      </a>";
      echo "    </div>";
      echo "  </div>";
      echo "</div>";
    }
    ?>
  </div>

  <footer class="text-center text-body-secondary small mt-5 mb-3">

    Copyright &copy; 2025-2026 EmmeV.
    Rilasciato sotto
    <a href="https://git.vichingo455.qzz.io/emmev-code/orario/src/branch/stable/LICENSE.txt"
      target="_blank"
      class="fw-bold text-decoration-none">
      Licenza GNU AGPL 3.0
    </a>.
    <br>

    Codice sorgente disponibile su
    <a href="https://git.vichingo455.qzz.io/emmev-code/orario"
      target="_blank"
      class="fw-bold text-decoration-none">
      Gitea
    </a>.
    <br>

    La favicon in uso è stata scaricata da
    <a href="https://www.vecteezy.com/free-png/clcok"
      target="_blank"
      class="fw-bold text-decoration-none">
      Vecteezy
    </a>.

  </footer>
  <script>
    document.getElementById('searchBox').addEventListener('input', function(e) {
      const query = e.target.value.toLowerCase().trim();
      const classGrids = document.querySelectorAll('.grid');

      // 1. Filter Classi (First grid - filters individual class items inside columns)
      const classiGrid = classGrids[0];
      if (classiGrid) {
        const uls = classiGrid.querySelectorAll('ul');
        uls.forEach(ul => {
          let visibleButtons = 0;
          const lis = ul.querySelectorAll('li');
          // Skip the header (e.g., "Prime", "Seconde")
          for (let i = 1; i < lis.length; i++) {
            const btn = lis[i].querySelector('a');
            if (btn) {
              const text = btn.textContent.toLowerCase();
              if (text.includes(query)) {
                lis[i].style.display = '';
                visibleButtons++;
              } else {
                lis[i].style.display = 'none';
              }
            }
          }
          // Hide the column entirely if no classes within it match
          ul.style.display = visibleButtons > 0 || query === '' ? '' : 'none';
        });
      }

      // Helper function to filter card-based grids (Docenti & Laboratori)
      function filterCardGrid(gridIndex) {
        const grid = classGrids[gridIndex];
        if (!grid) return 0;
        const uls = grid.querySelectorAll('ul');
        let visibleCards = 0;
        uls.forEach(ul => {
          const header = ul.querySelector('li b');
          if (header) {
            const text = header.textContent.toLowerCase();
            if (text.includes(query)) {
              ul.style.display = '';
              visibleCards++;
            } else {
              ul.style.display = 'none';
            }
          }
        });
        return visibleCards;
      }

      // 2. Filter Docenti (Second grid)
      const visibleDocenti = filterCardGrid(1);

      // 3. Filter Laboratori (Third grid)
      const visibleLaboratori = filterCardGrid(2);

      // Toggle visibility of section headers (h2) if all matching items are hidden
      const headings = document.querySelectorAll('h2');
      if (headings.length >= 3) {
        const visibleClassi = classiGrid ? Array.from(classiGrid.querySelectorAll('ul')).some(ul => ul.style.display !== 'none') : false;

        // Classi section
        headings[0].style.display = visibleClassi || query === '' ? '' : 'none';
        if (classiGrid) classiGrid.style.display = visibleClassi || query === '' ? '' : 'none';

        // Docenti section
        headings[1].style.display = visibleDocenti > 0 || query === '' ? '' : 'none';
        if (classGrids[1]) classGrids[1].style.display = visibleDocenti > 0 || query === '' ? '' : 'none';

        // Laboratori section
        headings[2].style.display = visibleLaboratori > 0 || query === '' ? '' : 'none';
        if (classGrids[2]) classGrids[2].style.display = visibleLaboratori > 0 || query === '' ? '' : 'none';
      }
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>