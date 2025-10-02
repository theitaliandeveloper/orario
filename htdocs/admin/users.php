<?php
session_start();
include("../db.php");

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
    <title>Gestione Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <div class="logo">Admin Dashboard</div>
    <div class="links">
        <a href="index.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="admin-container">
    <h1>Gestione Amministratori</h1>
    <a href="index.php" class="back-link">⬅ Torna al Dashboard</a>

    <?php if ($message): ?>
        <p style="color:<?php echo strpos($message,'successo')!==false ? 'green':'red'; ?>;">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <h2>Utenti Attivi</h2>
    <table border="1" cellspacing="0" cellpadding="6" width="100%">
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
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td>
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

    <h2>Aggiungi Nuovo Admin</h2>
    <form method="POST">
        <label>Username:<br>
            <input type="text" name="username" required>
        </label><br><br>
        <label>Password:<br>
            <input type="password" name="password" required>
        </label><br><br>
        <button type="submit" name="add_user">Aggiungi</button>
    </form>
</div>

</body>
</html>
