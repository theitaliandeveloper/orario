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

require_once __DIR__ . "/auth_check.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT id, name FROM subjects ORDER BY name ASC");
    $subjects = [];
    while ($row = $res->fetch_assoc()) {
        $subjects[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'teacher' => '',
            'room' => ''
        ];
    }
    echo json_encode($subjects);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? $_POST['name'] ?? '');

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "Materia obbligatoria."]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO subjects (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "id" => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Errore nell'inserimento: " . $conn->error]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');

    if ($id <= 0 || empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "ID e materia sono obbligatori."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE subjects SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Errore nell'aggiornamento: " . $conn->error]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID materia non valido."]);
        exit;
    }

    $used = $conn->prepare("SELECT 1 FROM timetable_lessons WHERE subject_id = ? LIMIT 1");
    $used->bind_param("i", $id);
    $used->execute();
    $inUse = $used->get_result()->num_rows > 0;
    $used->close();

    if ($inUse) {
        http_response_code(409);
        echo json_encode(["error" => "Materia in uso nell'orario: rimuovila prima dalle classi."]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM subjects WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Errore nell'eliminazione: " . $conn->error]);
    }
    exit;
}
