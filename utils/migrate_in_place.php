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

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Questo script deve essere eseguito da CLI.\n");
    exit(1);
}

$yes = in_array('--yes', $argv, true);
$help = in_array('--help', $argv, true) || in_array('-h', $argv, true);

if ($help) {
    echo "Migrazione in-place schema legacy -> schema nuovo (stesso database).\n\n";
    echo "Uso:\n";
    echo "  php utils/migrate_in_place.php --yes\n\n";
    echo "Opzioni:\n";
    echo "  --yes    Esegue la migrazione senza richiesta interattiva.\n";
    echo "  --help   Mostra questo messaggio.\n";
    exit(0);
}

if (!$yes) {
    echo "ATTENZIONE: la migrazione rinomina le tabelle legacy e crea le nuove tabelle nello stesso database.\n";
    echo "Non dare per scontato che la migrazione vada a buon fine, esegui prima un backup del DB.\n";
    echo "Sicuro di voler continuare? (conferma digitando YES): ";
    $line = trim((string)fgets(STDIN));
    if ($line !== 'YES') {
        echo "Migrazione annullata.\n";
        exit(0);
    }
}

require_once __DIR__ . '/../htdocs/lib/variables.php';

if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
    fwrite(STDERR, "Errore: variabili di connessione al database non definite in lib/variables.php\n");
    exit(1);
}

