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
require_once __DIR__ . "/lib/variables.php";
if (!MAINTENANCE) {
  header('Location: index.php');
}
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo APP_NAME; ?> - A.S. <?php echo YEAR; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/navbar.css">
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
</head>
<body>
  <div class="navbar">
    <div class="logo"><?php echo APP_NAME; ?> <?php echo YEAR; ?><?php if (DEV_MODE){echo " - SVILUPPO";}?></div>
    <div class="links">
      <a href="index.php">Home</a>
      <a href="admin/index.php">Admin</a>
    </div>
  </div>
  <h1><?php echo APP_NAME; ?></h1>
  <p class="centered">
    <img src="assets/wip.jpg" alt="Caricamento in corso..." width="40%">
  </p>
  <p class="centered" style="font-size:22px; color: red;">
    Il sito è momentaneamente in manutenzione, ci scusiamo per il disagio.
  </p>
  <p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-<?php echo date("Y"); ?> EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.com/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.com/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
  </p>
</body>
</html>
