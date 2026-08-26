-- Orario Scuola
-- Schema nuovo

CREATE DATABASE IF NOT EXISTS school_timetable
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE school_timetable;


-- =========================================================
-- ADMIN
-- =========================================================

CREATE TABLE admin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);


-- =========================================================
-- CLASSI
-- =========================================================

CREATE TABLE classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(50) NOT NULL,
    section VARCHAR(50) DEFAULT NULL,

    UNIQUE KEY uq_classes_name (name)
);


-- =========================================================
-- MATERIE
-- =========================================================

CREATE TABLE subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    external_id VARCHAR(100) DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    full_name VARCHAR(255) DEFAULT NULL,

    bg_color CHAR(6) DEFAULT NULL,
    text_color CHAR(6) DEFAULT NULL,

    UNIQUE KEY uq_subjects_external_id (external_id)
);


-- =========================================================
-- DOCENTI
-- =========================================================

CREATE TABLE teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    external_id VARCHAR(100) DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    username VARCHAR(150) DEFAULT NULL,

    UNIQUE KEY uq_teachers_external_id (external_id)
);


-- =========================================================
-- AULE
-- =========================================================

CREATE TABLE rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    external_id VARCHAR(100) DEFAULT NULL,
    name VARCHAR(150) NOT NULL,

    UNIQUE KEY uq_rooms_external_id (external_id),
    UNIQUE KEY uq_rooms_name (name)
);


-- =========================================================
-- SLOT ORARIO
--
-- Una riga identifica:
-- classe + giorno + ora
--
-- Esempio:
-- 1A / Tuesday / 3
-- =========================================================

CREATE TABLE timetable_slots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    class_id INT UNSIGNED NOT NULL,

    -- 1 = Monday
    -- 2 = Tuesday
    -- 3 = Wednesday
    -- 4 = Thursday
    -- 5 = Friday
    -- 6 = Saturday
    day TINYINT UNSIGNED NOT NULL,

    hour TINYINT UNSIGNED NOT NULL,

    UNIQUE KEY uq_timetable_slot (
        class_id,
        day,
        hour
    ),

    INDEX idx_slots_class_day (
        class_id,
        day
    ),

    FOREIGN KEY (class_id)
        REFERENCES classes(id)
        ON DELETE CASCADE,

    CHECK (day BETWEEN 1 AND 6),
    CHECK (hour >= 1)
);


-- =========================================================
-- LEZIONI
--
-- Uno slot può avere più lezioni.
--
-- Questo gestisce le compresenze / più attività
-- nello stesso giorno e ora.
-- =========================================================

CREATE TABLE timetable_lessons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    slot_id BIGINT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED DEFAULT NULL,

    remote BOOLEAN NOT NULL DEFAULT FALSE,

    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    INDEX idx_lessons_slot (
        slot_id
    ),

    INDEX idx_lessons_subject (
        subject_id
    ),

    FOREIGN KEY (slot_id)
        REFERENCES timetable_slots(id)
        ON DELETE CASCADE,

    FOREIGN KEY (subject_id)
        REFERENCES subjects(id)
        ON DELETE SET NULL
);


-- =========================================================
-- DOCENTI DELLA LEZIONE
--
-- Una lezione può avere più docenti.
--
-- Esempio:
-- LAB. TPS
--   - ROSSI MARIO
--   - VERDI MICHELE
-- =========================================================

CREATE TABLE timetable_lesson_teachers (
    lesson_id BIGINT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,

    PRIMARY KEY (
        lesson_id,
        teacher_id
    ),

    FOREIGN KEY (lesson_id)
        REFERENCES timetable_lessons(id)
        ON DELETE CASCADE,

    FOREIGN KEY (teacher_id)
        REFERENCES teachers(id)
        ON DELETE CASCADE
);


-- =========================================================
-- AULE DELLA LEZIONE
--
-- Una lezione può avere zero, una o più aule.
-- =========================================================

CREATE TABLE timetable_lesson_rooms (
    lesson_id BIGINT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,

    PRIMARY KEY (
        lesson_id,
        room_id
    ),

    FOREIGN KEY (lesson_id)
        REFERENCES timetable_lessons(id)
        ON DELETE CASCADE,

    FOREIGN KEY (room_id)
        REFERENCES rooms(id)
        ON DELETE CASCADE
);


-- Utente admin predefinito (username: admin, password: admin)
INSERT INTO admin (username, password)
VALUES ('admin', '$2y$10$IS9v8CJNJnRXslV1NWDSquAjJ0GgU1sm6spBmGp6mjTLiNApfGcQi')
ON DUPLICATE KEY UPDATE username = username;