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
include_once __DIR__ . '/../config/config.php';
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Navbar -->
  <div class="navbar">
    <div class="logo">Admin Dashboard</div>
    <div class="links">
      <a href="/">Torna al sito</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>

  <!-- Contenuto Dashboard -->
  <div class="dashboard">
    <h1>Benvenuto, <?php echo htmlspecialchars($_SESSION['admin']); ?>!</h1>
    <p>
      <a href="classes.php">Gestisci Classi</a>
      <a href="subjects.php">Gestisci Materie</a>
      <a href="timetable.php">Gestisci Orario</a>
      <?php
          if (defined(API_URL) || API_URL != "") {
            echo '<a href="importer.php" style="background: #28a745;">🔄 Importa Orario</a>';
          }
      ?>
      <?php
      if ($_SESSION['auth_type'] === 'local') {
        echo '<a href="password.php">Cambia Password</a>';
      }
      ?>
      <?php
      if ($_SESSION['auth_type'] === 'local' && $_SESSION['admin'] === 'admin') {
        echo '<a href="users.php">Gestisci Amministratori</a>';
      }
      ?>
    </p>
    <p>
      Nota: Questa pagina si vede meglio da computer desktop. Se sei da computer, puoi ignorare questo messaggio.
    </p>
    <p style="text-align: center;">Copyright (C) 2025 EmmeV. - Released under <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank">GNU AGPL 3.0 License</a>.</p>
  </div>
</body>
</html>
