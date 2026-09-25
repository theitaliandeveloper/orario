-- Orario Scuola
-- Copyright (C) 2025-2026 EmmeV. All rights reserved.
-- Esegui questo script nel tuo database MySQL prima di usare il progetto.

CREATE DATABASE IF NOT EXISTS school_timetable
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE school_timetable;


-- =========================================================
-- VERSIONE SCHEMA
-- =========================================================

CREATE TABLE IF NOT EXISTS schema_versions (
    version INT UNSIGNED NOT NULL PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO schema_versions (version, description)
VALUES (1, 'Schema normalizzato iniziale');

INSERT IGNORE INTO schema_versions (version, description)
VALUES (2, 'Preferenze applicative nel database');


-- =========================================================
-- ADMIN
-- =========================================================

CREATE TABLE IF NOT EXISTS admin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);


-- =========================================================
-- CLASSI
-- =========================================================

CREATE TABLE IF NOT EXISTS classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(50) NOT NULL,
    section VARCHAR(50) DEFAULT NULL,

    UNIQUE KEY uq_classes_name (name)
);


-- =========================================================
-- MATERIE
-- =========================================================

CREATE TABLE IF NOT EXISTS subjects (
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

CREATE TABLE IF NOT EXISTS teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    external_id VARCHAR(100) DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    username VARCHAR(150) DEFAULT NULL,

    UNIQUE KEY uq_teachers_external_id (external_id)
);


-- =========================================================
-- AULE
-- =========================================================

CREATE TABLE IF NOT EXISTS rooms (
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

CREATE TABLE IF NOT EXISTS timetable_slots (
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

CREATE TABLE IF NOT EXISTS timetable_lessons (
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

CREATE TABLE IF NOT EXISTS timetable_lesson_teachers (
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

CREATE TABLE IF NOT EXISTS timetable_lesson_rooms (
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


INSERT INTO admin (username, password)
SELECT 'admin', '$2y$10$IS9v8CJNJnRXslV1NWDSquAjJ0GgU1sm6spBmGp6mjTLiNApfGcQi'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM admin
    WHERE username = 'admin'
);

-- =========================================================
-- PREFERENZE APPLICATIVE
-- =========================================================

CREATE TABLE IF NOT EXISTS preferences (
    identifier VARCHAR(50) NOT NULL PRIMARY KEY,
    value TEXT NOT NULL,
    description VARCHAR(255) NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO preferences (identifier, value, description) VALUES
    ('APP_NAME', 'Orario Scuola', 'Nome del sito'),
    ('YEAR', '2025/26', 'Anno scolastico corrente'),
    ('PDF_EXPORT', '1', 'Consenti esportazione degli orari in PDF'),
    ('MAINTENANCE', '0', 'Abilita la modalità di manutenzione'),
    ('AUTH_TYPE', 'local', 'Tipo di autenticazione amministrativa'),
    ('APP_DOMAIN', '', 'Dominio del sito'),
    ('OIDC_ISSUER', '', 'Issuer URL per OIDC'),
    ('OIDC_CLIENT_ID', '', 'Client ID per OIDC'),
    ('OIDC_CLIENT_SECRET', '', 'Client Secret per OIDC'),
    ('OIDC_ALLOWED_USERS', '[]', 'Utenti OIDC autorizzati'),
    ('OIDC_NO_LOGOUT', '0', 'Non eseguire il logout dal provider OIDC'),
    ('PHP_MAX_RAM', '128M', 'Limite di memoria per PHP'),
    ('SESSION_LIFETIME', '3600', 'Durata del cookie di login'),
    ('API_URL', '', 'URL API di importazione');