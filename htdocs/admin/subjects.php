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
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}
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
if (isset($_POST['update'])) {
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
  <title><?php echo APP_NAME; ?> - Gestisci Materie</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="../css/home.css">
  <link rel="icon" href="../favicon.svg" type="image/svg+xml">
</head>
<body>
  <!-- Navbar -->
  <div class="navbar">
    <div class="logo"><?php echo APP_NAME; ?> - Admin Dashboard<?php if (DEV_MODE){echo " - SVILUPPO";}?></div>
    <div class="links">
      <a href="index.php">Dashboard</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>

  <div class="admin-container">
    <h1>Gestisci Materie</h1>
    <a href="index.php" class="back-link">⬅ Torna al Dashboard</a>
    <?php
    // --- PARTE MODIFICA (FORM CONDIZIONALE) ---
    if (isset($_GET['edit'])) {
      $id = intval($_GET['edit']);
      $stmt = $conn->prepare("SELECT * FROM subjects WHERE id=?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $res = $stmt->get_result();

      if ($res->num_rows > 0) {
        $subject = $res->fetch_assoc();
    ?>
        <div class="edit-section" style="background: #eef2f7; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
          <h3>Modifica materia</h3>
          <form method="post" action="subjects.php">
            <input type="hidden" name="id" value="<?php echo $subject['id']; ?>">
            <input type="text" name="name" value="<?php echo htmlspecialchars($subject['name']); ?>" required placeholder="Materia">
            <input type="text" name="teacher" value="<?php echo htmlspecialchars($subject['teacher']); ?>" required placeholder="Docente">
            <input type="text" name="room" value="<?php echo htmlspecialchars($subject['room']); ?>" placeholder="Laboratorio">
            <button type="submit" name="update" class="cancel-edit">Salva modifiche</button>
            <a class="cancel-edit" href="subjects.php" style="margin-left: 10px;">Annulla</a>
          </form>
        </div>
    <?php
      }
      $stmt->close();
    }
    ?>

    <h2>Aggiungi Nuova Materia</h2>
    <form method="POST" class="add-form">
      <input type="text" name="name" placeholder="Materia (es. Informatica)" required>
      <input type="text" name="teacher" placeholder="Docente (Cognome Nome)" required>
      <input type="text" name="room" placeholder="Laboratorio (opzionale)">
      <button type="submit">Aggiungi</button>
    </form>

    <hr style="margin: 40px 0; border: 0; border-top: 1px solid #ddd;">

    <h2>Elenco Materie e Docenti</h2>

    <?php
    // Ordiniamo prima per materia (name) e poi per docente
    $res = $conn->query("SELECT * FROM subjects ORDER BY name ASC, teacher ASC");

    $current_subject = "";
    $first_iteration = true;

    while ($row = $res->fetch_assoc()) {
      // Se la materia cambia, chiudiamo la griglia precedente e ne apriamo una nuova
      if ($row['name'] !== $current_subject) {
        if (!$first_iteration) {
          echo '</div>'; // Chiude la div .grid
        }
        $current_subject = $row['name'];
        echo "<h3 style='margin-top: 30px; color: #2c3e50; border-left: 5px solid #1f618d; padding-left: 10px;'>" . htmlspecialchars($current_subject) . "</h3>";
        echo '<div class="grid">';
        $first_iteration = false;
      }
    ?>

      <ul>
        <li><b><?php echo htmlspecialchars($row['teacher']); ?></b></li>
        <?php if (!empty($row['room'])): ?>
          <li style="font-size: 0.85em; color: #666;"><?php echo htmlspecialchars($row['room']); ?></li>
        <?php endif; ?>

        <li style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
          <a href="subjects.php?edit=<?php echo $row['id']; ?>" class="edit-btn littlebutton" style="background: #e3f2fd; color: #1976d2; font-size: 0.8em;">Modifica</a>
          <a href="subjects.php?delete=<?php echo $row['id']; ?>"
            onclick="return confirm('Eliminare questo docente?')"
            class="delete-btn littlebutton" style="background: #fbe9e7; color: #d32f2f; font-size: 0.8em;">Elimina</a>
        </li>
      </ul>

    <?php }
    if (!$first_iteration) echo '</div>'; // Chiude l'ultima griglia aperta
    ?>
    <p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.freeddns.org/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
    </p>
  </div>
</body>
</html>
