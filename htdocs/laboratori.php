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

$room = $_GET['room']; // Will be bound
$stmt = $conn->prepare("SELECT DISTINCT room FROM subjects WHERE room = ? LIMIT 1");
$stmt->bind_param("s", $room);
$stmt->execute();
$res = $stmt->get_result();

function joinList(array $arr): string {
    if (empty($arr)) return '';
    if (count($arr) === 1) return $arr[0];
    $last = array_pop($arr);
    return implode(', ', $arr) . ' e ' . $last;
}

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
              $stmt = $conn->prepare("
                SELECT subjects.name AS subject_name, subjects.teacher, classes.name AS class_name
                FROM timetable
                LEFT JOIN subjects ON timetable.subject_id = subjects.id
                LEFT JOIN classes ON timetable.class_id = classes.id
                WHERE subjects.room=? 
                  AND timetable.day=? AND timetable.hour=?
              ");
              $stmt->bind_param("ssi", $room, $d, $hnum);
              $stmt->execute();
              $q = $stmt->get_result();
              
              if($q->num_rows > 0) {
                  $subject = null;
                  $class_teacher_pairs = [];
                  
                  while($row = $q->fetch_assoc()) {
                      if($subject === null) {
                          $subject = $row['subject_name'];
                      }
                      $class_teacher_pairs[] = [
                          'class' => $row['class_name'],
                          'teacher' => $row['teacher']
                      ];
                  }
                  
                  $timetable[$d_clean][$hnum] = [
                      'hour' => $hnum,
                      'time' => strip_tags($hlabel),
                      'subject' => $subject,
                      'classes' => $class_teacher_pairs
                  ];
              } else {
                  $timetable[$d_clean][$hnum] = [
                      'hour' => $hnum,
                      'time' => strip_tags($hlabel),
                      'subject' => null,
                      'classes' => []
                  ];
              }
          }
      }
      
      $response = [
          'room' => $room,
          'timetable' => $timetable
      ];
      
      echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
      exit;
    } else {
      http_response_code(403);
      if (DEV_MODE) {
          echo "Non puoi accedere a questo JSON perchè gli Open Data in questa istanza sono disattivati. Per attivarli, apri il file config.php e modifica OPEN_DATA su true.";
      }
      else {
          echo "Non puoi accedere a questo JSON perchè non hai i permessi necessari per farlo.";
      }
      exit;
    }
}
else if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && PDF_EXPORT) {
    require_once 'lib/pdf.php';
    exportTimetablePDF($conn, 'room', $room);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Orario <?php echo htmlspecialchars($room); ?> | <?php echo APP_NAME; ?> <?php echo YEAR; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/timetable.css">
  <link rel="stylesheet" href="css/navbar.css">
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
</head>
<body>
  <div class="navbar">
    <div class="logo"><?php echo APP_NAME; ?> <?php echo YEAR; ?><?php if (DEV_MODE){echo " - SVILUPPO";}?><?php if (isset($_SESSION['admin']) && MAINTENANCE){echo " - MANUTENZIONE";}?></div>
    <div class="links">
      <a href="index.php">Home</a>
      <?php if (PDF_EXPORT):?>
        <a href="?room=<?= $room ?>&pdf=1" target="_blank">Esporta PDF</a>
      <?php endif;?>
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
        $stmt = $conn->prepare("
          SELECT subjects.name AS subject_name, subjects.teacher, classes.name AS class_name
          FROM timetable
          LEFT JOIN subjects ON timetable.subject_id = subjects.id
          LEFT JOIN classes ON timetable.class_id = classes.id
          WHERE subjects.room=? 
            AND timetable.day=? AND timetable.hour=?
        ");
        $stmt->bind_param("ssi", $room, $d, $hnum);
        $stmt->execute();
        $q = $stmt->get_result();

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
        $stmt = $conn->prepare("
          SELECT subjects.name AS subject_name, subjects.teacher, classes.name AS class_name
          FROM timetable
          LEFT JOIN subjects ON timetable.subject_id = subjects.id
          LEFT JOIN classes ON timetable.class_id = classes.id
          WHERE subjects.room=? 
            AND timetable.day=? AND timetable.hour=?
        ");
        $stmt->bind_param("ssi", $room, $d, $hnum);
        $stmt->execute();
        $q = $stmt->get_result();

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

  <p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.qzz.io/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.qzz.io/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
  </p>
</body>
</html>
