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

if (!defined('API_URL') || API_URL == "") {
    http_response_code(400);
    echo json_encode(["error" => "API_URL non configurato."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Metodo non consentito."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$classe_codice = trim($input['classe_codice'] ?? $_POST['classe_codice'] ?? '');
$classe_id = intval($input['classe_id'] ?? $_POST['classe_id'] ?? 0);

if (empty($classe_codice) || $classe_id === 0) {
    http_response_code(400);
    echo json_encode(["error" => "Compila tutti i campi obbligatori."]);
    exit;
}

try {
    $baseUrl = rtrim(API_URL, '/');
    $suffix = str_ends_with($baseUrl, "/classe") ? "" : "/classe";
    $url = $baseUrl . $suffix . "?classe=" . urlencode($classe_codice);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("Errore nella chiamata API (HTTP $httpCode)");
    }
    
    $data = json_decode($response, true);
    if (!$data || !is_array($data)) {
        throw new Exception("Formato JSON non valido o vuoto");
    }
    
    $stmt = $conn->prepare("DELETE FROM timetable_slots WHERE class_id = ?");
    $stmt->bind_param("i", $classe_id);
    $stmt->execute();
    $stmt->close();
    
    $inserimenti = 0;
    $materie_create = [];
    
    foreach ($data as $dayIndex => $dayHours) {
        $giorno = $dayIndex + 1;
        foreach ($dayHours as $hourIndex => $lessons) {
            $ora = $hourIndex + 1;
            if (empty($lessons)) {
                continue;
            }

            $slotId = ensure_timetable_slot($conn, $classe_id, $giorno, $ora);
            $sortOrder = 0;

            foreach ($lessons as $lessonData) {
                $materia = $lessonData['materia_short'] ?? '';
                $laboratori = isset($lessonData['aule']) && is_array($lessonData['aule']) ? $lessonData['aule'] : [];
                $docenti = [];
                if (isset($lessonData['docenti']) && is_array($lessonData['docenti'])) {
                    $docenti = array_keys($lessonData['docenti']);
                }

                if (empty($materia) || count($docenti) === 0) {
                    continue;
                }

                $subjectId = get_or_create_subject($conn, $materia);
                
                // Caso 1: Stesso numero di docenti e laboratori → associazione 1:1
                if (count($docenti) === count($laboratori) && count($laboratori) > 0) {
                    foreach ($docenti as $idx => $docente) {
                        $docente = trim((string)$docente);
                        $laboratorio = trim((string)$laboratori[$idx]);
                        if ($docente === '') {
                            continue;
                        }

                        $teacherId = get_or_create_teacher($conn, $docente);
                        $roomId = $laboratorio !== '' ? get_or_create_room($conn, $laboratorio) : null;
                        $teacherIds = [$teacherId];
                        $roomIds = $roomId !== null ? [$roomId] : [];
                        create_lesson($conn, $slotId, $subjectId, $teacherIds, $roomIds, $sortOrder++, false);

                        $createdLabel = $materia . ' (' . $docente . ($laboratorio !== '' ? ' - ' . $laboratorio : '') . ')';
                        if (!in_array($createdLabel, $materie_create, true)) {
                            $materie_create[] = $createdLabel;
                        }
                        $inserimenti++;
                    }
                }
                // Caso 2: Più docenti, un laboratorio (o nessuno) → stesso laboratorio per tutti
                else if (count($laboratori) <= 1) {
                    $laboratorio = count($laboratori) > 0 ? trim((string)$laboratori[0]) : '';
                    
                    foreach ($docenti as $docente) {
                        $docente = trim((string)$docente);
                        if ($docente === '') {
                            continue;
                        }

                        $teacherId = get_or_create_teacher($conn, $docente);
                        $roomId = $laboratorio !== '' ? get_or_create_room($conn, $laboratorio) : null;
                        $teacherIds = [$teacherId];
                        $roomIds = $roomId !== null ? [$roomId] : [];
                        create_lesson($conn, $slotId, $subjectId, $teacherIds, $roomIds, $sortOrder++, false);

                        $createdLabel = $materia . ' (' . $docente . ($laboratorio !== '' ? ' - ' . $laboratorio : '') . ')';
                        if (!in_array($createdLabel, $materie_create, true)) {
                            $materie_create[] = $createdLabel;
                        }
                        $inserimenti++;
                    }
                }
                // Caso 3: Più laboratori che docenti → usa il primo laboratorio per tutti
                else {
                    $laboratorio = trim((string)$laboratori[0]);
                    
                    foreach ($docenti as $docente) {
                        $docente = trim((string)$docente);
                        if ($docente === '') {
                            continue;
                        }

                        $teacherId = get_or_create_teacher($conn, $docente);
                        $roomId = $laboratorio !== '' ? get_or_create_room($conn, $laboratorio) : null;
                        $teacherIds = [$teacherId];
                        $roomIds = $roomId !== null ? [$roomId] : [];
                        create_lesson($conn, $slotId, $subjectId, $teacherIds, $roomIds, $sortOrder++, false);

                        $createdLabel = $materia . ' (' . $docente . ($laboratorio !== '' ? ' - ' . $laboratorio : '') . ')';
                        if (!in_array($createdLabel, $materie_create, true)) {
                            $materie_create[] = $createdLabel;
                        }
                        $inserimenti++;
                    }
                }
            }
        }
    }
    
    echo json_encode([
        "success" => true,
        "inserimenti" => $inserimenti,
        "materie_create_count" => count($materie_create),
        "materie_create" => $materie_create
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
exit;
