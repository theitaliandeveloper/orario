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

require_once __DIR__ . "/../lib/redis.php";

header('Content-Type: application/json; charset=utf-8');

$cacheKey = 'homepage:occupancy:v' . redis_occupancy_version() . ':' . date('Y-m-d-H-i');
$redis = redis_connection();

if ($redis) {
    try {
        $cached = $redis->get($cacheKey);
        if ($cached !== false) {
            echo $cached;
            exit;
        }
    } catch (Throwable $exception) {
        error_log('Lettura cache Redis fallita: ' . $exception->getMessage());
    }
}

require_once __DIR__ . "/../lib/db.php";

function occupancy_clock_to_seconds($value): ?int
{
    $parts = explode(':', (string)$value);
    if (count($parts) < 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
        return null;
    }

    return ((int)$parts[0] * 3600) + ((int)$parts[1] * 60) + (int)($parts[2] ?? 0);
}

function occupancy_list(array $values): array
{
    return array_values(array_keys($values));
}

$preferences = get_timetable_preferences($conn);
$nowSeconds = ((int)date('G') * 3600) + ((int)date('i') * 60) + (int)date('s');
$currentPeriod = null;
$currentHour = null;

foreach ($preferences['TIMETABLE_HOURS'] as $index => $period) {
    $start = occupancy_clock_to_seconds($period['start'] ?? null);
    $end = occupancy_clock_to_seconds($period['end'] ?? null);
    if ($start !== null && $end !== null && $nowSeconds >= $start && $nowSeconds < $end) {
        $currentPeriod = [
            'label' => (string)($period['label'] ?? ''),
            'start' => (string)$period['start'],
            'end' => (string)$period['end'],
            'type' => 'lesson'
        ];
        $currentHour = $index + 1;
        break;
    }
}

if ($currentPeriod === null) {
    foreach ($preferences['TIMETABLE_BREAKS'] as $period) {
        $start = occupancy_clock_to_seconds($period['start'] ?? null);
        $end = occupancy_clock_to_seconds($period['end'] ?? null);
        if ($start !== null && $end !== null && $nowSeconds >= $start && $nowSeconds < $end) {
            $currentPeriod = [
                'label' => (string)($period['label'] ?? ''),
                'start' => (string)$period['start'],
                'end' => (string)$period['end'],
                'type' => 'break'
            ];
            break;
        }
    }
}

$response = [
    'period' => $currentPeriod,
    'teachers' => [],
    'rooms' => []
];

if ($currentHour !== null) {
    $day = (int)date('N');
    $stmt = $conn->prepare(
        "SELECT c.name AS class_name, t.name AS teacher_name, r.name AS room_name
         FROM timetable_slots ts
         INNER JOIN timetable_lessons tl ON tl.slot_id = ts.id
         INNER JOIN classes c ON c.id = ts.class_id
         LEFT JOIN timetable_lesson_teachers tlt ON tlt.lesson_id = tl.id
         LEFT JOIN teachers t ON t.id = tlt.teacher_id
         LEFT JOIN timetable_lesson_rooms tlr ON tlr.lesson_id = tl.id
         LEFT JOIN rooms r ON r.id = tlr.room_id
         WHERE ts.day = ? AND ts.hour = ?
         ORDER BY tl.sort_order ASC, tl.id ASC"
    );
    $stmt->bind_param('ii', $day, $currentHour);
    $stmt->execute();
    $result = $stmt->get_result();

    $teachers = [];
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $className = trim((string)$row['class_name']);
        $teacherName = trim((string)$row['teacher_name']);
        $roomName = trim((string)$row['room_name']);

        if ($teacherName !== '') {
            $teachers[$teacherName]['classes'][$className] = true;
            if ($roomName !== '') {
                $teachers[$teacherName]['rooms'][$roomName] = true;
            }
        }
        if ($roomName !== '') {
            $rooms[$roomName][$className] = true;
        }
    }
    $stmt->close();

    foreach ($teachers as $teacherName => $teacherData) {
        $response['teachers'][$teacherName] = [
            'classes' => occupancy_list($teacherData['classes'] ?? []),
            'rooms' => occupancy_list($teacherData['rooms'] ?? [])
        ];
    }
    foreach ($rooms as $roomName => $classes) {
        $response['rooms'][$roomName] = occupancy_list($classes);
    }
}

$json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
if ($redis) {
    try {
        $redis->setex($cacheKey, 65, $json);
    } catch (Throwable $exception) {
        error_log('Scrittura cache Redis fallita: ' . $exception->getMessage());
    }
}

echo $json;
