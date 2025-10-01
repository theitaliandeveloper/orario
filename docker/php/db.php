<?php
$host = "db";
$user = "orario";
$pass = "orario";
$dbname = "school_timetable";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
