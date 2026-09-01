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
    $res = $conn->query("SELECT id, name, username FROM teachers ORDER BY name ASC");
    $teachers = [];
    while ($row = $res->fetch_assoc()) {
        $teachers[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'username' => $row['username'] ?? ''
        ];
    }
    echo json_encode($teachers);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$name = trim((string)($input['name'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Nome docente obbligatorio.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO teachers (name) VALUES (?)");
    $stmt->bind_param('s', $name);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        http_response_code($conn->errno === 1062 ? 409 : 500);
        echo json_encode(['error' => $conn->errno === 1062 ? 'Docente già presente.' : 'Errore nell\'inserimento: ' . $conn->error]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0 || $name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'ID e nome docente sono obbligatori.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE teachers SET name = ? WHERE id = ?");
    $stmt->bind_param('si', $name, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code($conn->errno === 1062 ? 409 : 500);
        echo json_encode(['error' => $conn->errno === 1062 ? 'Docente già presente.' : 'Errore nell\'aggiornamento: ' . $conn->error]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID docente non valido.']);
        exit;
    }

    $used = $conn->prepare("SELECT 1 FROM timetable_lesson_teachers WHERE teacher_id = ? LIMIT 1");
    $used->bind_param('i', $id);
    $used->execute();
    $inUse = $used->get_result()->num_rows > 0;
    $used->close();
    if ($inUse) {
        http_response_code(409);
        echo json_encode(['error' => 'Docente utilizzato nell\'orario.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Errore nell\'eliminazione: ' . $conn->error]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Metodo non consentito.']);
