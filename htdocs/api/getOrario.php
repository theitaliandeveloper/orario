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
require_once __DIR__ . "/../lib/json.php";
require_once __DIR__ . "/../lib/pdf.php";

if (!isset($_GET["type"]) || !isset($_GET["id"])) {
    http_response_code(400);
    echo json_encode(["error" => "Parametri mancanti."]);
    exit();
}

$type = $_GET["type"];
$id = $_GET["id"];
$dl = $_GET["dl"];

if (!in_array($type, ["classe", "docente", "laboratorio", "class", "teacher", "room"], true)) {
    http_response_code(400);
    echo json_encode(["error" => "Tipo non valido."]);
    exit();
}

if ($dl == 1) {
    if (PDF_EXPORT) {
        exportTimetablePDF($conn, $type, $id);
    } else {
        http_response_code(403);
        echo json_encode(["error" => "PDF exporting is disabled in this instance."]);
    }
} else {
    exportTimetableJSON($conn, $type, $id);
}
