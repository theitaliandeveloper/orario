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
include("lib/db.php");  // FIX: Decommentato
$class_id = intval($_GET['class_id']);
$class = $conn->query("SELECT * FROM classes WHERE id=$class_id")->fetch_assoc();
$days = ["Lunedì","Martedì","Mercoledì","Giovedì","Venerdì","Sabato"];
$hours = [
  1 => "Prima ora<br> 7:50 - 8:50",
  2 => "Seconda ora<br> 8:50 - 9:45",
  3 => "Terza ora<br> 9:55 - 10:50",
  4 => "Quarta ora<br> 10:50 - 11:45",
  5 => "Quinta ora<br> 11:55 - 12:50",
  6 => "Sesta ora<br> 12:50 - 13:50"
];

// FIX: Validazione classe prima di tutto
if (!isset($_GET['class_id'])) {
    header("Location: index.php");
    exit;
}

$class_id = intval($_GET['class_id']);
$res = $conn->query("SELECT id FROM classes WHERE id = $class_id LIMIT 1");

if ($res->num_rows === 0) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Orario <?php echo htmlspecialchars($class['name']); ?></title>
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
  <h1>Orario della classe <?php echo htmlspecialchars($class['name']); ?></h1>
  
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
        $q = $conn->query("SELECT subjects.name, subjects.teacher, subjects.room 
                           FROM timetable 
                           LEFT JOIN subjects ON timetable.subject_id = subjects.id 
                           WHERE class_id=$class_id AND day='$d' AND hour=$hnum");

        if($q->num_rows > 0){
          // FIX: Gestione corretta di multipli docenti/materie
          $entries = [];
          $subject = null;
          $room = null;
          
          while($row = $q->fetch_assoc()){
            if($subject === null) {
              $subject = $row['name'];
              $room = $row['room'];
            }
            $entries[] = $row['teacher'];
          }

          // Unisci i docenti correttamente
          if(count($entries) > 1){
            $last = array_pop($entries);
            $teachers_list = implode(", ", $entries) . " e " . $last;
          } else {
            $teachers_list = $entries[0];
          }

          echo "<td data-label='$d'>
                  <div class='subject'>" . htmlspecialchars($subject) . "</div>
                  <div class='teacher'>" . htmlspecialchars($teachers_list) . "</div>";
          if(!empty($room)) {
            echo "<div class='room'>" . htmlspecialchars($room) . "</div>";
          }
          echo "</td>";
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
        $q = $conn->query("SELECT subjects.name, subjects.teacher, subjects.room 
                           FROM timetable 
                           LEFT JOIN subjects ON timetable.subject_id = subjects.id 
                           WHERE class_id=$class_id AND day='$d' AND hour=$hnum");

        if($q->num_rows > 0):
          // FIX: Stessa logica corretta anche per mobile
          $entries = [];
          $subject = null;
          $room = null;
          
          while($row = $q->fetch_assoc()){
            if($subject === null) {
              $subject = $row['name'];
              $room = $row['room'];
            }
            $entries[] = $row['teacher'];
          }

          if(count($entries) > 1){
            $last = array_pop($entries);
            $teachers_list = implode(", ", $entries) . " e " . $last;
          } else {
            $teachers_list = $entries[0];
          }
      ?>
          <div class="lesson">
            <div class="hour"><?= strip_tags($hlabel) ?></div>
            <div class="subject"><?= htmlspecialchars($subject) ?></div>
            <div class="teacher"><?= htmlspecialchars($teachers_list) ?></div>
            <?php if(!empty($room)): ?><div class="room"><?= htmlspecialchars($room) ?></div><?php endif; ?>
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
