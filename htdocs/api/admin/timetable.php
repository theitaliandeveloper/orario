<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.
*/
require_once __DIR__ . "/auth_check.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $class_id = intval($_GET['class_id'] ?? 0);
    $timetable = [];
    if ($class_id > 0) {
        $stmt = $conn->prepare("SELECT day, hour, subject_id FROM timetable WHERE class_id=?");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $timetable[] = [
                'day' => $row['day'],
                'hour' => (int)$row['hour'],
                'subject_id' => (int)$row['subject_id']
            ];
        }
        $stmt->close();
    }
    echo json_encode($timetable);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $class_id = intval($input['class_id'] ?? 0);
    if ($class_id <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "ID classe non valido."]);
        exit;
    }
    
    $conn->begin_transaction();
    try {
        $stmt_del = $conn->prepare("DELETE FROM timetable WHERE class_id=?");
        $stmt_del->bind_param("i", $class_id);
        $stmt_del->execute();
        $stmt_del->close();
        
        $assignments = $input['assignments'] ?? [];
        if (!empty($assignments)) {
            $stmt_ins = $conn->prepare("INSERT INTO timetable (class_id, day, hour, subject_id) VALUES (?, ?, ?, ?)");
            foreach ($assignments as $a) {
                $day = $a['day'] ?? '';
                $hour = intval($a['hour'] ?? 0);
                $subject_id = intval($a['subject_id'] ?? 0);
                if (!empty($day) && $hour > 0 && $subject_id > 0) {
                    $stmt_ins->bind_param("isii", $class_id, $day, $hour, $subject_id);
                    $stmt_ins->execute();
                }
            }
            $stmt_ins->close();
        }
        $conn->commit();
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["error" => "Errore durante il salvataggio: " . $e->getMessage()]);
    }
    exit;
}
