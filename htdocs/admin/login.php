<?php
session_start();
include("../lib/db.php");
include("../config/config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin'] = $row['username'];
            $_SESSION['auth_type'] = 'local';
            header("Location: index.php");
            exit;
        }
    }
    $error = "Credenziali non valide";
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <div class="navbar">
    <div class="logo">Admin Dashboard</div>
    <div class="links">
      <a href="/">Torna al sito</a>
    </div>
  </div>

  <!-- Container login -->
  <div class="login-container">
    <h1>Login Admin</h1>
    <form method="post">
      <input type="text" name="username" placeholder="Username" required><br>
      <input type="password" name="password" placeholder="Password" required><br>
      <button type="submit">Login</button>
    </form>
    <?php if(isset($error)) echo "<br><div class='error'>$error</div>"; ?>
  </div>
<p style="text-align: center;">Copyright (C) 2025 EmmeV. - Released under <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank">GNU AGPL 3.0 License</a>.</p>
</body>
</html>
