<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
include("../db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_id = $_POST['class_id'];
    $day = $_POST['day'];
    $hour = $_POST['hour'];
    $subject_id = $_POST['subject_id'];
    $conn->query("INSERT INTO timetable (class_id,day,hour,subject_id) VALUES ($class_id,'$day',$hour,$subject_id)");
    header("Location: timetable.php"); exit;
}
?>
<?php
include("../db.php"); // o il percorso corretto al tuo DB

// Funzione per eliminare una voce del timetable
function deleteTimetableEntry($conn, $id) {
    $id = intval($id); // sicurezza
    $conn->query("DELETE FROM timetable WHERE id=$id");
}

// Se è stato cliccato il link "Elimina"
if(isset($_GET['delete'])) {
    deleteTimetableEntry($conn, $_GET['delete']);
    // Dopo l'eliminazione, reindirizza per evitare duplicazioni
    header("Location: timetable.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestisci Orario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<!-- Navbar -->
  <div class="navbar">
    <div class="logo">Admin Dashboard</div>
    <div class="links">
      <a href="index.php">Dashboard</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>

<div class="admin-container">
  <h1>Gestisci Orario</h1>
  <a href="index.php" class="back-link">⬅ Torna al Dashboard</a>

  <form method="POST" autocomplete="off">
    Classe: 
    <select name="class_id" required>
      <option value="" selected disabled>--Scegli un'opzione--</option>
      <?php 
      $res=$conn->query("SELECT * FROM classes ORDER BY name ASC"); 
      while($r=$res->fetch_assoc()) 
        echo "<option value='{$r['id']}'>{$r['name']}</option>"; 
      ?>
    </select>

    Giorno: 
    <select name="day" required>
      <option value="" selected disabled>--Scegli un'opzione--</option>
      <option>Lunedì</option><option>Martedì</option><option>Mercoledì</option>
      <option>Giovedì</option><option>Venerdì</option><option>Sabato</option>
    </select>

    Ora: 
    <select name="hour" required>
      <option value="" selected disabled>--Scegli un'opzione--</option>
      <option value="1">1</option><option value="2">2</option><option value="3">3</option>
      <option value="4">4</option><option value="5">5</option><option value="6">6</option>
    </select>

    Materia: 
    <select name="subject_id" required>
      <option value="" selected disabled>--Scegli un'opzione--</option>
      <?php 
  $res = $conn->query("SELECT * FROM subjects ORDER BY name ASC"); 
  while($r = $res->fetch_assoc()) {
      $label = $r['name'];
      if(!empty($r['teacher'])) {
          $label .= " (" . $r['teacher'] . ")";
      }
        if(!empty($r['room'])) {
            $label .= " (" . $r['room'] . ")";
        }
      echo "<option value='{$r['id']}'>" . htmlspecialchars($label) . "</option>";
  }
  ?>
    </select>

    <button type="submit">Aggiungi</button>
  </form>
<?php
// Recupera tutte le entry del timetable
$res = $conn->query("SELECT timetable.id, classes.name AS class_name, timetable.day, timetable.hour, subjects.name AS subject_name, subjects.teacher as teacher, subjects.room as room
                     FROM timetable
                     LEFT JOIN classes ON timetable.class_id = classes.id
                     LEFT JOIN subjects ON timetable.subject_id = subjects.id
                     ORDER BY class_name, day, hour");
?>

<h2>Orario Inserito</h2>
<div class="table-container">
<table class="responsive-table" border="1" cellpadding="5" style="border-collapse:collapse; width:100%; max-width:1000px; margin:auto;">
    <tr>
        <th>Classe</th>
        <th>Giorno</th>
        <th>Ora</th>
        <th>Materia</th>
        <th>Azione</th>
    </tr>

    <?php while($row = $res->fetch_assoc()): ?>
        <tr>
            <td data-label="Classe"><span><?php echo htmlspecialchars($row['class_name']); ?></span></td>
            <td data-label="Giorno"><span><?php echo htmlspecialchars($row['day']); ?></span></td>
            <td data-label="Ora"><span><?php echo htmlspecialchars($row['hour']); ?></span></td>
            <td data-label="Materia"><span><?php 
        echo htmlspecialchars($row['subject_name']);
        if(!empty($row['teacher'])) {
            echo " (" . htmlspecialchars($row['teacher']) . ")";
        }
//	if(!empty($row['room'])) {
//	    echo " (" . htmlspecialchars($row['room']) . ")";
//	}
        ?></span></td>
            <td data-label="Azione"><span>
                <a href="timetable.php?delete=<?php echo $row['id']; ?>" 
                   onclick="return confirm('Sei sicuro di voler eliminare questa voce?');" class='delete-link'>
                   Elimina
                </a>
            </span></td>
        </tr>
    <?php endwhile; ?>
</table>
</div>
    <p>
      Nota: Questa pagina si vede meglio da computer desktop. Se sei da computer, puoi ignorare questo messaggio.
    </p>
    <p style="text-align: center;">Copyright (C) 2025 EmmeV. All rights reserved.</p>
</div>

</body>
</html>
