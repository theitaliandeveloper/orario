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

function exportTimetableJSON(mysqli $conn, string $type, $identifier): void
{
    header('Content-Type: application/json; charset=utf-8');

    $days = ["Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"];
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

        $stmt = $conn->prepare(
            "SELECT name FROM classes WHERE id = ? LIMIT 1"
        );
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

        $teacher = $identifier;

        $stmt = $conn->prepare(
            "SELECT 1 FROM subjects WHERE teacher = ? LIMIT 1"
        );
        $stmt->bind_param("s", $teacher);
        $stmt->execute();

        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;

        $stmt->close();

        if (!$exists) {
            http_response_code(404);
            echo json_encode(["error" => "Docente non trovato."]);
            exit;
        }

    } elseif ($normalized_type === 'room') {

        $room = $identifier;

        $stmt = $conn->prepare(
            "SELECT 1 FROM subjects WHERE room = ? LIMIT 1"
        );
        $stmt->bind_param("s", $room);
        $stmt->execute();

        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;

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
                    "SELECT subjects.name, subjects.teacher, subjects.room
                     FROM timetable
                     LEFT JOIN subjects ON timetable.subject_id = subjects.id
                     WHERE class_id = ? AND day = ? AND hour = ?"
                );

                $class_id = intval($identifier);
                $stmt->bind_param("isi", $class_id, $d, $hnum);
                $stmt->execute();

                $q = $stmt->get_result();

                $subject = null;
                $teachers = [];
                $rooms = [];

                while ($row = $q->fetch_assoc()) {
                    if ($subject === null && !empty($row['name'])) {
                        $subject = normalise_string($row['name']);
                    }

                    if (!empty($row['teacher'])) {
                        $t_norm = normalise_string($row['teacher']);

                        if (!in_array($t_norm, $teachers, true)) {
                            $teachers[] = $t_norm;
                        }
                    }

                    if (!empty($row['room']) && !in_array($row['room'], $rooms, true)) {
                        $rooms[] = $row['room'];
                    }
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
                    "SELECT subjects.name, classes.name AS class_name, subjects.room
                     FROM timetable
                     LEFT JOIN subjects ON timetable.subject_id = subjects.id
                     LEFT JOIN classes ON timetable.class_id = classes.id
                     WHERE subjects.teacher = ?
                     AND timetable.day = ?
                     AND timetable.hour = ?"
                );

                $teacher = $identifier;
                $stmt->bind_param("ssi", $teacher, $d, $hnum);
                $stmt->execute();

                $q = $stmt->get_result();

                $subject = null;
                $classes = [];
                $rooms = [];

                while ($row = $q->fetch_assoc()) {
                    if ($subject === null && !empty($row['name'])) {
                        $subject = normalise_string($row['name']);
                    }

                    if (!empty($row['class_name']) &&
                        !in_array($row['class_name'], $classes, true)) {
                        $classes[] = $row['class_name'];
                    }

                    if (!empty($row['room']) &&
                        !in_array($row['room'], $rooms, true)) {
                        $rooms[] = $row['room'];
                    }
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
                    "SELECT subjects.name AS subject_name,
                            subjects.teacher,
                            classes.name AS class_name
                     FROM timetable
                     LEFT JOIN subjects ON timetable.subject_id = subjects.id
                     LEFT JOIN classes ON timetable.class_id = classes.id
                     WHERE subjects.room = ?
                     AND timetable.day = ?
                     AND timetable.hour = ?"
                );

                $room = $identifier;
                $stmt->bind_param("ssi", $room, $d, $hnum);
                $stmt->execute();

                $q = $stmt->get_result();

                $subject = null;
                $class_teacher_pairs = [];

                while ($row = $q->fetch_assoc()) {
                    if ($subject === null && !empty($row['subject_name'])) {
                        $subject = normalise_string($row['subject_name']);
                    }

                    $t_norm = !empty($row['teacher'])
                        ? normalise_string($row['teacher'])
                        : '';

                    $class_teacher_pairs[] = [
                        'class'   => $row['class_name'],
                        'teacher' => $t_norm
                    ];
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
            'room'      => $identifier,
            'timetable' => $timetable
        ];
    }

    echo json_encode(
        $response,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    exit;
}
