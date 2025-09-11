<?php
include("db.php");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Orario - A.S. 2025/26</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/navbar.css">
</head>
<body>
<div class="navbar">
    <div class="logo">Orario Scuola 2025/26</div>
    <div class="links">
      <a href="index.php">Home</a>
      <a href="admin/index.php">Admin</a>
      <a href="https://git.vichingo455.freeddns.org/emmev-code/orario" target="_blank">Codice sorgente</a>
    </div>
  </div>
  <h1>Orario - a.s. 2025/26</h1>

  <!-- Sezione Classi -->
  <h2>Classi</h2>
  <div class="grid">
    <?php
    $years = [1=>"Prime",2=>"Seconde",3=>"Terze",4=>"Quarte",5=>"Quinte"];
    foreach($years as $year=>$label){
      echo "<ul><li><b>$label</b></li>";
      $res = $conn->query("SELECT * FROM classes WHERE name LIKE '$year%' ORDER BY name");
      while($row = $res->fetch_assoc()){
        echo "<li><a href='studenti.php?class_id={$row['id']}'>{$row['name']}</a></li>";
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
      	echo "<li><a href='docenti.php?teacher=".urlencode($teacher_name)."'>Visualizza orario</a></li>";
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
    echo "<li><a href='laboratori.php?room=".urlencode($room_name)."'>Visualizza orario</a></li>";
    echo "</ul>";
}
?>
</div>

<p style="text-align: center;">Copyright (C) 2025 EmmeV. - Released under GNU AGPL 3.0 License.</p>
</body>
</html>
