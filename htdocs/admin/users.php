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

if (!isset($_SESSION['admin']) || $_SESSION['auth_type'] != 'local' || $_SESSION['admin'] != 'admin') {
    header("Location: login.php");
    exit;
}

$message = "";

// Add admin
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hash);
        if ($stmt->execute()) {
            $message = "Utente admin aggiunto con successo.";
        } else {
            $message = "Errore durante l'aggiunta: " . $conn->error;
        }
    } else {
        $message = "Compila tutti i campi.";
    }
}

// Delete admin
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id != 1) { // proteggi super admin
        $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = "Utente admin rimosso.";
    } else {
        $message = "Non puoi eliminare il super admin.";
    }
}

// Fetch admins
$result = $conn->query("SELECT id, username FROM admin ORDER BY id ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Gestione Admin</title>
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
    <h1>Gestione Utenti</h1>
    <a href="index.php" class="back-link">⬅ Torna al Dashboard</a>

    <?php if ($message): ?>
        <p style="color:<?php echo strpos($message,'successo')!==false ? 'green':'red'; ?>;">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <h2>Utenti Attivi</h2>
    <div class="table-container">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Azione</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td data-label="ID"><?php echo $row['id']; ?></td>
                        <td data-label="Username"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td data-label="Azione">
                            <?php if ($row['id'] != 1): ?>
                                <a href="?delete=<?php echo $row['id']; ?>" 
                                    onclick="return confirm('Vuoi davvero eliminare questo amministratore?')"
                                    style="color:red;">Elimina</a>
                            <?php else: ?>
                                <em>Super Admin</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <h2>Aggiungi nuovo utente</h2>
    <form method="POST">
        <label>Username:<br>
            <input type="text" name="username" required>
        </label><br><br>
        <label>Password:<br>
            <input type="password" name="password" required>
        </label><br><br>
        <button type="submit" name="add_user">Aggiungi</button>
    </form>
    <p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.freeddns.org/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
    </p>
</div>

</body>
</html>
