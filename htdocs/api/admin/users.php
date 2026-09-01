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

if ($_SESSION['auth_type'] !== 'local' || $_SESSION['admin'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["error" => "Non hai i permessi per gestire gli utenti."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $res = $conn->query("SELECT id, username FROM admin ORDER BY id ASC");
    $admins = [];
    while ($row = $res->fetch_assoc()) {
        $admins[] = [
            'id' => (int)$row['id'],
            'username' => $row['username']
        ];
    }
    echo json_encode($admins);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? $_POST['username'] ?? '');
    $password = $input['password'] ?? $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(["error" => "Nome utente e password obbligatori."]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hash);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "id" => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Errore durante l'aggiunta dell'utente: " . $conn->error]);
    }
    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID utente non valido."]);
        exit;
    }

    if ($id === 1) {
        http_response_code(400);
        echo json_encode(["error" => "Non puoi eliminare l'utente di default."]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Errore durante l'eliminazione dell'utente: " . $conn->error]);
    }
    $stmt->close();
    exit;
}
