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
include("lib/db.php");
include("lib/csrf.php");
session_start();
$now = time();
if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) { // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
    session_unset();
    session_destroy();
    session_start();
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
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/navbar.css">
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
</head>
<body>
<div class="navbar">
    <div class="logo"><?php echo APP_NAME; ?> <?php echo YEAR; ?><?php if (DEV_MODE){echo " - SVILUPPO";}?><?php if (isset($_SESSION['admin']) && MAINTENANCE){echo " - MANUTENZIONE";}?></div>
    <div class="links">
      <a href="index.php">Home</a>
      <a href="admin/index.php">Admin</a>
    </div>
  </div>
  <h1><?php echo APP_NAME; ?> - A.S. <?php echo YEAR; ?></h1>

  <?php
  if (MAINTENANCE) {
    echo "<p class='centered' style='color: red; font-size: 18px;'>ATTENZIONE! MODALITA' DI MANUTENZIONE ATTIVA!</p>";
  }
  ?>

  <!-- Search Box -->
  <div class="search-container">
    <input type="text" id="searchBox" placeholder="Cerca classe, docente o laboratorio..." autocomplete="off">
  </div>

  <!-- Sezione Classi -->
  <h2>Classi</h2>
  <div class="grid">
    <?php
    $years = [1=>"Prime",2=>"Seconde",3=>"Terze",4=>"Quarte",5=>"Quinte"];
    foreach($years as $year=>$label){
      echo "<ul><li><b>$label</b></li>";
      $likeYear = $year . '%';
      $stmt = $conn->prepare("SELECT * FROM classes WHERE name LIKE ? ORDER BY name");
      $stmt->bind_param("s", $likeYear);
      $stmt->execute();
      $res = $stmt->get_result();
      while($row = $res->fetch_assoc()){
        echo "<li><a href='studenti.php?class_id={$row['id']}' class='littlebutton'>" . htmlspecialchars($row['name']) . "</a></li>";
      }
      echo "</ul>";
    }
    ?>
  </div>

  <!-- Sezione Docenti -->
  <h2>Docenti</h2>
  <div class="grid">
    <?php
    $res = $conn->query("SELECT DISTINCT teacher FROM subjects ORDER BY teacher");
    while($row = $res->fetch_assoc()){
      if ($row['teacher'] != "No Lezione" && $row['teacher'] != "sconosciuto") {
	$teacher_name = htmlspecialchars($row['teacher']);
      	echo "<ul><li><b>$teacher_name</b></li>";
      	echo "<li><a href='docenti.php?teacher=".urlencode($teacher_name)."' class='littlebutton'>Visualizza orario</a></li>";
     	echo "</ul>";
      }
    }
    ?>
  </div>

<!-- Sezione Aule -->
<h2>Laboratori</h2>
<div class="grid">
<?php
$res = $conn->query("SELECT DISTINCT room FROM subjects WHERE room IS NOT NULL AND room != '' ORDER BY room");
while($row = $res->fetch_assoc()){
    $room_name = htmlspecialchars($row['room']);
    echo "<ul><li><b>$room_name</b></li>";
    echo "<li><a href='laboratori.php?room=".urlencode($room_name)."' class='littlebutton'>Visualizza orario</a></li>";
    echo "</ul>";
}
?>
</div>

<p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.qzz.io/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.qzz.io/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
</p>
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
</body>
</html>
