<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
include("../lib/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name'])) {
    $name = $_POST['name'];
    if (!empty($name)) { $conn->query("INSERT INTO classes (name) VALUES ('$name')"); }
    header("Location: classes.php"); exit;
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM classes WHERE id=$id");
    header("Location: classes.php"); exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestisci Classi</title>
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
    <h1>Gestisci Classi</h1>
    <a href="index.php" class="back-link">⬅ Torna al Dashboard</a>

    <form method="POST">
      <input type="text" name="name" placeholder="Nome Classe" required>
      <button type="submit">Aggiungi</button>
    </form>

    <table>
      <tr><th>ID</th><th>Nome</th><th>Azione</th></tr>
      <?php
      $res = $conn->query("SELECT * FROM classes ORDER BY name ASC");
      while($row=$res->fetch_assoc()){
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['name']}</td>
                <td><a href='classes.php?delete={$row['id']}' class='delete-link' onclick='return confirm(\"Sei sicuro di voler eliminare questa classe?\")'>Elimina</a></td>
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
