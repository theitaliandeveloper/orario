<?php
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
    <h1>Benvenuto nel pannello di amministrazione!</h1>
    <p>
      <a href="classes.php">Gestisci Classi</a>
      <a href="subjects.php">Gestisci Materie</a>
      <a href="timetable.php">Gestisci Orario</a>
      <!--<a href="logout.php">Logout</a>-->
    </p>
    <p>
      Nota: Questa pagina si vede meglio da computer desktop. Se sei da computer, puoi ignorare questo messaggio.
    </p>
    <p style="text-align: center;">Copyright (C) 2025 EmmeV. All rights reserved.</p>
  </div>
</body>
</html>

