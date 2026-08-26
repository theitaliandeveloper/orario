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
            "SELECT ts.day, ts.hour, tl.subject_id
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
                'subject_id' => (int)($row['subject_id'] ?? 0)
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
        $stmt_del = $conn->prepare("DELETE FROM timetable_slots WHERE class_id=?");
        $stmt_del->bind_param("i", $class_id);
        $stmt_del->execute();
        $stmt_del->close();
        
        $assignments = $input['assignments'] ?? [];
        if (!empty($assignments)) {
            $slotOrder = [];
            foreach ($assignments as $a) {
                $dayLabel = trim((string)($a['day'] ?? ''));
                $hour = intval($a['hour'] ?? 0);
                $subjectId = intval($a['subject_id'] ?? 0);
                $day = timetable_label_to_day($dayLabel);
                if ($day !== null && $hour > 0 && $subjectId > 0) {
                    $slotId = ensure_timetable_slot($conn, $class_id, $day, $hour);
                    $slotKey = $day . ':' . $hour;
                    $sortOrder = $slotOrder[$slotKey] ?? 0;
                    create_lesson($conn, $slotId, $subjectId, [], [], $sortOrder, false);
                    $slotOrder[$slotKey] = $sortOrder + 1;
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
