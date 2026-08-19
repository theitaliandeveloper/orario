<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.
*/
require_once __DIR__ . "/auth_check.php";

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
    
    $stmt = $conn->prepare("DELETE FROM timetable WHERE class_id = ?");
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

            foreach ($lessons as $lessonData) {
                $materia = $lessonData['materia_short'] ?? '';
                $laboratori = isset($lessonData['aule']) ? $lessonData['aule'] : [];
                $docenti = [];
                if (isset($lessonData['docenti']) && is_array($lessonData['docenti'])) {
                    $docenti = array_keys($lessonData['docenti']);
                }

                if (empty($materia) || count($docenti) === 0) {
                    continue;
                }
                
                // Caso 1: Stesso numero di docenti e laboratori → associazione 1:1
                if (count($docenti) === count($laboratori) && count($laboratori) > 0) {
                    foreach ($docenti as $idx => $docente) {
                        $laboratorio = $laboratori[$idx];
                        
                        $stmt = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND teacher = ? AND room = ?");
                        $stmt->bind_param("sss", $materia, $docente, $laboratorio);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $subject_id = $result->fetch_assoc()['id'];
                        } else {
                            $stmt2 = $conn->prepare("INSERT INTO subjects (name, teacher, room) VALUES (?, ?, ?)");
                            $stmt2->bind_param("sss", $materia, $docente, $laboratorio);
                            $stmt2->execute();
                            $subject_id = $conn->insert_id;
                            $stmt2->close();
                            $materie_create[] = "$materia ($docente - $laboratorio)";
                        }
                        $stmt->close();
                        
                        $stmt3 = $conn->prepare("INSERT INTO timetable (class_id, day, hour, subject_id) VALUES (?, ?, ?, ?)");
                        $stmt3->bind_param("isii", $classe_id, $giorno, $ora, $subject_id);
                        $stmt3->execute();
                        $stmt3->close();
                        $inserimenti++;
                    }
                }
                // Caso 2: Più docenti, un laboratorio (o nessuno) → stesso laboratorio per tutti
                else if (count($laboratori) <= 1) {
                    $laboratorio = count($laboratori) > 0 ? $laboratori[0] : null;
                    
                    foreach ($docenti as $docente) {
                        if ($laboratorio) {
                            $stmt = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND teacher = ? AND room = ?");
                            $stmt->bind_param("sss", $materia, $docente, $laboratorio);
                        } else {
                            $stmt = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND teacher = ? AND (room IS NULL OR room = '')");
                            $stmt->bind_param("ss", $materia, $docente);
                        }
                        
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $subject_id = $result->fetch_assoc()['id'];
                        } else {
                            $stmt2 = $conn->prepare("INSERT INTO subjects (name, teacher, room) VALUES (?, ?, ?)");
                            $stmt2->bind_param("sss", $materia, $docente, $laboratorio);
                            $stmt2->execute();
                            $subject_id = $conn->insert_id;
                            $stmt2->close();
                            $materie_create[] = "$materia ($docente" . ($laboratorio ? " - $laboratorio" : "") . ")";
                        }
                        $stmt->close();
                        
                        $stmt3 = $conn->prepare("INSERT INTO timetable (class_id, day, hour, subject_id) VALUES (?, ?, ?, ?)");
                        $stmt3->bind_param("isii", $classe_id, $giorno, $ora, $subject_id);
                        $stmt3->execute();
                        $stmt3->close();
                        $inserimenti++;
                    }
                }
                // Caso 3: Più laboratori che docenti → usa il primo laboratorio per tutti
                else {
                    $laboratorio = $laboratori[0];
                    
                    foreach ($docenti as $docente) {
                        $stmt = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND teacher = ? AND room = ?");
                        $stmt->bind_param("sss", $materia, $docente, $laboratorio);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $subject_id = $result->fetch_assoc()['id'];
                        } else {
                            $stmt2 = $conn->prepare("INSERT INTO subjects (name, teacher, room) VALUES (?, ?, ?)");
                            $stmt2->bind_param("sss", $materia, $docente, $laboratorio);
                            $stmt2->execute();
                            $subject_id = $conn->insert_id;
                            $stmt2->close();
                            $materie_create[] = "$materia ($docente - $laboratorio)";
                        }
                        $stmt->close();
                        
                        $stmt3 = $conn->prepare("INSERT INTO timetable (class_id, day, hour, subject_id) VALUES (?, ?, ?, ?)");
                        $stmt3->bind_param("isii", $classe_id, $giorno, $ora, $subject_id);
                        $stmt3->execute();
                        $stmt3->close();
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
