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
include_once __DIR__ . '/../config/config.php';

/**
 * Esporta un orario in PDF.
 *
 * @param mysqli $conn       Connessione al DB
 * @param string $type       Tipo di orario: 'class' | 'teacher' | 'room'
 * @param string $identifier ID (per class) o nome (per teacher/room)
 *
 * Uso:
 *   // Orario classe
 *   exportTimetablePDF($conn, 'class', $class_id);
 *
 *   // Orario docente
 *   exportTimetablePDF($conn, 'teacher', 'Mario Rossi');
 *
 *   // Orario laboratori
 *   exportTimetablePDF($conn, 'room', 'Laboratorio Informatica 1');
 */
function exportTimetablePDF(mysqli $conn, string $type, $identifier): void
{
    $days = ["Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"];
    $hours = [
        1 => "Prima ora\n7:50 - 8:50",
        2 => "Seconda ora\n8:50 - 9:45",
        3 => "Terza ora\n9:55 - 10:50",
        4 => "Quarta ora\n10:50 - 11:45",
        5 => "Quinta ora\n11:55 - 12:50",
        6 => "Sesta ora\n12:50 - 13:50",
    ];

    // --- Titolo e dati in base al tipo ---
    switch ($type) {
        case 'class':
            $class_id = intval($identifier);
            $row = $conn->query("SELECT name FROM classes WHERE id = $class_id LIMIT 1")->fetch_assoc();
            $title    = 'Orario classe ' . $row['name'];
            $filename = 'orario_classe_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $row['name']);
            break;

        case 'teacher':
            $title    = 'Orario docente ' . $identifier;
            $filename = 'orario_docente_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $identifier);
            break;

        case 'room':
            $title    = 'Orario ' . $identifier;
            $filename = 'orario_laboratorio_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $identifier);
            break;

        default:
            http_response_code(400);
            exit('Tipo non valido.');
    }

    // --- Carica i dati dell'orario ---
    $data = _loadTimetableData($conn, $type, $identifier, $days, array_keys($hours));

    // --- Genera il PDF ---
    _renderPDF($title, $filename . '.pdf', $days, $hours, $data);
}


// -----------------------------------------------------------------------
// Funzioni private
// -----------------------------------------------------------------------

/**
 * Carica tutti i dati dell'orario in un array $data[$day][$hour].
 * Ogni cella contiene: subject, lines[] (righe secondarie), room.
 */
