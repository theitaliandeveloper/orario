<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.
*/
require_once __DIR__ . "/../../lib/db.php";
require_once __DIR__ . "/../../lib/csrf.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$now = time();
if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) {
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(["error" => "Non autorizzato."]);
    exit;
}

$_SESSION['discard_after'] = $now + SESSION_LIFETIME;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    // For non-GET requests (POST, PUT, DELETE), verify CSRF
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        echo json_encode(["error" => "Token CSRF non valido."]);
        exit;
    }
}

header('Content-Type: application/json; charset=utf-8');
