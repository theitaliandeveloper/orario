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

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . "/variables.php";
require_once __DIR__ . "/timetable_model.php";


function exportTimetablePDF(mysqli $conn, string $type, $identifier): void
{
    $days = array_values(timetable_day_map());
    $hours = [
        1 => "Prima ora\n7:50 - 8:50",
        2 => "Seconda ora\n8:50 - 9:45",
        3 => "Terza ora\n9:55 - 10:50",
        4 => "Quarta ora\n10:50 - 11:45",
        5 => "Quinta ora\n11:55 - 12:50",
        6 => "Sesta ora\n12:50 - 13:50",
    ];

    // CONTROLLO TIPO VALIDO
    if (!in_array($type, ['classe', 'docente', 'laboratorio'], true)) {
        http_response_code(400);
        exit("Errore 400: Tipo di ricerca non valido. Usa: classe, docente o laboratorio");
    }

    // CONTROLLO DI ESISTENZA DELLE RISORSE
    $title    = '';
    $filename = '';

    switch ($type) {
        case 'classe':
            $class_id = intval($identifier);

            if ($class_id <= 0) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => true,
                    'message' => 'ID Classe non fornito'
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("SELECT id, name FROM classes WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $class_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => true,
                    'message' => 'ID Classe non trovato',
                    'id' => $class_id
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $row = $result->fetch_assoc();
            $title    = 'Orario classe ' . $row['name'];
            $filename = 'orario_classe_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $row['name']);
            $stmt->close();
            break;

        case 'docente':
            if (empty($identifier)) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => true,
                    'message' => 'Nome docente non fornito'
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("SELECT 1 FROM teachers WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => true,
                    'message' => 'Docente non trovato',
                    'id' => $identifier
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $title    = 'Orario docente ' . $identifier;
            $filename = 'orario_docente_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $identifier);
            $stmt->close();
            break;

        case 'laboratorio':
            if (empty($identifier)) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => true,
                    'message' => 'Nome laboratorio non fornito'
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("SELECT 1 FROM rooms WHERE name = ? LIMIT 1");
            $stmt->bind_param("s", $identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                http_response_code(404);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => true,
                    'message' => 'Laboratorio non trovato',
                    'id' => $identifier
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $title    = 'Orario ' . $identifier;
            $filename = 'orario_laboratorio_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $identifier);
            $stmt->close();
            break;
    }

    $data = _loadTimetableData($conn, $type, $identifier, $days, array_keys($hours));
    _renderPDF($title, $filename . '.pdf', $days, $hours, $data);
}


function _loadTimetableData(mysqli $conn, string $type, $identifier, array $days, array $hournums): array
{
    $data = [];

    foreach ($days as $d) {
        $data[$d] = [];
        $dayNum = timetable_label_to_day($d);
        if ($dayNum === null) {
            continue;
        }

        foreach ($hournums as $hnum) {

            switch ($type) {

                // ---- CLASSE ----
                case 'classe':
                    $class_id = intval($identifier);
                    $stmt = $conn->prepare("
                                                SELECT tl.id AS lesson_id, s.name AS subject_name
                                                FROM timetable_slots ts
                                                INNER JOIN timetable_lessons tl ON tl.slot_id = ts.id
                                                LEFT JOIN subjects s ON s.id = tl.subject_id
                                                WHERE ts.class_id = ?
                                                    AND ts.day = ?
                                                    AND ts.hour = ?
                                                ORDER BY tl.sort_order ASC, tl.id ASC
                    ");
                                        $stmt->bind_param("iii", $class_id, $dayNum, $hnum);
                    $stmt->execute();
                    $q = $stmt->get_result();

                    $subject  = null;
                    $teachers = [];
                    $rooms    = [];

                    while ($row = $q->fetch_assoc()) {
                        if ($subject === null && !empty($row['subject_name'])) {
                            $subject = $row['subject_name'];
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
                            if (!in_array($trow['name'], $teachers, true)) {
                                $teachers[] = $trow['name'];
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

                    $data[$d][$hnum] = [
                        'subject' => $subject,
                        'lines'   => $teachers,
                        'rooms'   => $rooms,
                    ];
                    $stmt->close();
                    break;

                // ---- DOCENTE ----
                case 'docente':
                    $stmt = $conn->prepare("
                        SELECT tl.id AS lesson_id, s.name AS subject_name, c.name AS class_name
                        FROM teachers t
                        INNER JOIN timetable_lesson_teachers tlt ON tlt.teacher_id = t.id
                        INNER JOIN timetable_lessons tl ON tl.id = tlt.lesson_id
                        INNER JOIN timetable_slots ts ON ts.id = tl.slot_id
                        INNER JOIN classes c ON c.id = ts.class_id
                        LEFT JOIN subjects s ON s.id = tl.subject_id
                        WHERE t.name = ?
                          AND ts.day = ?
                          AND ts.hour = ?
                        ORDER BY tl.sort_order ASC, tl.id ASC
                    ");
                    $stmt->bind_param("sii", $identifier, $dayNum, $hnum);
                    $stmt->execute();
                    $q = $stmt->get_result();

                    $subject = null;
                    $classes = [];
                    $rooms   = [];

                    while ($row = $q->fetch_assoc()) {
                        if ($subject === null && !empty($row['subject_name'])) {
                            $subject = $row['subject_name'];
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

                    $data[$d][$hnum] = [
                        'subject' => $subject,
                        'lines'   => $classes,
                        'rooms'   => $rooms,
                    ];
                    $stmt->close();
                    break;

                // ---- AULA ----
                case 'laboratorio':
                    $stmt = $conn->prepare("
                                                SELECT tl.id AS lesson_id, s.name AS subject_name, c.name AS class_name
                                                FROM rooms r
                                                INNER JOIN timetable_lesson_rooms tlr ON tlr.room_id = r.id
                                                INNER JOIN timetable_lessons tl ON tl.id = tlr.lesson_id
                                                INNER JOIN timetable_slots ts ON ts.id = tl.slot_id
                                                INNER JOIN classes c ON c.id = ts.class_id
                                                LEFT JOIN subjects s ON s.id = tl.subject_id
                                                WHERE r.name = ?
                                                    AND ts.day = ?
                                                    AND ts.hour = ?
                                                ORDER BY tl.sort_order ASC, tl.id ASC
                    ");
                                        $stmt->bind_param("sii", $identifier, $dayNum, $hnum);
                    $stmt->execute();
                    $q = $stmt->get_result();

                    $subject = null;
                    $pairs   = [];

                    while ($row = $q->fetch_assoc()) {
                        if ($subject === null && !empty($row['subject_name'])) {
                            $subject = $row['subject_name'];
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

                        $hasTeacher = false;
                        while ($trow = $tres->fetch_assoc()) {
                            $hasTeacher = true;
                            $pair = $row['class_name'] . ' (' . $trow['name'] . ')';
                            $pairs[$pair] = true;
                        }
                        $tstmt->close();

                        if (!$hasTeacher) {
                            $pairs[$row['class_name']] = true;
                        }
                    }

                    $data[$d][$hnum] = [
                        'subject' => $subject,
                        'lines'   => array_keys($pairs),
                        'rooms'   => [],
                    ];
                    $stmt->close();
                    break;
            }
        }
    }

    return $data;
}


class _OrarioPDF extends Fpdf\Fpdf
{
    public string $pageTitle = '';

    public function Header(): void
    {
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(13, 110, 253); // Bootstrap Primary Blue #0d6efd
        $this->Cell(0, 9, mb_convert_encoding($this->pageTitle, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(108, 117, 125); // Bootstrap Secondary Text #6c757d
        $this->Cell(0, 4, mb_convert_encoding('Anno Scolastico ' . YEAR, 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(3);
    }

    public function Footer(): void
    {
        $this->SetY(-11);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(0, 5, mb_convert_encoding(APP_NAME . ' - Copyright (C) 2025-' . date('Y') . ' EmmeV. - Ultimo aggiornamento: ' . date('d/m/Y'), 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    }
}


function _renderPDF(string $title, string $filename, array $days, array $hours, array $data): void
{
    $pdf = new _OrarioPDF('L', 'mm', 'A4');
    $pdf->pageTitle = $title;
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 14);
    $pdf->AddPage();

    $pageW    = 297 - 20; // 277 mm
    $hourColW = 25;
    $dayColW  = ($pageW - $hourColW) / count($days); // ~42 mm
    $rowH     = 22;
    $headerH  = 9;

    // ---- Intestazione colonne (giorni) ----
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(13, 110, 253); // Bootstrap Primary Blue #0d6efd
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetDrawColor(222, 226, 230); // #dee2e6

    $giorni = array_map(function($d) {
        return mb_convert_encoding($d, 'ISO-8859-1', 'UTF-8');
    }, $days);

    $pdf->Cell($hourColW, $headerH, mb_convert_encoding('Ora/Giorno', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
    foreach ($giorni as $d) {
        $pdf->Cell($dayColW, $headerH, $d, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // ---- Righe ore ----
    foreach ($hours as $hnum => $hlabel) {
        $pdf->SetTextColor(33, 37, 41);
        $xStart = $pdf->GetX();
        $yStart = $pdf->GetY();

        // Colonna ORA
        $pdf->SetFillColor(248, 249, 250); // #f8f9fa
        $pdf->SetDrawColor(222, 226, 230);
        $pdf->SetFont('Arial', 'B', 7.5);
        $pdf->MultiCell($hourColW, $rowH / 2, mb_convert_encoding($hlabel, 'ISO-8859-1', 'UTF-8'), 1, 'C', true);
        $pdf->SetXY($xStart + $hourColW, $yStart);

        // Colonne GIORNO
        foreach ($days as $d) {
            $cell = $data[$d][$hnum];
            $x    = $pdf->GetX();
            $y    = $pdf->GetY();

            if ($cell['subject'] !== null) {
                // Cella piena - Sfondo tenue Bootstrap #e7f1ff
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetDrawColor(222, 226, 230);
                $pdf->Rect($x, $y, $dayColW, $rowH, 'FD');

                // Materia - Primary emphasis #0a58ca
                $pdf->SetFont('Arial', 'B', 8.5);
                $pdf->SetTextColor(10, 88, 202);
                $pdf->SetXY($x + 1, $y + 2);
                $pdf->MultiCell($dayColW - 2, 4, mb_convert_encoding($cell['subject'], 'ISO-8859-1', 'UTF-8'), 0, 'C');

                // Righe secondarie (docenti / classi) - Dark body text #212529
                if (!empty($cell['lines'])) {
                    $linesStr = joinList($cell['lines']);
                    $pdf->SetFont('Arial', '', 7);
                    $pdf->SetTextColor(33, 37, 41);
                    $pdf->SetXY($x + 1, $pdf->GetY() + 0.5);
                    $pdf->MultiCell($dayColW - 2, 3.2, mb_convert_encoding($linesStr, 'ISO-8859-1', 'UTF-8'), 0, 'C');
                }

                // Aula/e - Secondary muted text #6c757d
                if (!empty($cell['rooms'])) {
                    $roomStr = joinList($cell['rooms']);
                    $pdf->SetFont('Arial', 'I', 6.5);
                    $pdf->SetTextColor(108, 117, 125);
                    $pdf->SetXY($x + 1, $y + $rowH - 5);
                    $pdf->Cell($dayColW - 2, 4, mb_convert_encoding($roomStr, 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
                }

            } else {
                // Cella vuota
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetDrawColor(222, 226, 230);
                $pdf->Rect($x, $y, $dayColW, $rowH, 'FD');
            }

            // Bordo
            $pdf->SetDrawColor(222, 226, 230);
            $pdf->Rect($x, $y, $dayColW, $rowH, 'D');
            $pdf->SetXY($x + $dayColW, $y);
        }

        $pdf->Ln($rowH);
    }

    $pdf->Output('I', $filename);
    exit;
}

function joinList(array $arr): string
{
    if (empty($arr)) return '';
    if (count($arr) === 1) return $arr[0];
    $last = array_pop($arr);
    return implode(', ', $arr) . ' e ' . $last;
}