function _loadTimetableData(mysqli $conn, string $type, $identifier, array $days, array $hournums): array
{
    $data = [];

    foreach ($days as $d) {
        $data[$d] = [];
        $escaped_d = $conn->real_escape_string($d);

        foreach ($hournums as $hnum) {

            switch ($type) {

                // ---- CLASSE ----
                case 'class':
                    $class_id = intval($identifier);
                    $q = $conn->query("
                        SELECT subjects.name, subjects.teacher, subjects.room
                        FROM timetable
                        LEFT JOIN subjects ON timetable.subject_id = subjects.id
                        WHERE timetable.class_id = $class_id
                          AND timetable.day = '$escaped_d'
                          AND timetable.hour = $hnum
                    ");

                    $subject  = null;
                    $room     = null;
                    $teachers = [];

                    while ($row = $q->fetch_assoc()) {
                        if ($subject === null) {
                            $subject = $row['name'];
                            $room    = $row['room'];
                        }
                        if (!empty($row['teacher'])) {
                            $teachers[] = $row['teacher'];
                        }
                    }

                    $data[$d][$hnum] = [
                        'subject' => $subject,
                        'lines'   => $teachers,   // docenti
                        'room'    => $room,
                    ];
                    break;

                // ---- DOCENTE ----
                case 'teacher':
                    $escaped_teacher = $conn->real_escape_string($identifier);
                    $q = $conn->query("
                        SELECT subjects.name, classes.name AS class_name, subjects.room
                        FROM timetable
                        LEFT JOIN subjects ON timetable.subject_id = subjects.id
                        LEFT JOIN classes  ON timetable.class_id   = classes.id
                        WHERE subjects.teacher = '$escaped_teacher'
                          AND timetable.day    = '$escaped_d'
                          AND timetable.hour   = $hnum
                    ");

                    $subject = null;
                    $room    = null;
                    $classes = [];

                    while ($row = $q->fetch_assoc()) {
                        if ($subject === null) {
                            $subject = $row['name'];
                            $room    = $row['room'];
                        }
                        if (!empty($row['class_name'])) {
                            $classes[] = $row['class_name'];
                        }
                    }

                    $data[$d][$hnum] = [
                        'subject' => $subject,
                        'lines'   => $classes,    // classi
                        'room'    => $room,
                    ];
                    break;

                // ---- AULA ----
                case 'room':
                    $escaped_room = $conn->real_escape_string($identifier);
                    $q = $conn->query("
                        SELECT subjects.name AS subject_name, subjects.teacher, classes.name AS class_name
                        FROM timetable
                        LEFT JOIN subjects ON timetable.subject_id = subjects.id
                        LEFT JOIN classes  ON timetable.class_id   = classes.id
                        WHERE subjects.room = '$escaped_room'
                          AND timetable.day = '$escaped_d'
                          AND timetable.hour = $hnum
                    ");

                    $subject = null;
                    $pairs   = [];

                    while ($row = $q->fetch_assoc()) {
                        if ($subject === null) {
                            $subject = $row['subject_name'];
                        }
                        $pair = $row['class_name'] . ' (' . $row['teacher'] . ')';
                        $pairs[$pair] = true; // deduplicazione
                    }

                    $data[$d][$hnum] = [
                        'subject' => $subject,
                        'lines'   => array_keys($pairs),  // classe + docente
                        'room'    => null,                 // siamo già nell'aula
                    ];
                    break;
            }
        }
    }

    return $data;
}


/**
 * Classe FPDF con header e footer personalizzati.
 */
class _OrarioPDF extends Fpdf\Fpdf
{
    public string $pageTitle = '';

    public function Header(): void
    {
        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(30, 30, 30);
        $this->Cell(0, 9, $this->pageTitle, 0, 1, 'C');
        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 4, 'Anno Scolastico ' . YEAR, 0, 1, 'C');
        $this->Ln(3);
    }

    public function Footer(): void
    {
        $this->SetY(-11);
        $this->SetFont('Arial', 'I', 6.5);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 5, 'Orario Scuola - Copyright (C) 2025-2026 EmmeV. - Ultimo aggiornamento: ' . date('d/m/Y'), 0, 0, 'C');
    }
}


/**
 * Disegna il PDF e lo invia al browser.
 */
function _renderPDF(string $title, string $filename, array $days, array $hours, array $data): void
{
    // Layout A4 landscape, margini 10mm
    $pdf = new _OrarioPDF('L', 'mm', 'A4');
    $pdf->pageTitle = $title;
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 14);
    $pdf->AddPage();

    $pageW    = 297 - 20;           // larghezza utile
    $hourColW = 24;                 // colonna "ora"
    $dayColW  = ($pageW - $hourColW) / count($days);
    $rowH     = 22;
    $headerH  = 8;

    // ---- Intestazione colonne (giorni) ----
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(44, 62, 80);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetDrawColor(200, 200, 200);
    $giorni = mb_convert_encoding($days, 'Windows-1252');
    $pdf->Cell($hourColW, $headerH, '', 1, 0, 'C', true);
    foreach ($giorni as $d) {
        $pdf->Cell($dayColW, $headerH, $d, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // ---- Righe ore ----
    foreach ($hours as $hnum => $hlabel) {
        $pdf->SetTextColor(0, 0, 0);
        $xStart = $pdf->GetX();
        $yStart = $pdf->GetY();

        // Colonna ORA
        $pdf->SetFillColor(236, 240, 241);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->MultiCell($hourColW, $rowH / 2, $hlabel, 1, 'C', true);
        $pdf->SetXY($xStart + $hourColW, $yStart);

        // Colonne GIORNO
        foreach ($days as $d) {
            $cell = $data[$d][$hnum];
            $x    = $pdf->GetX();
            $y    = $pdf->GetY();

            if ($cell['subject'] !== null) {
                // Cella piena
                $pdf->SetFillColor(214, 234, 248);
                $pdf->Rect($x, $y, $dayColW, $rowH, 'FD');

                // Materia
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetXY($x + 1, $y + 1.5);
                $pdf->MultiCell($dayColW - 2, 4, $cell['subject'], 0, 'C');

                // Righe secondarie (docenti / classi / coppie classe+docente)
                if (!empty($cell['lines'])) {
                    $linesStr = implode(', ', $cell['lines']);
                    $pdf->SetFont('Arial', '', 6.5);
                    $pdf->SetTextColor(50, 50, 50);
                    $pdf->SetXY($x + 1, $pdf->GetY());
                    $pdf->MultiCell($dayColW - 2, 3.2, $linesStr, 0, 'C');
                    $pdf->SetTextColor(0, 0, 0);
                }

                // Aula (solo per classe e docente)
                if (!empty($cell['room'])) {
                    $pdf->SetFont('Arial', 'I', 6);
                    $pdf->SetTextColor(100, 100, 100);
                    $pdf->SetXY($x + 1, $y + $rowH - 5);
                    $pdf->Cell($dayColW - 2, 4, 'Aula: ' . $cell['room'], 0, 0, 'C');
                    $pdf->SetTextColor(0, 0, 0);
                }

            } else {
                // Cella vuota
                $pdf->SetFillColor(250, 250, 250);
                $pdf->Rect($x, $y, $dayColW, $rowH, 'FD');
            }

            // Bordo
            $pdf->Rect($x, $y, $dayColW, $rowH, 'D');
            $pdf->SetXY($x + $dayColW, $y);
        }

        $pdf->Ln($rowH);
    }

    // Output
    $pdf->Output('D', $filename);
    exit;
}