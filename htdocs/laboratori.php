<?php
/*
Orario Scuola, Copyright (C) 2025 EmmeV.

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
$days = ["Lunedì","Martedì","Mercoledì","Giovedì","Venerdì","Sabato"];
$hours = [
  1 => "Prima ora<br> 7:50 - 8:50",
  2 => "Seconda ora<br> 8:50 - 9:45",
  3 => "Terza ora<br> 9:55 - 10:50",
  4 => "Quarta ora<br> 10:50 - 11:45",
  5 => "Quinta ora<br> 11:55 - 12:50",
  6 => "Sesta ora<br> 12:50 - 13:50"
];

if (!isset($_GET['room'])) {
    header("Location: index.php");
    exit;
}

$room = $conn->real_escape_string($_GET['room']);
$res = $conn->query("SELECT DISTINCT room FROM subjects WHERE room = '$room' LIMIT 1");

if ($res->num_rows === 0) {
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
    <div class="logo"><?php echo APP_NAME; ?> <?php echo YEAR; ?></div>
    <div class="links">
      <a href="index.php">Home</a>
    </div>
  </div>

  <h1>Orario <?php echo htmlspecialchars($room); ?></h1>

  <!-- Visualizzazione Desktop -->
  <table class="desktop-schedule">
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

        if($q->num_rows > 0){
          $subject = null;
          // FIX: Uso array associativo per evitare duplicati classe+docente
          $class_teacher_pairs = [];

          while($row = $q->fetch_assoc()){
            if($subject === null) {
              $subject = $row['subject_name'];
            }
            // Creo una coppia unica classe-docente
            $pair = $row['class_name'] . " (" . $row['teacher'] . ")";
            $class_teacher_pairs[$pair] = true; // Uso chiave per evitare duplicati
          }

          // Converto in array e unisco
          $entries = array_keys($class_teacher_pairs);
          
          if(count($entries) > 1){
            $last = array_pop($entries);
            $entries_list = implode(", ", $entries) . " e " . $last;
          } else {
            $entries_list = $entries[0];
          }

          echo "<td data-label='$d'>
                  <div class='subject'>" . htmlspecialchars($subject) . "</div>
                  <div class='room'>" . htmlspecialchars($entries_list) . "</div>
                </td>";
        } else {
          echo "<td data-label='$d'></td>";
        }
      }
      echo "</tr>";
    }
    ?>
  </table>

  <!-- Visualizzazione Mobile -->
  <div class="mobile-schedule">
  <?php foreach($days as $d): ?>
    <div class="day">
      <h2><?= htmlspecialchars($d) ?></h2>
      <?php
      foreach($hours as $hnum => $hlabel):
        $q = $conn->query("
          SELECT subjects.name AS subject_name, subjects.teacher, classes.name AS class_name
          FROM timetable
          LEFT JOIN subjects ON timetable.subject_id = subjects.id
          LEFT JOIN classes ON timetable.class_id = classes.id
          WHERE subjects.room='". $conn->real_escape_string($room) ."' 
            AND timetable.day='$d' AND timetable.hour=$hnum
        ");

        if($q->num_rows > 0):
          $subject = null;
          $class_teacher_pairs = [];

          while($row = $q->fetch_assoc()){
            if($subject === null) {
              $subject = $row['subject_name'];
            }
            $pair = $row['class_name'] . " (" . $row['teacher'] . ")";
            $class_teacher_pairs[$pair] = true;
          }

          $entries = array_keys($class_teacher_pairs);
          
          if(count($entries) > 1){
            $last = array_pop($entries);
            $entries_list = implode(", ", $entries) . " e " . $last;
          } else {
            $entries_list = $entries[0];
          }
      ?>
          <div class="lesson">
            <div class="hour"><?= strip_tags($hlabel) ?></div>
            <div class="subject"><?= htmlspecialchars($subject) ?></div>
            <div class="room"><?= htmlspecialchars($entries_list) ?></div>
          </div>
      <?php else: ?>
          <div class="lesson empty">
            <div class="hour"><?= strip_tags($hlabel) ?></div>
            <div class="subject">—</div>
          </div>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  </div>

  <p style="text-align: center;">Copyright (C) 2025 EmmeV. - Released under <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank">GNU AGPL 3.0 License</a>.</p>
</body>
</html>
