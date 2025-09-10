<?php
include("db.php");
$room = $_GET['room']; // aula selezionata
$days = ["Lunedì","Martedì","Mercoledì","Giovedì","Venerdì","Sabato"];
$hours = [
  1 => "Prima ora<br>7:50 - 8:50",
  2 => "Seconda ora<br>8:50 - 9:45",
  3 => "Terza ora<br>9:55 - 10:50",
  4 => "Quarta ora<br>10:50 - 11:45",
  5 => "Quinta ora<br>11:55 - 12:50",
  6 => "Sesta ora<br>12:50 - 13:50"
];
if (!isset($_GET['room'])) {
    header("Location: index.php");
    exit;
}

$room = $conn->real_escape_string($_GET['room']);
$res = $conn->query("SELECT DISTINCT room FROM subjects WHERE room = '$room' LIMIT 1");

if ($res->num_rows === 0) {
    // Aula non trovata
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Orario <?php echo htmlspecialchars($room); ?></title>
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

  <h1>Orario <?php echo htmlspecialchars($room); ?></h1>

  <table>
    <tr>
      <th></th>
      <?php foreach($days as $d) echo "<th>$d</th>"; ?>
    </tr>

    <?php
    foreach($hours as $hnum => $hlabel){
      echo "<tr><td>$hlabel</td>";
      foreach($days as $d){
        $q = $conn->query("
          SELECT subjects.name AS subject_name, subjects.teacher, classes.name AS class_name
          FROM timetable
          LEFT JOIN subjects ON timetable.subject_id = subjects.id
          LEFT JOIN classes ON timetable.class_id = classes.id
          WHERE subjects.room='". $conn->real_escape_string($room) ."' 
            AND timetable.day='$d' AND timetable.hour=$hnum
        ");
        if($row = $q->fetch_assoc()){
          echo "<td data-label='$d'>
                  <div class='subject'>{$row['subject_name']}</div>
                  <div class='teacher'>{$row['teacher']}</div>
                  <div class='room'>{$row['class_name']}</div>
                </td>";
        } else {
          echo "<td data-label='$d'></td>";
        }
      }
      echo "</tr>";
    }
    ?>
  </table>
  <p style="text-align: center;">Copyright (C) 2025 EmmeV. All rights reserved.</p>
</body>
</html>
