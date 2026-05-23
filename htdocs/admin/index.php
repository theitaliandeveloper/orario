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
require __DIR__ . "/../lib/variables.php";
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../favicon.svg" type="image/svg+xml">
</head>
<body>
  <!-- Navbar -->
  <div class="navbar">
    <div class="logo"><?php echo APP_NAME; ?> - Admin Dashboard<?php if (DEV_MODE){echo " - SVILUPPO";}?></div>
    <div class="links">
      <a href="/">Torna al sito</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>

  <!-- Contenuto Dashboard -->
  <div class="dashboard">
    <h1>Benvenuto, <?php echo htmlspecialchars($_SESSION['admin']); ?>!</h1>
    <p>
      <a href="classes.php" class="buttons">Gestisci Classi</a>
      <a href="subjects.php" class="buttons">Gestisci Materie</a>
      <a href="timetable.php" class="buttons">Gestisci Orario</a>
      <?php
          if (defined(API_URL) || API_URL != "") {
            echo '<a href="importer.php" class="buttons">Importa Orario</a>';
          }
      ?>
      <?php
      if ($_SESSION['auth_type'] === 'local') {
        echo '<a href="password.php" class="buttons">Cambia Password</a>';
      }
      ?>
      <?php
      if ($_SESSION['auth_type'] === 'local' && $_SESSION['admin'] === 'admin') {
        echo '<a href="users.php" class="buttons">Gestisci Utenti</a>';
      }
      ?>
      <a href="about.php" class="buttons">Informazioni sulla piattaforma</a>
    </p>
    <p class="only-mobile">
      Nota: Questa pagina si vede meglio da computer desktop. Se sei da computer, puoi ignorare questo messaggio.
    </p>
    <p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.qzz.io/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.qzz.io/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
    </p>
  </div>
</body>
</html>
