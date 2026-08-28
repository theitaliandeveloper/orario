-- Orario Scuola
-- Migrazione opzionale: schema legacy -> new_schema.sql (normalizzato)
--
-- Assunzioni:
-- 1) Hai gia importato new_schema.sql in un database vuoto.
-- 2) Hai le vecchie tabelle caricate con suffisso _legacy:
--    classes_legacy(id, name, section)
--    subjects_legacy(id, name, teacher, room)
--    timetable_legacy(id, class_id, day, hour, subject_id)
--
-- Se parti da un database legacy esistente con nomi originali:
-- RENAME TABLE classes TO classes_legacy, subjects TO subjects_legacy, timetable TO timetable_legacy;
-- Poi importa new_schema.sql e quindi esegui questo file.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS schema_versions (
  version INT UNSIGNED NOT NULL PRIMARY KEY,
  description VARCHAR(255) NOT NULL,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 1) Classi
INSERT INTO classes (name, section)
SELECT DISTINCT c.name, c.section
FROM classes_legacy c
WHERE c.name IS NOT NULL AND TRIM(c.name) <> '';

-- 2) Materie (deduplicate per nome)
INSERT INTO subjects (name)
SELECT DISTINCT s.name
FROM subjects_legacy s
WHERE s.name IS NOT NULL AND TRIM(s.name) <> '';

-- 3) Docenti
INSERT INTO teachers (name)
SELECT DISTINCT s.teacher
FROM subjects_legacy s
WHERE s.teacher IS NOT NULL
  AND TRIM(s.teacher) <> ''
  AND s.teacher COLLATE utf8mb4_unicode_ci <> 'No Lezione'
  AND s.teacher COLLATE utf8mb4_unicode_ci <> 'sconosciuto';

-- 4) Aule/Laboratori
INSERT INTO rooms (name)
SELECT DISTINCT s.room
FROM subjects_legacy s
WHERE s.room IS NOT NULL
  AND TRIM(s.room) <> '';

-- 5) Mapping classi legacy -> nuove
DROP TEMPORARY TABLE IF EXISTS tmp_class_map;
CREATE TEMPORARY TABLE tmp_class_map (
  old_class_id INT NOT NULL,
  new_class_id INT NOT NULL,
  PRIMARY KEY (old_class_id)
);

INSERT INTO tmp_class_map (old_class_id, new_class_id)
SELECT cl.id, c.id
FROM classes_legacy cl
INNER JOIN classes c ON c.name COLLATE utf8mb4_unicode_ci = cl.name COLLATE utf8mb4_unicode_ci;

-- 6) Mapping materie legacy -> nuova materia (per nome)
DROP TEMPORARY TABLE IF EXISTS tmp_subject_map;
CREATE TEMPORARY TABLE tmp_subject_map (
  old_subject_id INT NOT NULL,
  new_subject_id INT NOT NULL,
  PRIMARY KEY (old_subject_id)
);

INSERT INTO tmp_subject_map (old_subject_id, new_subject_id)
SELECT sl.id, MIN(s.id) AS new_subject_id
FROM subjects_legacy sl
INNER JOIN subjects s ON s.name COLLATE utf8mb4_unicode_ci = sl.name COLLATE utf8mb4_unicode_ci
GROUP BY sl.id;

-- 7) Crea snapshot lezioni legacy con giorno numerico
DROP TEMPORARY TABLE IF EXISTS tmp_old_lessons;
CREATE TEMPORARY TABLE tmp_old_lessons (
  old_timetable_id INT NOT NULL,
  new_class_id INT NOT NULL,
  day_num TINYINT UNSIGNED NOT NULL,
  hour_num TINYINT UNSIGNED NOT NULL,
  new_subject_id INT UNSIGNED NOT NULL,
  teacher_name VARCHAR(150) DEFAULT NULL,
  room_name VARCHAR(150) DEFAULT NULL,
  PRIMARY KEY (old_timetable_id)
);

INSERT INTO tmp_old_lessons (
  old_timetable_id,
  new_class_id,
  day_num,
  hour_num,
  new_subject_id,
  teacher_name,
  room_name
)
SELECT
  t.id,
  cm.new_class_id,
  CASE t.day
    WHEN 'Lunedì' THEN 1
    WHEN 'Martedì' THEN 2
    WHEN 'Mercoledì' THEN 3
    WHEN 'Giovedì' THEN 4
    WHEN 'Venerdì' THEN 5
    WHEN 'Sabato' THEN 6
    ELSE 0
  END AS day_num,
  t.hour,
  sm.new_subject_id,
  sl.teacher,
  sl.room
FROM timetable_legacy t
INNER JOIN tmp_class_map cm ON cm.old_class_id = t.class_id
INNER JOIN tmp_subject_map sm ON sm.old_subject_id = t.subject_id
INNER JOIN subjects_legacy sl ON sl.id = t.subject_id
WHERE t.hour >= 1;

DELETE FROM tmp_old_lessons WHERE day_num = 0;

-- 8) Slot orario
INSERT INTO timetable_slots (class_id, day, hour)
SELECT DISTINCT
  ol.new_class_id,
  ol.day_num,
  ol.hour_num
FROM tmp_old_lessons ol;

