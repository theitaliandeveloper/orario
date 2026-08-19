<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.
*/
require_once __DIR__ . "/auth_check.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT id, name FROM classes ORDER BY name ASC");
    $classes = [];
    while ($row = $res->fetch_assoc()) {
        $classes[] = [
            'id' => (int)$row['id'],
            'name' => $row['name']
        ];
    }
    echo json_encode($classes);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? $_POST['name'] ?? '');
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "Nome classe obbligatorio."]);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO classes (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "id" => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Errore nell'inserimento: " . $conn->error]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID classe non valido."]);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM classes WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Errore nell'eliminazione: " . $conn->error]);
    }
    exit;
}
