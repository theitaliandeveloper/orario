<?php
include_once __DIR__ . '/../config/config.php';
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    if (DEV_MODE)
        die("[DEBUG] Connessione al database fallita: " . $conn->connect_error);
    else
        die("Connessione al database fallita!");
}
?>