if (!MAINTENANCE) {
    fwrite(STDERR, "Errore: La modalità di manutenzione deve essere abilitata per eseguire la migrazione.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

function out(string $msg): void
{
    echo $msg . PHP_EOL;
}

function table_exists(mysqli $conn, string $table): bool
{
    $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $exists;
}

function run(mysqli $conn, string $sql): void
{
    $conn->query($sql);
}

try {
    out('Connessione DB riuscita.');

    $legacyTables = ['classes', 'subjects', 'timetable'];
    foreach ($legacyTables as $tbl) {
        if (!table_exists($conn, $tbl)) {
            throw new RuntimeException("Tabella legacy mancante: {$tbl}");
        }
    }

    $newTables = [
        'teachers',
        'rooms',
        'timetable_slots',
        'timetable_lessons',
        'timetable_lesson_teachers',
        'timetable_lesson_rooms',
    ];

    foreach ($newTables as $tbl) {
        if (table_exists($conn, $tbl)) {
            throw new RuntimeException("La tabella {$tbl} esiste gia. Migrazione probabilmente gia eseguita.");
        }
    }

    $suffix = 'legacy_' . date('Ymd_His');
    $classesLegacy = 'classes_' . $suffix;
    $subjectsLegacy = 'subjects_' . $suffix;
    $timetableLegacy = 'timetable_' . $suffix;

    out('Rinomino tabelle legacy...');
    run($conn, "RENAME TABLE classes TO `{$classesLegacy}`, subjects TO `{$subjectsLegacy}`, timetable TO `{$timetableLegacy}`");

    out('Creo nuove tabelle (stesso database)...');
    run($conn, "CREATE TABLE classes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        section VARCHAR(50) DEFAULT NULL,
        UNIQUE KEY uq_classes_name (name)
    )");

    run($conn, "CREATE TABLE subjects (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        external_id VARCHAR(100) DEFAULT NULL,
        name VARCHAR(150) NOT NULL,
        full_name VARCHAR(255) DEFAULT NULL,
        bg_color CHAR(6) DEFAULT NULL,
        text_color CHAR(6) DEFAULT NULL,
        UNIQUE KEY uq_subjects_external_id (external_id)
    )");

    run($conn, "CREATE TABLE teachers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        external_id VARCHAR(100) DEFAULT NULL,
        name VARCHAR(150) NOT NULL,
        username VARCHAR(150) DEFAULT NULL,
        UNIQUE KEY uq_teachers_external_id (external_id)
    )");

    run($conn, "CREATE TABLE rooms (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        external_id VARCHAR(100) DEFAULT NULL,
        name VARCHAR(150) NOT NULL,
        UNIQUE KEY uq_rooms_external_id (external_id),
        UNIQUE KEY uq_rooms_name (name)
    )");

    run($conn, "CREATE TABLE timetable_slots (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        class_id INT UNSIGNED NOT NULL,
        day TINYINT UNSIGNED NOT NULL,
        hour TINYINT UNSIGNED NOT NULL,
        UNIQUE KEY uq_timetable_slot (class_id, day, hour),
        INDEX idx_slots_class_day (class_id, day),
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        CHECK (day BETWEEN 1 AND 6),
        CHECK (hour >= 1)
    )");

    run($conn, "CREATE TABLE timetable_lessons (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slot_id BIGINT UNSIGNED NOT NULL,
        subject_id INT UNSIGNED DEFAULT NULL,
        remote BOOLEAN NOT NULL DEFAULT FALSE,
        sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        INDEX idx_lessons_slot (slot_id),
        INDEX idx_lessons_subject (subject_id),
        FOREIGN KEY (slot_id) REFERENCES timetable_slots(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
    )");

    run($conn, "CREATE TABLE timetable_lesson_teachers (
        lesson_id BIGINT UNSIGNED NOT NULL,
        teacher_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (lesson_id, teacher_id),
        FOREIGN KEY (lesson_id) REFERENCES timetable_lessons(id) ON DELETE CASCADE,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
    )");

    run($conn, "CREATE TABLE timetable_lesson_rooms (
        lesson_id BIGINT UNSIGNED NOT NULL,
        room_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (lesson_id, room_id),
        FOREIGN KEY (lesson_id) REFERENCES timetable_lessons(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
    )");

    out('Copio classi...');
    run($conn, "INSERT INTO classes (name, section)
                SELECT name, MIN(section)
                FROM `{$classesLegacy}`
                WHERE name IS NOT NULL AND TRIM(name) <> ''
                GROUP BY name");

    out('Copio materie...');
    run($conn, "INSERT INTO subjects (name)
                SELECT DISTINCT name
                FROM `{$subjectsLegacy}`
                WHERE name IS NOT NULL AND TRIM(name) <> ''");

    out('Copio docenti e aule...');
    run($conn, "INSERT INTO teachers (name)
                SELECT DISTINCT teacher
                FROM `{$subjectsLegacy}`
                WHERE teacher IS NOT NULL
                  AND TRIM(teacher) <> ''
                  AND teacher COLLATE utf8mb4_unicode_ci <> 'No Lezione'
                  AND teacher COLLATE utf8mb4_unicode_ci <> 'sconosciuto'");

    run($conn, "INSERT INTO rooms (name)
                SELECT DISTINCT room
                FROM `{$subjectsLegacy}`
                WHERE room IS NOT NULL AND TRIM(room) <> ''");

    out('Creo slot orari...');
    run($conn, "INSERT INTO timetable_slots (class_id, day, hour)
                SELECT DISTINCT
                    c.id,
                    CASE t.day
                        WHEN 'Lunedì' THEN 1
                        WHEN 'Martedì' THEN 2
                        WHEN 'Mercoledì' THEN 3
                        WHEN 'Giovedì' THEN 4
                        WHEN 'Venerdì' THEN 5
                        WHEN 'Sabato' THEN 6
                        ELSE 0
                    END AS day_num,
                    t.hour
                FROM `{$timetableLegacy}` t
                INNER JOIN `{$classesLegacy}` cl ON cl.id = t.class_id
                INNER JOIN classes c ON c.name COLLATE utf8mb4_unicode_ci = cl.name COLLATE utf8mb4_unicode_ci
                WHERE t.hour >= 1
                  AND t.subject_id IS NOT NULL
                  AND CASE t.day
                        WHEN 'Lunedì' THEN 1
                        WHEN 'Martedì' THEN 2
                        WHEN 'Mercoledì' THEN 3
                        WHEN 'Giovedì' THEN 4
                        WHEN 'Venerdì' THEN 5
                        WHEN 'Sabato' THEN 6
                        ELSE 0
                      END BETWEEN 1 AND 6");

    out('Creo lezioni...');
    run($conn, "INSERT INTO timetable_lessons (slot_id, subject_id, remote, sort_order)
                SELECT
                    ts.id,
                    s.id,
                    0,
                    ROW_NUMBER() OVER (
                        PARTITION BY ts.id
                        ORDER BY t.id
                    ) - 1
                FROM `{$timetableLegacy}` t
                INNER JOIN `{$classesLegacy}` cl ON cl.id = t.class_id
                INNER JOIN classes c ON c.name COLLATE utf8mb4_unicode_ci = cl.name COLLATE utf8mb4_unicode_ci
                INNER JOIN `{$subjectsLegacy}` sl ON sl.id = t.subject_id
                INNER JOIN subjects s ON s.name COLLATE utf8mb4_unicode_ci = sl.name COLLATE utf8mb4_unicode_ci
                INNER JOIN timetable_slots ts
                    ON ts.class_id = c.id
                   AND ts.hour = t.hour
                   AND ts.day = CASE t.day
                        WHEN 'Lunedì' THEN 1
                        WHEN 'Martedì' THEN 2
                        WHEN 'Mercoledì' THEN 3
                        WHEN 'Giovedì' THEN 4
                        WHEN 'Venerdì' THEN 5
                        WHEN 'Sabato' THEN 6
                        ELSE 0
                   END
                WHERE t.hour >= 1");

    out('Associo docenti/aule alle lezioni...');
    run($conn, "DROP TEMPORARY TABLE IF EXISTS tmp_old_ranked");
    run($conn, "CREATE TEMPORARY TABLE tmp_old_ranked (
        old_timetable_id INT NOT NULL,
        slot_id BIGINT UNSIGNED NOT NULL,
        subject_id INT UNSIGNED NOT NULL,
        rn INT NOT NULL,
        teacher_name VARCHAR(150) DEFAULT NULL,
        room_name VARCHAR(150) DEFAULT NULL,
        PRIMARY KEY (old_timetable_id)
    )");

    run($conn, "INSERT INTO tmp_old_ranked (old_timetable_id, slot_id, subject_id, rn, teacher_name, room_name)
                SELECT
                    t.id,
                    ts.id,
                    s.id,
                    ROW_NUMBER() OVER (
                        PARTITION BY ts.id, s.id
                        ORDER BY t.id
                    ) AS rn,
                    sl.teacher,
                    sl.room
                FROM `{$timetableLegacy}` t
                INNER JOIN `{$classesLegacy}` cl ON cl.id = t.class_id
                INNER JOIN classes c ON c.name COLLATE utf8mb4_unicode_ci = cl.name COLLATE utf8mb4_unicode_ci
                INNER JOIN `{$subjectsLegacy}` sl ON sl.id = t.subject_id
                INNER JOIN subjects s ON s.name COLLATE utf8mb4_unicode_ci = sl.name COLLATE utf8mb4_unicode_ci
                INNER JOIN timetable_slots ts
                    ON ts.class_id = c.id
                   AND ts.hour = t.hour
                   AND ts.day = CASE t.day
                        WHEN 'Lunedì' THEN 1
                        WHEN 'Martedì' THEN 2
                        WHEN 'Mercoledì' THEN 3
                        WHEN 'Giovedì' THEN 4
                        WHEN 'Venerdì' THEN 5
                        WHEN 'Sabato' THEN 6
                        ELSE 0
                   END");

    run($conn, "DROP TEMPORARY TABLE IF EXISTS tmp_new_ranked");
    run($conn, "CREATE TEMPORARY TABLE tmp_new_ranked (
        lesson_id BIGINT UNSIGNED NOT NULL,
        slot_id BIGINT UNSIGNED NOT NULL,
        subject_id INT UNSIGNED NOT NULL,
        rn INT NOT NULL,
        PRIMARY KEY (lesson_id)
    )");

    run($conn, "INSERT INTO tmp_new_ranked (lesson_id, slot_id, subject_id, rn)
                SELECT
                    tl.id,
                    tl.slot_id,
                    tl.subject_id,
                    ROW_NUMBER() OVER (
                        PARTITION BY tl.slot_id, tl.subject_id
                        ORDER BY tl.id
                    ) AS rn
                FROM timetable_lessons tl");

    run($conn, "DROP TEMPORARY TABLE IF EXISTS tmp_lesson_map");
    run($conn, "CREATE TEMPORARY TABLE tmp_lesson_map (
        lesson_id BIGINT UNSIGNED NOT NULL,
        teacher_name VARCHAR(150) DEFAULT NULL,
        room_name VARCHAR(150) DEFAULT NULL,
        KEY idx_lesson_id (lesson_id)
    )");

    run($conn, "INSERT INTO tmp_lesson_map (lesson_id, teacher_name, room_name)
                SELECT
                    n.lesson_id,
                    o.teacher_name,
                    o.room_name
                FROM tmp_old_ranked o
                INNER JOIN tmp_new_ranked n
                    ON n.slot_id = o.slot_id
                   AND n.subject_id = o.subject_id
                   AND n.rn = o.rn");

    run($conn, "INSERT IGNORE INTO timetable_lesson_teachers (lesson_id, teacher_id)
                SELECT
                    lm.lesson_id,
                    t.id
                FROM tmp_lesson_map lm
                INNER JOIN teachers t ON t.name COLLATE utf8mb4_unicode_ci = lm.teacher_name COLLATE utf8mb4_unicode_ci
                WHERE lm.teacher_name IS NOT NULL
                  AND TRIM(lm.teacher_name) <> ''
                  AND lm.teacher_name <> 'No Lezione'
                  AND lm.teacher_name <> 'sconosciuto'");

    run($conn, "INSERT IGNORE INTO timetable_lesson_rooms (lesson_id, room_id)
                SELECT
                    lm.lesson_id,
                    r.id
                FROM tmp_lesson_map lm
                INNER JOIN rooms r ON r.name COLLATE utf8mb4_unicode_ci = lm.room_name COLLATE utf8mb4_unicode_ci
                WHERE lm.room_name IS NOT NULL
                  AND TRIM(lm.room_name) <> ''");

    out('Aggiorno tabella admin (seed se vuota)...');
    run($conn, "INSERT INTO admin (username, password)
                SELECT 'admin', '$2y$10$IS9v8CJNJnRXslV1NWDSquAjJ0GgU1sm6spBmGp6mjTLiNApfGcQi'
                FROM DUAL
                WHERE NOT EXISTS (SELECT 1 FROM admin LIMIT 1)");

    $counts = [
        'classes' => 0,
        'subjects' => 0,
        'teachers' => 0,
        'rooms' => 0,
        'timetable_slots' => 0,
        'timetable_lessons' => 0,
    ];

    foreach (array_keys($counts) as $tbl) {
        $res = $conn->query("SELECT COUNT(*) AS cnt FROM {$tbl}");
        $counts[$tbl] = (int)$res->fetch_assoc()['cnt'];
    }

    out('Migrazione completata con successo.');
    out("Backup legacy creato con suffisso: {$suffix}");
    out('Riepilogo record:');
    foreach ($counts as $tbl => $cnt) {
        out(" - {$tbl}: {$cnt}");
    }

    out('Tabelle legacy mantenute per rollback manuale:');
    out(" - {$classesLegacy}");
    out(" - {$subjectsLegacy}");
    out(" - {$timetableLegacy}");

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Errore migrazione: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
