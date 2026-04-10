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
$days = ["Lunedì","Martedì","Mercoledì","Giovedì","Venerdì","Sabato"];
$hours = [
  1 => "Prima ora<br> 7:50 - 8:50",
  2 => "Seconda ora<br> 8:50 - 9:45",
  3 => "Terza ora<br> 9:55 - 10:50",
  4 => "Quarta ora<br> 10:50 - 11:45",
  5 => "Quinta ora<br> 11:55 - 12:50",
  6 => "Sesta ora<br> 12:50 - 13:50"
];

if (!isset($_GET['teacher'])) {
    header("Location: index.php");
    exit;
}

$teacher = $conn->real_escape_string($_GET['teacher']);

if ($teacher == "No Lezione" || $teacher == "sconosciuto") {
    header("Location: index.php");
    exit;
}

$res = $conn->query("SELECT DISTINCT teacher FROM subjects WHERE teacher = '$teacher' LIMIT 1");

if ($res->num_rows === 0) {
    header("Location: index.php");
    exit;
}
else if (isset($_GET['json']) && $_GET['json'] == '1') {
    if (OPEN_DATA) {
      header('Content-Type: application/json; charset=utf-8');
      
      $timetable = [];
      
      foreach($days as $d) {
          $d_clean = str_replace(
              ['à','è','é','ì','ò','ù'],
              ['a','e','e','i','o','u'],
              $d
          );
          $timetable[$d_clean] = [];
          
          foreach($hours as $hnum => $hlabel) {
              $q = $conn->query("SELECT subjects.name, classes.name AS class_name, subjects.room
                                FROM timetable 
                                LEFT JOIN subjects ON timetable.subject_id = subjects.id
                                LEFT JOIN classes ON timetable.class_id = classes.id
                                WHERE subjects.teacher='$teacher' AND timetable.day='$d' AND timetable.hour=$hnum");
              
              if($row = $q->fetch_assoc()) {
                  $timetable[$d_clean][$hnum] = [
                      'hour' => $hnum,
                      'time' => strip_tags($hlabel),
                      'subject' => $row['name'],
                      'class' => $row['class_name'],
                      'room' => $row['room'] ?? ''
                  ];
              } else {
                  $timetable[$d_clean][$hnum] = [
                      'hour' => $hnum,
                      'time' => strip_tags($hlabel),
                      'subject' => null,
                      'class' => null,
                      'room' => null
                  ];
              }
          }
      }
      
      $response = [
          'teacher' => $teacher,
          'timetable' => $timetable
      ];
      
      echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
      exit;
    } else {
      http_response_code(403);
      if (DEV_MODE) {
          echo "Non puoi accedere a questa API perchè gli Open Data in questa istanza sono disattivati. Per attivarli, apri il file config.php e modifica OPEN_DATA su true.";
      }
      else {
          echo "Non puoi accedere a questa API perchè non hai i permessi necessari per farlo.";
      }
      exit;
    }
}
else if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && PDF_EXPORT) {
    require_once 'lib/pdf.php';
    exportTimetablePDF($conn, 'teacher', $teacher);
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
    <div class="logo"><?php echo APP_NAME; ?> <?php echo YEAR; ?></div>
    <div class="links">
      <a href="index.php">Home</a>
      <?php if (PDF_EXPORT):?>
        <a href="?teacher=<?= $teacher ?>&pdf=1" target="_blank">Esporta PDF</a>
      <?php endif;?>
    </div>
  </div>
  
  <h1>Orario docente <?php echo htmlspecialchars($teacher); ?></h1>
  
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
        $q = $conn->query("SELECT subjects.name, classes.name AS class_name, subjects.room
                           FROM timetable 
                           LEFT JOIN subjects ON timetable.subject_id = subjects.id
                           LEFT JOIN classes ON timetable.class_id = classes.id
                           WHERE subjects.teacher='$teacher' AND timetable.day='$d' AND timetable.hour=$hnum");
        if($row = $q->fetch_assoc()){
          echo "<td data-label='$d'>
                  <div class='subject'>" . htmlspecialchars($row['name']) . "</div>
                  <div class='teacher'>" . htmlspecialchars($row['class_name']) . "</div>";
          if(!empty($row['room'])) {
            echo "<div class='room'>" . htmlspecialchars($row['room']) . "</div>";
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

  <!-- FIX: Visualizzazione Mobile aggiunta -->
  <div class="mobile-schedule">
  <?php foreach($days as $d): ?>
    <div class="day">
      <h2><?= htmlspecialchars($d) ?></h2>
      <?php
      foreach($hours as $hnum => $hlabel):
        $q = $conn->query("SELECT subjects.name, classes.name AS class_name, subjects.room
                           FROM timetable 
                           LEFT JOIN subjects ON timetable.subject_id = subjects.id
                           LEFT JOIN classes ON timetable.class_id = classes.id
                           WHERE subjects.teacher='$teacher' AND timetable.day='$d' AND timetable.hour=$hnum");
        
        if($row = $q->fetch_assoc()):
      ?>
          <div class="lesson">
            <div class="hour"><?= strip_tags($hlabel) ?></div>
            <div class="subject"><?= htmlspecialchars($row['name']) ?></div>
            <div class="teacher"><?= htmlspecialchars($row['class_name']) ?></div>
            <?php if(!empty($row['room'])): ?>
              <div class="room"><?= htmlspecialchars($row['room']) ?></div>
            <?php endif; ?>
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

<p style="text-align: center;">Copyright (C) 2025-2026 EmmeV. - Released under <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank">GNU AGPL 3.0 License</a>.</p>
</body>
</html>
