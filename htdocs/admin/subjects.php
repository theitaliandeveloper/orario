<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
include("../lib/db.php");
include("../config/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
    $name = $_POST['name']; 
    $teacher = $_POST['teacher']; 
    $room = $_POST['room'];
    if (!empty($name)) { 
        $conn->query("INSERT INTO subjects (name,teacher,room) VALUES ('$name','$teacher','$room')"); 
    }
    header("Location: subjects.php"); exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM subjects WHERE id=$id");
    header("Location: subjects.php"); exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestisci Materie</title>
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
  <h1>Gestisci Materie</h1>
  <a href="index.php" class="back-link">⬅ Torna al Dashboard</a>

  <form method="POST">
    <input type="text" name="name" placeholder="Materia" required>
    <input type="text" name="teacher" placeholder="Docente" required>
    <input type="text" name="room" placeholder="Laboratorio (opzionale)">
    <button type="submit">Aggiungi</button>
  </form>
  <?php
// 1. Aggiornamento dati
if(isset($_POST['update'])){
    $id = intval($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    $teacher = $conn->real_escape_string($_POST['teacher']);
    $room = $conn->real_escape_string($_POST['room']);

    $conn->query("UPDATE subjects 
                  SET name='$name', teacher='$teacher', room='$room' 
                  WHERE id=$id");
}
// 2. Mostrare il form se edit richiesto
if(isset($_GET['edit'])){
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM subjects WHERE id=$id");
    if($res->num_rows > 0){
        $subject = $res->fetch_assoc();
        ?>
        <h3>Modifica materia</h3>
        <form method="post" action="subjects.php">
            <input type="hidden" name="id" value="<?php echo $subject['id']; ?>">
            
            <label>Materia:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($subject['name']); ?>"><br>
            
            <label>Docente:</label>
            <input type="text" name="teacher" value="<?php echo htmlspecialchars($subject['teacher']); ?>"><br>
            
            <label>Aula:</label>
            <input type="text" name="room" value="<?php echo htmlspecialchars($subject['room']); ?>"><br>
            
            <button type="submit" name="update">Salva modifiche</button>
        </form>
        <?php
    }
}
?>
  <table>
    <tr>
      <th>ID</th>
      <th>Materia</th>
      <th>Docente</th>
      <th>Aula</th>
      <th>Azione</th>
    </tr>
    <?php
    $res = $conn->query("SELECT * FROM subjects ORDER BY name ASC");
    while($row=$res->fetch_assoc()){
      echo "<tr>
              <td>{$row['id']}</td>
              <td>{$row['name']}</td>
              <td>{$row['teacher']}</td>
              <td>{$row['room']}</td>
              <td>
                <a href='subjects.php?edit={$row['id']}' class='edit-link'>Modifica</a> | 
                <a href='subjects.php?delete={$row['id']}' class='delete-link'>Elimina</a>
              </td>
            </tr>";
    }
    ?>
  </table>
    <p>
      Nota: Questa pagina si vede meglio da computer desktop. Se sei da computer, puoi ignorare questo messaggio.
    </p>
    <p style="text-align: center;">Copyright (C) 2025 EmmeV. - Released under <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank">GNU AGPL 3.0 License</a>.</p>
</div>

</body>
</html>