-- 9) Inserisci lezioni (una per riga legacy)
INSERT INTO timetable_lessons (slot_id, subject_id, remote, sort_order)
SELECT
  ts.id AS slot_id,
  ol.new_subject_id,
  0 AS remote,
  ROW_NUMBER() OVER (
    PARTITION BY ol.new_class_id, ol.day_num, ol.hour_num
    ORDER BY ol.old_timetable_id
  ) - 1 AS sort_order
FROM tmp_old_lessons ol
INNER JOIN timetable_slots ts
  ON ts.class_id = ol.new_class_id
 AND ts.day = ol.day_num
 AND ts.hour = ol.hour_num;

-- 10) Mappa righe legacy -> lesson_id nuovi
DROP TEMPORARY TABLE IF EXISTS tmp_old_ranked;
CREATE TEMPORARY TABLE tmp_old_ranked (
  old_timetable_id INT NOT NULL,
  new_class_id INT NOT NULL,
  day_num TINYINT UNSIGNED NOT NULL,
  hour_num TINYINT UNSIGNED NOT NULL,
  new_subject_id INT UNSIGNED NOT NULL,
  rn INT NOT NULL,
  teacher_name VARCHAR(150) DEFAULT NULL,
  room_name VARCHAR(150) DEFAULT NULL,
  PRIMARY KEY (old_timetable_id)
);

INSERT INTO tmp_old_ranked (
  old_timetable_id,
  new_class_id,
  day_num,
  hour_num,
  new_subject_id,
  rn,
  teacher_name,
  room_name
)
SELECT
  ol.old_timetable_id,
  ol.new_class_id,
  ol.day_num,
  ol.hour_num,
  ol.new_subject_id,
  ROW_NUMBER() OVER (
    PARTITION BY ol.new_class_id, ol.day_num, ol.hour_num, ol.new_subject_id
    ORDER BY ol.old_timetable_id
  ) AS rn,
  ol.teacher_name,
  ol.room_name
FROM tmp_old_lessons ol;

DROP TEMPORARY TABLE IF EXISTS tmp_new_ranked;
CREATE TEMPORARY TABLE tmp_new_ranked (
  lesson_id BIGINT UNSIGNED NOT NULL,
  new_class_id INT NOT NULL,
  day_num TINYINT UNSIGNED NOT NULL,
  hour_num TINYINT UNSIGNED NOT NULL,
  new_subject_id INT UNSIGNED NOT NULL,
  rn INT NOT NULL,
  PRIMARY KEY (lesson_id)
);

INSERT INTO tmp_new_ranked (
  lesson_id,
  new_class_id,
  day_num,
  hour_num,
  new_subject_id,
  rn
)
SELECT
  tl.id,
  ts.class_id,
  ts.day,
  ts.hour,
  tl.subject_id,
  ROW_NUMBER() OVER (
    PARTITION BY ts.class_id, ts.day, ts.hour, tl.subject_id
    ORDER BY tl.id
  ) AS rn
FROM timetable_lessons tl
INNER JOIN timetable_slots ts ON ts.id = tl.slot_id;

DROP TEMPORARY TABLE IF EXISTS tmp_legacy_new_lessons;
CREATE TEMPORARY TABLE tmp_legacy_new_lessons (
  old_timetable_id INT NOT NULL,
  lesson_id BIGINT UNSIGNED NOT NULL,
  teacher_name VARCHAR(150) DEFAULT NULL,
  room_name VARCHAR(150) DEFAULT NULL,
  PRIMARY KEY (old_timetable_id),
  KEY idx_lesson_id (lesson_id)
);

INSERT INTO tmp_legacy_new_lessons (old_timetable_id, lesson_id, teacher_name, room_name)
SELECT
  o.old_timetable_id,
  n.lesson_id,
  o.teacher_name,
  o.room_name
FROM tmp_old_ranked o
INNER JOIN tmp_new_ranked n
  ON n.new_class_id = o.new_class_id
 AND n.day_num = o.day_num
 AND n.hour_num = o.hour_num
 AND n.new_subject_id = o.new_subject_id
 AND n.rn = o.rn;

-- 11) Link docenti alle lezioni
INSERT IGNORE INTO timetable_lesson_teachers (lesson_id, teacher_id)
SELECT
  lnk.lesson_id,
  t.id
FROM tmp_legacy_new_lessons lnk
INNER JOIN teachers t ON t.name COLLATE utf8mb4_unicode_ci = lnk.teacher_name COLLATE utf8mb4_unicode_ci
WHERE lnk.teacher_name IS NOT NULL
  AND TRIM(lnk.teacher_name) <> ''
  AND lnk.teacher_name <> 'No Lezione'
  AND lnk.teacher_name <> 'sconosciuto';

-- 12) Link aule alle lezioni
INSERT IGNORE INTO timetable_lesson_rooms (lesson_id, room_id)
SELECT
  lnk.lesson_id,
  r.id
FROM tmp_legacy_new_lessons lnk
INNER JOIN rooms r ON r.name COLLATE utf8mb4_unicode_ci = lnk.room_name COLLATE utf8mb4_unicode_ci
WHERE lnk.room_name IS NOT NULL
  AND TRIM(lnk.room_name) <> '';

INSERT IGNORE INTO schema_versions (version, description)
VALUES (1, 'Schema normalizzato iniziale');

COMMIT;

-- Facoltativo: copie credenziali admin da tabella legacy, se presente.
-- INSERT IGNORE INTO admin (username, password)
-- SELECT username, password FROM admin_legacy;
