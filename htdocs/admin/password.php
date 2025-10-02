<?php
session_start();
include("../lib/db.php");
include("../config/config.php");

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
    <title>Cambia Password</title>
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
</div>

</body>
</html>
