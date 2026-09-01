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
