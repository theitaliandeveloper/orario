<?php
include("lib/db.php");
include("config/config.php");
$class_id = intval($_GET['class_id']);
$class = $conn->query("SELECT * FROM classes WHERE id=$class_id")->fetch_assoc();
$days = ["Lunedì","Martedì","Mercoledì","Giovedì","Venerdì","Sabato"];
$hours = [
  1 => "Prima ora<br>7:50 - 8:50",
  2 => "Seconda ora<br>8:50 - 9:45",
  3 => "Terza ora<br>9:55 - 10:50",
  4 => "Quarta ora<br>10:50 - 11:45",
  5 => "Quinta ora<br>11:55 - 12:50",
  6 => "Sesta ora<br>12:50 - 13:50"
];
if (!isset($_GET['class_id'])) {
    header("Location: index.php");
    exit;
}

$class_id = intval($_GET['class_id']); // sicurezza
$res = $conn->query("SELECT id FROM classes WHERE id = $class_id LIMIT 1");

if ($res->num_rows === 0) {
    // Classe non trovata
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Orario <?php echo $class['name']; ?></title>
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
  <h1>Orario della classe <?php echo $class['name']; ?></h1>
  <table>
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
          $row = $q->fetch_assoc();
          $subject = $row['name'];
          $room = $row['room'];
          
          // metto il primo docente
          $teachers = [$row['teacher']];
          
          // aggiungo eventuali altri docenti
          while($row = $q->fetch_assoc()){
            $teachers[] = $row['teacher'];
          }

          // se più docenti -> unisci con virgola e "e" finale
          if(count($teachers) > 1){
            $last = array_pop($teachers);
            $teachers_list = implode(", ", $teachers) . " e " . $last;
          } else {
            $teachers_list = $teachers[0];
          }

          echo "<td data-label='$d'>
                  <div class='subject'>$subject</div>
                  <div class='teacher'>$teachers_list</div>
                  <div class='room'>$room</div>
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
