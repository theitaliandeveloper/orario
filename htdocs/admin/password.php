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
include("../lib/db.php");

if (!isset($_SESSION['admin']) || $_SESSION['auth_type'] != 'local') {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $user = $_SESSION['admin'];

    if ($new !== $confirm) {
        $message = "Le nuove password non coincidono.";
    } else {
        // Recupera hash password attuale
        $stmt = $conn->prepare("SELECT password FROM admin WHERE username = ?");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();

        if ($row && password_verify($old, $row['password'])) {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
            $stmt->bind_param("ss", $newHash, $user);
            $stmt->execute();
            $message = "Password cambiata con successo.";
        } else {
            $message = "Password attuale errata.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Cambia Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="../favicon.svg" type="image/svg+xml">
</head>
<body>

<div class="navbar">
    <div class="logo"><?php echo APP_NAME; ?> - Admin Dashboard</div>
    <div class="links">
        <a href="index.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="admin-container">
    <h1>Cambia Password</h1>
    <a href="index.php" class="back-link">⬅ Torna al Dashboard</a>

    <form method="POST">
        <label>Password attuale:<br>
            <input type="password" name="old_password" required>
        </label><br><br>

        <label>Nuova password:<br>
            <input type="password" name="new_password" required>
        </label><br><br>

        <label>Conferma nuova password:<br>
            <input type="password" name="confirm_password" required>
        </label><br><br>

        <button type="submit">Cambia password</button>
    </form>
        <?php if ($message): ?>
        <p style="color:<?php echo strpos($message,'successo')!==false ? 'green':'red'; ?>;"><?php echo $message; ?></p>
    <?php endif; ?>
<p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.freeddns.org/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
</p>
</div>

</body>
</html>
