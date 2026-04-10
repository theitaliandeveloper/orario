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
include("../lib/db.php");
if (OPEN_DATA) {
    $res = $conn->query("SELECT name FROM classes ORDER BY name");
    $classi = [];
    while ($row = $res->fetch_assoc()) {
        $classi[] = $row['name'];
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header("Access-Control-Allow-Headers: X-Requested-With");
    echo json_encode($classi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
} else {
    http_response_code(403);
    if (DEV_MODE) {
        echo "Non puoi accedere a questa API perchè gli Open Data in questa istanza sono disattivati. Per attivarli, apri il file config.php e modifica OPEN_DATA su true.";
    }
    else {
        echo "Non puoi accedere a questa API perchè non hai i permessi necessari per farlo.";
    }
    exit();
}