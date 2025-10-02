<?php
$host = "<MYSQL_HOST>";
$user = "<MYSQL_USER>";
$pass = "<MYSQL_PASSWORD>";
$dbname = "school_timetable";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
