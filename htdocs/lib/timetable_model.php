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

function timetable_day_map(): array
{
    return [
        1 => 'Lunedì',
        2 => 'Martedì',
        3 => 'Mercoledì',
        4 => 'Giovedì',
        5 => 'Venerdì',
        6 => 'Sabato',
    ];
}

function timetable_day_to_label(int $day): ?string
{
    $map = timetable_day_map();
    return $map[$day] ?? null;
}

function timetable_label_to_day(string $label): ?int
{
    static $reverse = null;
    if ($reverse === null) {
        $reverse = array_flip(timetable_day_map());
    }
    return $reverse[$label] ?? null;
}

function get_or_create_subject(mysqli $conn, string $name): int
{
    $stmt = $conn->prepare('SELECT id FROM subjects WHERE name = ? ORDER BY id ASC LIMIT 1');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $id = (int)$res->fetch_assoc()['id'];
        $stmt->close();
        return $id;
    }
    $stmt->close();

    $ins = $conn->prepare('INSERT INTO subjects (name) VALUES (?)');
    $ins->bind_param('s', $name);
    $ins->execute();
    $id = (int)$conn->insert_id;
    $ins->close();

    return $id;
}

function get_or_create_teacher(mysqli $conn, string $name): int
{
    $stmt = $conn->prepare('SELECT id FROM teachers WHERE name = ? ORDER BY id ASC LIMIT 1');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $id = (int)$res->fetch_assoc()['id'];
        $stmt->close();
        return $id;
    }
    $stmt->close();

    $ins = $conn->prepare('INSERT INTO teachers (name) VALUES (?)');
    $ins->bind_param('s', $name);
    $ins->execute();
    $id = (int)$conn->insert_id;
    $ins->close();

    return $id;
}

function get_or_create_room(mysqli $conn, string $name): int
{
    $stmt = $conn->prepare('SELECT id FROM rooms WHERE name = ? ORDER BY id ASC LIMIT 1');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $id = (int)$res->fetch_assoc()['id'];
        $stmt->close();
        return $id;
    }
    $stmt->close();

    $ins = $conn->prepare('INSERT INTO rooms (name) VALUES (?)');
    $ins->bind_param('s', $name);
    $ins->execute();
    $id = (int)$conn->insert_id;
    $ins->close();

    return $id;
}

function ensure_timetable_slot(mysqli $conn, int $classId, int $day, int $hour): int
{
    $stmt = $conn->prepare('INSERT INTO timetable_slots (class_id, day, hour) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
    $stmt->bind_param('iii', $classId, $day, $hour);
    $stmt->execute();
    $slotId = (int)$conn->insert_id;
    $stmt->close();

    return $slotId;
}

function create_lesson(mysqli $conn, int $slotId, int $subjectId, array $teacherIds = [], array $roomIds = [], int $sortOrder = 0, bool $remote = false): int
{
    $remoteInt = $remote ? 1 : 0;

    $ins = $conn->prepare('INSERT INTO timetable_lessons (slot_id, subject_id, remote, sort_order) VALUES (?, ?, ?, ?)');
    $ins->bind_param('iiii', $slotId, $subjectId, $remoteInt, $sortOrder);
    $ins->execute();
    $lessonId = (int)$conn->insert_id;
    $ins->close();

    foreach ($teacherIds as $teacherId) {
        $teacherId = (int)$teacherId;
        if ($teacherId <= 0) {
            continue;
        }

        $linkT = $conn->prepare('INSERT IGNORE INTO timetable_lesson_teachers (lesson_id, teacher_id) VALUES (?, ?)');
        $linkT->bind_param('ii', $lessonId, $teacherId);
        $linkT->execute();
        $linkT->close();
    }

    foreach ($roomIds as $roomId) {
        $roomId = (int)$roomId;
        if ($roomId <= 0) {
            continue;
        }

        $linkR = $conn->prepare('INSERT IGNORE INTO timetable_lesson_rooms (lesson_id, room_id) VALUES (?, ?)');
        $linkR->bind_param('ii', $lessonId, $roomId);
        $linkR->execute();
        $linkR->close();
    }

    return $lessonId;
}
