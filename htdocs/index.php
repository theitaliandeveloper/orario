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
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo APP_NAME; ?> - A.S. <?php echo YEAR; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/navbar.css">
  <link rel="icon" href="favicon.svg" type="image/svg+xml">
</head>
<body>
<div class="navbar">
    <div class="logo"><?php echo APP_NAME; ?> <?php echo YEAR; ?></div>
    <div class="links">
      <a href="index.php">Home</a>
      <a href="admin/index.php">Admin</a>
    </div>
  </div>
  <h1><?php echo APP_NAME; ?> - A.S. <?php echo YEAR; ?></h1>

  <!-- Sezione Classi -->
  <h2>Classi</h2>
  <div class="grid">
    <?php
    $years = [1=>"Prime",2=>"Seconde",3=>"Terze",4=>"Quarte",5=>"Quinte"];
    foreach($years as $year=>$label){
      echo "<ul><li><b>$label</b></li>";
      $res = $conn->query("SELECT * FROM classes WHERE name LIKE '$year%' ORDER BY name");
      while($row = $res->fetch_assoc()){
        echo "<li><a href='studenti.php?class_id={$row['id']}' class='littlebutton'>{$row['name']}</a></li>";
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
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.freeddns.org/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
</p>
</body>
</html>
