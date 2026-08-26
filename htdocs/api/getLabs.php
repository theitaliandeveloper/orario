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
require_once __DIR__ . "/../lib/db.php";
$res = $conn->query(
    "SELECT DISTINCT r.name AS room
     FROM rooms r
     INNER JOIN timetable_lesson_rooms tlr ON tlr.room_id = r.id
     ORDER BY r.name"
);
$rooms = [];
while ($row = $res->fetch_assoc()) {
    $rooms[] = $row['room'];
}
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header("Access-Control-Allow-Headers: X-API-Key, X-Requested-With");
echo json_encode($rooms, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();