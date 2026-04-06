<?php
include("../lib/db.php");
$res = $conn->query("SELECT name FROM classes ORDER BY name");
$classi = [];
while ($row = $res->fetch_assoc()) {
    $classi[] = $row['name'];
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($classi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();