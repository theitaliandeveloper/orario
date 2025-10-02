<?php
include("lib/db.php");
include("config/config.php");
$teacher = $_GET['teacher'];
$days = ["Lunedì","Martedì","Mercoledì","Giovedì","Venerdì","Sabato"];
$hours = [
  1 => "Prima ora<br>7:50 - 8:50",
  2 => "Seconda ora<br>8:50 - 9:45",
  3 => "Terza ora<br>9:55 - 10:50",
  4 => "Quarta ora<br>10:50 - 11:45",
  5 => "Quinta ora<br>11:55 - 12:50",
  6 => "Sesta ora<br>12:50 - 13:50"
];
if ($teacher == "No Lezione" || $teacher == "sconosciuto") {
	  header("Location: index.php");
    exit;
}
else if (!isset($_GET['teacher'])) {
    header("Location: index.php");
    exit;
}

$teacher = $conn->real_escape_string($_GET['teacher']);
$res = $conn->query("SELECT DISTINCT teacher FROM subjects WHERE teacher = '$teacher' LIMIT 1");

if ($res->num_rows === 0) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Orario <?php echo htmlspecialchars($teacher); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/timetable.css">
  <link rel="stylesheet" href="css/navbar.css">
</head>
<body>
  <div class="navbar">
    <div class="logo">Orario Scuola 2025/26</div>
    <div class="links">
      <a href="index.php">Home</a>
    </div>
  </div>
  <h1>Orario docente <?php echo htmlspecialchars($teacher); ?></h1>
  <table>
    <tr>
      <th></th>
      <?php foreach($days as $d) echo "<th>$d</th>"; ?>
    </tr>
    <?php
    foreach($hours as $hnum => $hlabel){
      echo "<tr><td>$hlabel</td>";
      foreach($days as $d){
        $q = $conn->query("SELECT subjects.name, classes.name AS class_name, subjects.room
                           FROM timetable 
                           LEFT JOIN subjects ON timetable.subject_id = subjects.id
                           LEFT JOIN classes ON timetable.class_id = classes.id
                           WHERE subjects.teacher='$teacher' AND timetable.day='$d' AND timetable.hour=$hnum");
        if($row = $q->fetch_assoc()){
          echo "<td data-label='$d'>
                  <div class='subject'>{$row['name']}</div>
                  <div class='teacher'>{$row['class_name']}</div>
                  <div class='room'>{$row['room']}</div>
                </td>";
        } else {
          echo "<td data-label='$d'></td>";
        }
      }
      echo "</tr>";
    }
    ?>
  </table>
<p style="text-align: center;">Copyright (C) 2025 EmmeV. - Released under <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank">GNU AGPL 3.0 License</a>.</p>
</body>
</html>
