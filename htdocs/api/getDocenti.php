<?php
include("../lib/db.php");
$res = $conn->query("SELECT DISTINCT teacher FROM subjects ORDER BY teacher");
$docenti = [];
while ($row = $res->fetch_assoc()) {
    $docenti[] = $row['teacher'];
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($docenti, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();