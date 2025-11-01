<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
include("../lib/db.php");

// FIX: Usa prepared statements per sicurezza
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name']) && !isset($_POST['update'])) {
    $name = $_POST['name']; 
    $teacher = $_POST['teacher']; 
    $room = $_POST['room'];
    
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO subjects (name, teacher, room) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $teacher, $room);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: subjects.php"); 
    exit;
}

// FIX: Aggiunto redirect dopo update
if(isset($_POST['update'])){
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $teacher = $_POST['teacher'];
    $room = $_POST['room'];

    $stmt = $conn->prepare("UPDATE subjects SET name=?, teacher=?, room=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $teacher, $room, $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: subjects.php");
    exit;
}

// FIX: Usa prepared statement anche per delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: subjects.php"); 
    exit;
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

  <?php
  // Mostra form di modifica solo se richiesto
  if(isset($_GET['edit'])){
      $id = intval($_GET['edit']);
      $stmt = $conn->prepare("SELECT * FROM subjects WHERE id=?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $res = $stmt->get_result();
      
      if($res->num_rows > 0){
          $subject = $res->fetch_assoc();
          ?>
          <h3>Modifica materia</h3>
          <form method="post" action="subjects.php">
              <input type="hidden" name="id" value="<?php echo $subject['id']; ?>">
              
              <label>Materia:</label>
              <input type="text" name="name" value="<?php echo htmlspecialchars($subject['name']); ?>" required><br>
              
              <label>Docente:</label>
              <input type="text" name="teacher" value="<?php echo htmlspecialchars($subject['teacher']); ?>" required><br>
              
              <label>Laboratorio (opzionale):</label>
              <input type="text" name="room" value="<?php echo htmlspecialchars($subject['room']); ?>"><br>
              
              <button type="submit" name="update">Salva modifiche</button>
              <a class="cancel-edit" href="subjects.php" style="margin-left: 10px;">Annulla</a>
          </form>
          <hr>
          <?php
      }
      $stmt->close();
  }
  ?>

  <h2>Aggiungi Nuova Materia</h2>
  <form method="POST">
    <input type="text" name="name" placeholder="Materia" required>
    <input type="text" name="teacher" placeholder="Docente" required>
    <input type="text" name="room" placeholder="Laboratorio (opzionale)">
    <button type="submit">Aggiungi</button>
  </form>

  <h2>Elenco Materie</h2>
  <table>
    <tr>
      <th>ID</th>
      <th>Materia</th>
      <th>Docente</th>
      <th>Laboratorio</th>
      <th>Azione</th>
    </tr>
    <?php
    $res = $conn->query("SELECT * FROM subjects ORDER BY name ASC");
    while($row=$res->fetch_assoc()){
      echo "<tr>
              <td>{$row['id']}</td>
              <td>" . htmlspecialchars($row['name']) . "</td>
              <td>" . htmlspecialchars($row['teacher']) . "</td>
              <td>" . htmlspecialchars($row['room']) . "</td>
              <td>
                <a href='subjects.php?edit={$row['id']}' class='edit-link'>Modifica</a> | 
                <a href='subjects.php?delete={$row['id']}' class='delete-link' onclick='return confirm(\"Sei sicuro di voler eliminare questa materia?\")'>Elimina</a>
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
