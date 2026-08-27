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
require_once __DIR__ . "/../../lib/timetable_model.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $class_id = intval($_GET['class_id'] ?? 0);
    $timetable = [];
    if ($class_id > 0) {
        $stmt = $conn->prepare(
            "SELECT ts.day, ts.hour, tl.subject_id, tl.id AS lesson_id
             FROM timetable_slots ts
             INNER JOIN timetable_lessons tl ON tl.slot_id = ts.id
             WHERE ts.class_id = ?
             ORDER BY ts.day ASC, ts.hour ASC, tl.sort_order ASC, tl.id ASC"
        );
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $dayLabel = timetable_day_to_label((int)$row['day']);
            if ($dayLabel === null) {
                continue;
            }

            $timetable[] = [
                'day' => $dayLabel,
                'hour' => (int)$row['hour'],
                'subject_id' => (int)($row['subject_id'] ?? 0),
                'teacher_ids' => [],
                'room_ids' => []
            ];

            $last = count($timetable) - 1;
            $lessonId = (int)$row['lesson_id'];

            $teacherStmt = $conn->prepare("SELECT teacher_id FROM timetable_lesson_teachers WHERE lesson_id = ?");
            $teacherStmt->bind_param('i', $lessonId);
            $teacherStmt->execute();
            $teacherResult = $teacherStmt->get_result();
            while ($teacherRow = $teacherResult->fetch_assoc()) {
                $timetable[$last]['teacher_ids'][] = (int)$teacherRow['teacher_id'];
            }
            $teacherStmt->close();

            $roomStmt = $conn->prepare("SELECT room_id FROM timetable_lesson_rooms WHERE lesson_id = ?");
            $roomStmt->bind_param('i', $lessonId);
            $roomStmt->execute();
            $roomResult = $roomStmt->get_result();
            while ($roomRow = $roomResult->fetch_assoc()) {
                $timetable[$last]['room_ids'][] = (int)$roomRow['room_id'];
            }
            $roomStmt->close();
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

    if (!is_array($input['assignments'] ?? [])) {
        http_response_code(400);
        echo json_encode(["error" => "Formato assegnazioni non valido."]);
        exit;
    }

    $classCheck = $conn->prepare("SELECT 1 FROM classes WHERE id = ? LIMIT 1");
    $classCheck->bind_param("i", $class_id);
    $classCheck->execute();
    $classExists = $classCheck->get_result()->num_rows > 0;
    $classCheck->close();
    if (!$classExists) {
        http_response_code(404);
        echo json_encode(["error" => "Classe non trovata."]);
        exit;
    }
    
    $conn->begin_transaction();
    try {
        $stmt_del = $conn->prepare("DELETE FROM timetable_slots WHERE class_id=?");
        $stmt_del->bind_param("i", $class_id);
        $stmt_del->execute();
        $stmt_del->close();
        
        $assignments = $input['assignments'];
        if (!empty($assignments)) {
            $slotOrder = [];
            foreach ($assignments as $a) {
                if (!is_array($a)) {
                    throw new InvalidArgumentException('Una delle assegnazioni non è valida.');
                }
                $dayLabel = trim((string)($a['day'] ?? ''));
                $hour = intval($a['hour'] ?? 0);
                $subjectId = intval($a['subject_id'] ?? 0);
                $day = timetable_label_to_day($dayLabel);
                if ($day !== null && $hour > 0 && $subjectId > 0) {
                    $slotId = ensure_timetable_slot($conn, $class_id, $day, $hour);
                    $slotKey = $day . ':' . $hour;
                    $sortOrder = $slotOrder[$slotKey] ?? 0;
                    $teacherIdsInput = $a['teacher_ids'] ?? [];
                    $roomIdsInput = $a['room_ids'] ?? [];
                    if (!is_array($teacherIdsInput) || !is_array($roomIdsInput)) {
                        throw new InvalidArgumentException('Docenti o laboratori non validi.');
                    }
                    $teacherIds = array_values(array_filter(array_map('intval', $teacherIdsInput)));
                    $roomIds = array_values(array_filter(array_map('intval', $roomIdsInput)));
                    create_lesson($conn, $slotId, $subjectId, $teacherIds, $roomIds, $sortOrder, false);
                    $slotOrder[$slotKey] = $sortOrder + 1;
                } elseif ($dayLabel !== '' || $hour > 0 || $subjectId > 0) {
                    throw new InvalidArgumentException('Giorno, ora o materia non validi.');
                }
            }
        }
        $conn->commit();
        echo json_encode(["success" => true]);
    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["error" => "Errore durante il salvataggio: " . $e->getMessage()]);
    }
    exit;
}
