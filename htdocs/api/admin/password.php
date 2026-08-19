<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.
*/
require_once __DIR__ . "/auth_check.php";

if ($_SESSION['auth_type'] !== 'local') {
    http_response_code(403);
    echo json_encode(["error" => "Cambio password supportato solo per autenticazione locale."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Metodo non consentito."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$old = $input['old_password'] ?? $_POST['old_password'] ?? '';
$new = $input['new_password'] ?? $_POST['new_password'] ?? '';
$confirm = $input['confirm_password'] ?? $_POST['confirm_password'] ?? '';
$user = $_SESSION['admin'];

if (empty($old) || empty($new) || empty($confirm)) {
    http_response_code(400);
    echo json_encode(["error" => "Compila tutti i campi."]);
    exit;
}

if ($new !== $confirm) {
    http_response_code(400);
    echo json_encode(["error" => "Le nuove password non coincidono."]);
    exit;
}

$stmt = $conn->prepare("SELECT password FROM admin WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if ($row && password_verify($old, $row['password'])) {
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $newHash, $user);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Password cambiata con successo."]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Errore durante l'aggiornamento della password: " . $conn->error]);
    }
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(["error" => "Password attuale errata."]);
}
exit;
