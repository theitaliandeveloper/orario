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
include("../lib/csrf.php");
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

if (!isset($_GET['class_id'])) {
    header("Location: index.php");
    exit;
}

$class_id = intval($_GET['class_id']);
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$class = $res->fetch_assoc();

// Helper: dato un risultato query, estrae subject, teachers[], rooms[]
function parseRows($q) {
    $subject = null;
    $teachers = [];
    $rooms = [];

    while ($row = $q->fetch_assoc()) {
        if ($subject === null) {
            $subject = $row['name'];
        }
        if (!empty($row['teacher']) && !in_array($row['teacher'], $teachers)) {
            $teachers[] = $row['teacher'];
        }
        if (!empty($row['room']) && !in_array($row['room'], $rooms)) {
            $rooms[] = $row['room'];
        }
    }

    return [$subject, $teachers, $rooms];
}

// Helper: array → stringa "A, B e C"
function joinList($arr) {
    if (empty($arr)) return '';
    if (count($arr) === 1) return $arr[0];
    $last = array_pop($arr);
    return implode(', ', $arr) . ' e ' . $last;
}

if (isset($_GET['json']) && $_GET['json'] == '1') {
    if (OPEN_DATA) {
      header('Content-Type: application/json; charset=utf-8');

      $timetable = [];

      foreach ($days as $d) {
          $d_clean = str_replace(
              ['à','è','é','ì','ò','ù'],
              ['a','e','e','i','o','u'],
              $d
          );
          $timetable[$d_clean] = [];

          foreach ($hours as $hnum => $hlabel) {
              $stmt = $conn->prepare("SELECT subjects.name, subjects.teacher, subjects.room 
                                FROM timetable 
                                LEFT JOIN subjects ON timetable.subject_id = subjects.id 
                                WHERE class_id=? AND day=? AND hour=?");
              $stmt->bind_param("isi", $class_id, $d, $hnum);
              $stmt->execute();
              $q = $stmt->get_result();

              if ($q->num_rows > 0) {
                  [$subject, $teachers, $rooms] = parseRows($q);

                  $timetable[$d_clean][$hnum] = [
                      'hour'     => $hnum,
                      'time'     => strip_tags($hlabel),
                      'subject'  => $subject,
                      'teachers' => $teachers,
                      'rooms'    => $rooms   // <-- ora è un array
                  ];
              } else {
                  $timetable[$d_clean][$hnum] = [
                      'hour'     => $hnum,
                      'time'     => strip_tags($hlabel),
                      'subject'  => null,
                      'teachers' => [],
                      'rooms'    => []
                  ];
              }
          }
      }

      echo json_encode([
          'class_id'   => $class_id,
          'class_name' => $class['name'],
          'timetable'  => $timetable
      ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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

if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && PDF_EXPORT) {
    require_once 'lib/pdf.php';
    exportTimetablePDF($conn, 'class', $class_id);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Orario <?php echo htmlspecialchars($class['name']); ?> | <?php echo APP_NAME; ?> <?php echo YEAR; ?></title>
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
        <a href="?class_id=<?= $class_id ?>&pdf=1" target="_blank">Esporta PDF</a>
      <?php endif;?>
    </div>
  </div>

  <h1>Orario della classe <?php echo htmlspecialchars($class['name']); ?></h1>

  <!-- Visualizzazione Desktop -->
  <table class="desktop-schedule">
    <tr>
      <th></th>
      <?php foreach ($days as $d) echo "<th>$d</th>"; ?>
    </tr>

    <?php foreach ($hours as $hnum => $hlabel): ?>
    <tr>
      <td><?= $hlabel ?></td>
      <?php foreach ($days as $d): ?>
        <?php
        $stmt = $conn->prepare("SELECT subjects.name, subjects.teacher, subjects.room 
                           FROM timetable 
                           LEFT JOIN subjects ON timetable.subject_id = subjects.id 
                           WHERE class_id=? AND day=? AND hour=?");
        $stmt->bind_param("isi", $class_id, $d, $hnum);
        $stmt->execute();
        $q = $stmt->get_result();

        if ($q->num_rows > 0):
            [$subject, $teachers, $rooms] = parseRows($q);
            $teachers_str = joinList($teachers);
            $rooms_str    = joinList($rooms);
        ?>
          <td data-label="<?= htmlspecialchars($d) ?>">
            <div class="subject"><?= htmlspecialchars($subject) ?></div>
            <div class="teacher"><?= htmlspecialchars($teachers_str) ?></div>
            <?php if (!empty($rooms_str)): ?>
              <div class="room"><?= htmlspecialchars($rooms_str) ?></div>
            <?php endif; ?>
          </td>
        <?php else: ?>
          <td data-label="<?= htmlspecialchars($d) ?>"></td>
        <?php endif; ?>
      <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
  </table>

  <!-- Visualizzazione Mobile -->
  <div class="mobile-schedule">
  <?php foreach ($days as $d): ?>
    <div class="day">
      <h2><?= htmlspecialchars($d) ?></h2>
      <?php foreach ($hours as $hnum => $hlabel): ?>
        <?php
        $stmt = $conn->prepare("SELECT subjects.name, subjects.teacher, subjects.room 
                           FROM timetable 
                           LEFT JOIN subjects ON timetable.subject_id = subjects.id 
                           WHERE class_id=? AND day=? AND hour=?");
        $stmt->bind_param("isi", $class_id, $d, $hnum);
        $stmt->execute();
        $q = $stmt->get_result();

        if ($q->num_rows > 0):
            [$subject, $teachers, $rooms] = parseRows($q);
            $teachers_str = joinList($teachers);
            $rooms_str    = joinList($rooms);
        ?>
          <div class="lesson">
            <div class="hour"><?= strip_tags($hlabel) ?></div>
            <div class="subject"><?= htmlspecialchars($subject) ?></div>
            <div class="teacher"><?= htmlspecialchars($teachers_str) ?></div>
            <?php if (!empty($rooms_str)): ?>
              <div class="room"><?= htmlspecialchars($rooms_str) ?></div>
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

  <p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.qzz.io/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.qzz.io/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
  </p>
</body>
</html>
