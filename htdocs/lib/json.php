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

require_once __DIR__ . "/misc.php";
require_once __DIR__ . "/variables.php";
require_once __DIR__ . "/timetable_model.php";

function exportTimetableJSON(mysqli $conn, string $type, $identifier): void
{
    header('Content-Type: application/json; charset=utf-8');

    $days = array_values(timetable_day_map());
    $hours = [
        1 => "Prima ora<br> 7:50 - 8:50",
        2 => "Seconda ora<br> 8:50 - 9:45",
        3 => "Terza ora<br> 9:55 - 10:50",
        4 => "Quarta ora<br> 10:50 - 11:45",
        5 => "Quinta ora<br> 11:55 - 12:50",
        6 => "Sesta ora<br> 12:50 - 13:50",
    ];

    $timetable = [];

    $normalized_type = strtolower($type);
    if ($normalized_type === 'classe') $normalized_type = 'class';
    if ($normalized_type === 'docente') $normalized_type = 'teacher';
    if ($normalized_type === 'laboratorio') $normalized_type = 'room';

    /*
     * Controllo esistenza dell'entità richiesta.
     */
    $class_name = '';

    if ($normalized_type === 'class') {
        $class_id = intval($identifier);
        $stmt = $conn->prepare("SELECT name FROM classes WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            http_response_code(404);
            echo json_encode(["error" => "Classe non trovata."]);
            exit;
        }

        $class_name = $row['name'];
    } elseif ($normalized_type === 'teacher') {
        $teacher = trim((string)$identifier);
        $stmt = $conn->prepare("SELECT 1 FROM teachers WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $teacher);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$exists) {
            http_response_code(404);
            echo json_encode(["error" => "Docente non trovato."]);
            exit;
        }
    } elseif ($normalized_type === 'room') {
        $room = trim((string)$identifier);
        $stmt = $conn->prepare("SELECT 1 FROM rooms WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $room);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$exists) {
            http_response_code(404);
            echo json_encode(["error" => "Aula non trovata."]);
            exit;
        }
    }

    /*
     * Costruzione timetable.
     */
    foreach ($days as $d) {
        $dayNum = timetable_label_to_day($d);
        if ($dayNum === null) {
            continue;
        }

        $d_clean = str_replace(
            ['à', 'è', 'é', 'ì', 'ò', 'ù'],
            ['a', 'e', 'e', 'i', 'o', 'u'],
            $d
        );

        $timetable[$d_clean] = [];

        foreach ($hours as $hnum => $hlabel) {
            $time_str = strip_tags($hlabel);

            if ($normalized_type === 'class') {
                $stmt = $conn->prepare(
                    "SELECT tl.id AS lesson_id, s.name AS subject_name
                     FROM timetable_slots ts
                     INNER JOIN timetable_lessons tl ON tl.slot_id = ts.id
                     LEFT JOIN subjects s ON s.id = tl.subject_id
                     WHERE ts.class_id = ? AND ts.day = ? AND ts.hour = ?
                     ORDER BY tl.sort_order ASC, tl.id ASC"
                );

                $class_id = intval($identifier);
                $stmt->bind_param("iii", $class_id, $dayNum, $hnum);
                $stmt->execute();
                $q = $stmt->get_result();

                $subject = null;
                $teachers = [];
                $rooms = [];

                while ($row = $q->fetch_assoc()) {
                    if ($subject === null && !empty($row['subject_name'])) {
                        $subject = normalise_string($row['subject_name']);
                    }

                    $lessonId = (int)$row['lesson_id'];

                    $tstmt = $conn->prepare(
                        "SELECT t.name
                         FROM timetable_lesson_teachers tlt
                         INNER JOIN teachers t ON t.id = tlt.teacher_id
                         WHERE tlt.lesson_id = ?"
                    );
                    $tstmt->bind_param("i", $lessonId);
                    $tstmt->execute();
                    $tres = $tstmt->get_result();
                    while ($trow = $tres->fetch_assoc()) {
                        $teacherName = normalise_string($trow['name']);
                        if (!in_array($teacherName, $teachers, true)) {
                            $teachers[] = $teacherName;
                        }
                    }
                    $tstmt->close();

                    $rstmt = $conn->prepare(
                        "SELECT r.name
                         FROM timetable_lesson_rooms tlr
                         INNER JOIN rooms r ON r.id = tlr.room_id
                         WHERE tlr.lesson_id = ?"
                    );
                    $rstmt->bind_param("i", $lessonId);
                    $rstmt->execute();
                    $rres = $rstmt->get_result();
                    while ($rrow = $rres->fetch_assoc()) {
                        if (!in_array($rrow['name'], $rooms, true)) {
                            $rooms[] = $rrow['name'];
                        }
                    }
                    $rstmt->close();
                }

                $stmt->close();

                $timetable[$d_clean][$hnum] = [
                    'hour'     => $hnum,
                    'time'     => $time_str,
                    'subject'  => $subject,
                    'teachers' => $teachers,
                    'rooms'    => $rooms
                ];

            } elseif ($normalized_type === 'teacher') {
                $stmt = $conn->prepare(
                    "SELECT tl.id AS lesson_id, s.name AS subject_name, c.name AS class_name
                     FROM teachers t
                     INNER JOIN timetable_lesson_teachers tlt ON tlt.teacher_id = t.id
                     INNER JOIN timetable_lessons tl ON tl.id = tlt.lesson_id
                     INNER JOIN timetable_slots ts ON ts.id = tl.slot_id
                     INNER JOIN classes c ON c.id = ts.class_id
                     LEFT JOIN subjects s ON s.id = tl.subject_id
                     WHERE t.name = ? AND ts.day = ? AND ts.hour = ?
                     ORDER BY tl.sort_order ASC, tl.id ASC"
                );

                $teacher = trim((string)$identifier);
                $stmt->bind_param("sii", $teacher, $dayNum, $hnum);
                $stmt->execute();
                $q = $stmt->get_result();

                $subject = null;
                $classes = [];
                $rooms = [];

                while ($row = $q->fetch_assoc()) {
                    if ($subject === null && !empty($row['subject_name'])) {
                        $subject = normalise_string($row['subject_name']);
                    }

                    if (!empty($row['class_name']) && !in_array($row['class_name'], $classes, true)) {
                        $classes[] = $row['class_name'];
                    }

                    $lessonId = (int)$row['lesson_id'];
                    $rstmt = $conn->prepare(
                        "SELECT r.name
                         FROM timetable_lesson_rooms tlr
                         INNER JOIN rooms r ON r.id = tlr.room_id
                         WHERE tlr.lesson_id = ?"
                    );
                    $rstmt->bind_param("i", $lessonId);
                    $rstmt->execute();
                    $rres = $rstmt->get_result();
                    while ($rrow = $rres->fetch_assoc()) {
                        if (!empty($rrow['name']) && !in_array($rrow['name'], $rooms, true)) {
                            $rooms[] = $rrow['name'];
                        }
                    }
                    $rstmt->close();
                }

                $stmt->close();

                $timetable[$d_clean][$hnum] = [
                    'hour'    => $hnum,
                    'time'    => $time_str,
                    'subject' => $subject,
                    'classes' => $classes,
                    'rooms'   => $rooms
                ];

            } elseif ($normalized_type === 'room') {
                $stmt = $conn->prepare(
                    "SELECT tl.id AS lesson_id, s.name AS subject_name, c.name AS class_name
                     FROM rooms r
                     INNER JOIN timetable_lesson_rooms tlr ON tlr.room_id = r.id
                     INNER JOIN timetable_lessons tl ON tl.id = tlr.lesson_id
                     INNER JOIN timetable_slots ts ON ts.id = tl.slot_id
                     INNER JOIN classes c ON c.id = ts.class_id
                     LEFT JOIN subjects s ON s.id = tl.subject_id
                     WHERE r.name = ? AND ts.day = ? AND ts.hour = ?
                     ORDER BY tl.sort_order ASC, tl.id ASC"
                );

                $room = trim((string)$identifier);
                $stmt->bind_param("sii", $room, $dayNum, $hnum);
                $stmt->execute();
                $q = $stmt->get_result();

                $subject = null;
                $class_teacher_pairs = [];

                while ($row = $q->fetch_assoc()) {
                    if ($subject === null && !empty($row['subject_name'])) {
                        $subject = normalise_string($row['subject_name']);
                    }

                    $lessonId = (int)$row['lesson_id'];
                    $tstmt = $conn->prepare(
                        "SELECT t.name
                         FROM timetable_lesson_teachers tlt
                         INNER JOIN teachers t ON t.id = tlt.teacher_id
                         WHERE tlt.lesson_id = ?"
                    );
                    $tstmt->bind_param("i", $lessonId);
                    $tstmt->execute();
                    $tres = $tstmt->get_result();

                    $hasTeachers = false;
                    while ($trow = $tres->fetch_assoc()) {
                        $hasTeachers = true;
                        $class_teacher_pairs[] = [
                            'class' => $row['class_name'],
                            'teacher' => normalise_string($trow['name'])
                        ];
                    }
                    $tstmt->close();

                    if (!$hasTeachers) {
                        $class_teacher_pairs[] = [
                            'class' => $row['class_name'],
                            'teacher' => ''
                        ];
                    }
                }

                $stmt->close();

                $timetable[$d_clean][$hnum] = [
                    'hour'    => $hnum,
                    'time'    => $time_str,
                    'subject' => $subject,
                    'classes' => $class_teacher_pairs
                ];
            }
        }
    }

    /*
     * Risposta.
     */
    if ($normalized_type === 'class') {
        $response = [
            'class_id'   => intval($identifier),
            'class_name' => $class_name,
            'timetable'  => $timetable
        ];
    } elseif ($normalized_type === 'teacher') {
        $response = [
            'teacher'   => normalise_string($identifier),
            'timetable' => $timetable
        ];
    } else {
        $response = [
            'room'      => trim((string)$identifier),
            'timetable' => $timetable
        ];
    }

    echo json_encode(
        $response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    exit;
}
