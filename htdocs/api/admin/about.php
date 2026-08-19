<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.
*/
require_once __DIR__ . "/auth_check.php";

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Metodo non consentito."]);
    exit;
}

$classesCount = 0;
if ($res = $conn->query("SELECT COUNT(*) as cnt FROM classes")) {
    $classesCount = (int)$res->fetch_assoc()['cnt'];
}

$subjectsCount = 0;
if ($res = $conn->query("SELECT COUNT(*) as cnt FROM subjects")) {
    $subjectsCount = (int)$res->fetch_assoc()['cnt'];
}

$timetableCount = 0;
if ($res = $conn->query("SELECT COUNT(*) as cnt FROM timetable")) {
    $timetableCount = (int)$res->fetch_assoc()['cnt'];
}

$adminsCount = 0;
if ($_SESSION['auth_type'] == 'local') {
    if ($res = $conn->query("SELECT COUNT(*) as cnt FROM admin")) {
        $adminsCount = (int)$res->fetch_assoc()['cnt'];
    }
}

$dbVersion = $conn->server_info;
$dbSizeResult = $conn->query("SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
$dbSizeMB = $dbSizeResult ? ($dbSizeResult->fetch_assoc()['size_mb'] ?? 0) : 0;

$memoryLimit = ini_get('memory_limit');
$extensions = get_loaded_extensions();
natcasesort($extensions);

echo json_encode([
    'classesCount' => $classesCount,
    'subjectsCount' => $subjectsCount,
    'timetableCount' => $timetableCount,
    'adminsCount' => $adminsCount,
    'authType' => $_SESSION['auth_type'],
    'dbVersion' => $dbVersion,
    'dbSizeMB' => round($dbSizeMB, 2),
    'memoryLimit' => $memoryLimit,
    'phpVersion' => PHP_VERSION,
    'phpDebug' => defined('PHP_DEBUG') && PHP_DEBUG,
    'os' => php_uname(),
    'sessionLifetime' => SESSION_LIFETIME,
    'version' => VERSION,
    'maintenance' => MAINTENANCE,
    'extensions' => array_values($extensions)
]);
exit;
