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
$input = is_array($input) ? $input : $_POST;
$classe_codice = trim($input['classe_codice'] ?? $_POST['classe_codice'] ?? '');
$classe_id = intval($input['classe_id'] ?? $_POST['classe_id'] ?? 0);

if (empty($classe_codice) || $classe_id === 0) {
    http_response_code(400);
    echo json_encode(["error" => "Compila tutti i campi obbligatori."]);
    exit;
}

$classCheck = $conn->prepare("SELECT 1 FROM classes WHERE id = ? LIMIT 1");
$classCheck->bind_param("i", $classe_id);
$classCheck->execute();
$classExists = $classCheck->get_result()->num_rows > 0;
$classCheck->close();
if (!$classExists) {
    http_response_code(404);
    echo json_encode(["error" => "Classe di destinazione non trovata."]);
    exit;
}

try {
    $baseUrl = rtrim(API_URL, '/');
    $suffix = str_ends_with($baseUrl, "/orario") ? "" : "/orario";
    $url = $baseUrl . $suffix . "?classe=" . urlencode($classe_codice);
    
    $ch = curl_init();
    if ($ch === false) {
        throw new RuntimeException("Impossibile inizializzare cURL.");
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException("Errore nella chiamata API: " . ($curlError ?: 'errore sconosciuto'));
    }
    
    if ($httpCode !== 200) {
        throw new Exception("Errore nella chiamata API (HTTP $httpCode)");
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        throw new RuntimeException("Formato JSON non valido: " . json_last_error_msg());
    }

    if (isset($data['version']) && count($data) === 1) {
        throw new RuntimeException("L'API esterna ha restituito solo la versione {$data['version']} e nessun orario. Verifica l'endpoint o la versione dell'API configurata.");
    }

    foreach (['data', 'timetable', 'orario'] as $wrapperKey) {
        if (isset($data[$wrapperKey]) && is_array($data[$wrapperKey])) {
            $data = $data[$wrapperKey];
            break;
        }
    }

    $data = array_values($data);
    if (count($data) !== 6) {
        throw new InvalidArgumentException('Il JSON deve contenere esattamente 6 giorni.');
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM timetable_slots WHERE class_id = ?");
        $stmt->bind_param("i", $classe_id);
        $stmt->execute();
        $stmt->close();

        $inserimenti = 0;
        $materie_create = [];
        $docenti_create = [];
        $laboratori_create = [];

        foreach ($data as $dayIndex => $dayHours) {
            if (!is_array($dayHours) || count($dayHours) !== 6) {
                throw new InvalidArgumentException('Ogni giorno deve contenere esattamente 6 ore.');
            }

            $giorno = (int)$dayIndex + 1;
            foreach ($dayHours as $hourIndex => $lessons) {
                if (!is_array($lessons) || count($lessons) === 0) {
                    continue;
                }

                $ora = (int)$hourIndex + 1;
                $slotId = ensure_timetable_slot($conn, $classe_id, $giorno, $ora);
                $sortOrder = 0;

                foreach ($lessons as $lessonData) {
                    if (!is_array($lessonData)) {
                        throw new InvalidArgumentException('Una lezione non è un oggetto JSON valido.');
                    }

                    $materia = trim((string)($lessonData['materia_short'] ?? $lessonData['materia'] ?? ''));
                    if ($materia === '') {
                        continue;
                    }

                    $externalSubjectId = trim((string)($lessonData['IDmateria'] ?? ''));
                    $fullName = trim((string)($lessonData['materia'] ?? '')) ?: null;
                    $bgColor = trim((string)($lessonData['bgcolor'] ?? '')) ?: null;
                    $textColor = trim((string)($lessonData['color'] ?? '')) ?: null;
                    $subjectId = get_or_create_import_subject($conn, $externalSubjectId, $materia, $fullName, $bgColor, $textColor);

                    $teacherNames = [];
                    if (isset($lessonData['docenti']) && is_array($lessonData['docenti'])) {
                        $teacherNames = array_keys($lessonData['docenti']);
                    } elseif (isset($lessonData['docenti_usernames']) && is_array($lessonData['docenti_usernames'])) {
                        $teacherNames = $lessonData['docenti_usernames'];
                    }

                    $teacherIds = [];
                    foreach ($teacherNames as $teacherIndex => $teacherName) {
                        $teacherName = trim((string)$teacherName);
                        if ($teacherName === '' || in_array($teacherName, $teacherNames, true) && array_search($teacherName, $teacherNames, true) !== $teacherIndex) {
                            continue;
                        }
                        $username = isset($lessonData['docenti_usernames'][$teacherIndex])
                            ? trim((string)$lessonData['docenti_usernames'][$teacherIndex])
                            : '';
                        $teacherId = get_or_create_import_teacher($conn, $username, $teacherName);
                        $teacherIds[] = $teacherId;
                        $docenti_create[$teacherName] = true;
                    }

                    $roomIds = [];
                    $roomNames = isset($lessonData['aule']) && is_array($lessonData['aule']) ? $lessonData['aule'] : [];
                    foreach ($roomNames as $roomName) {
                        $roomName = trim((string)$roomName);
                        if ($roomName === '') {
                            continue;
                        }
                        $roomId = get_or_create_import_room($conn, '', $roomName);
                        $roomIds[] = $roomId;
                        $laboratori_create[$roomName] = true;
                    }

                    $remote = !empty($lessonData['a_distanza']);
                    create_lesson($conn, $slotId, $subjectId, array_values(array_unique($teacherIds)), array_values(array_unique($roomIds)), $sortOrder++, $remote);
                    $materie_create[$materia] = true;
                    $inserimenti++;
                }
            }
        }

        $conn->commit();
        $materie_create = array_keys($materie_create);
        $docenti_create = array_keys($docenti_create);
        $laboratori_create = array_keys($laboratori_create);
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
    
    echo json_encode([
        "success" => true,
        "inserimenti" => $inserimenti,
        "materie_create_count" => count($materie_create),
        "materie_create" => $materie_create,
        "docenti_importati" => $docenti_create,
        "laboratori_importati" => $laboratori_create
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
exit;
