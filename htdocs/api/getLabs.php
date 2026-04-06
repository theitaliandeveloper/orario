<?php
include("../lib/db.php");
$res = $conn->query("SELECT DISTINCT room FROM subjects WHERE room IS NOT NULL AND room != '' ORDER BY room");
$rooms = [];
while ($row = $res->fetch_assoc()) {
    $rooms[] = $row['room'];
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($rooms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();