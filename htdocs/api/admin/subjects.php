<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.
*/
require_once __DIR__ . "/auth_check.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT id, name, teacher, room FROM subjects ORDER BY name ASC");
    $subjects = [];
    while ($row = $res->fetch_assoc()) {
        $subjects[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'teacher' => $row['teacher'],
            'room' => $row['room']
        ];
    }
    echo json_encode($subjects);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? $_POST['name'] ?? '');
    $teacher = trim($input['teacher'] ?? $_POST['teacher'] ?? '');
    $room = trim($input['room'] ?? $_POST['room'] ?? '');

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "Nome materia obbligatorio."]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO subjects (name, teacher, room) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $teacher, $room);
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
    $teacher = trim($input['teacher'] ?? '');
    $room = trim($input['room'] ?? '');

    if ($id <= 0 || empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "ID e nome materia sono obbligatori."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE subjects SET name=?, teacher=?, room=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $teacher, $room, $id);
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
