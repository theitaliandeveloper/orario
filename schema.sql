-- Orario Scuola
-- Copyright (C) 2025 EmmeV. All rights reserved.
-- Esegui questo script nel tuo database MySQL prima di usare il progetto.

-- Database: school_timetable
CREATE DATABASE IF NOT EXISTS school_timetable
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_timetable;

-- Tabella admin (per login)
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Utente admin predefinito (username: admin, password: admin)
-- Necessario se si usa l'autenticazione normale invece di Keycloak
INSERT INTO admin (username, password) 
VALUES ('admin', '$2y$10$IS9v8CJNJnRXslV1NWDSquAjJ0GgU1sm6spBmGp6mjTLiNApfGcQi');
-- Sostituisci l'hash predefinito con l'hash della tua password
-- generato con utils/generate_hash.php

-- Tabella classi
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(10) NOT NULL,
    section VARCHAR(50) DEFAULT NULL
);

-- Tabella materie
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    teacher VARCHAR(100),
    room VARCHAR(100)
);

-- Tabella orario
CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    day ENUM('Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato') NOT NULL,
    hour INT NOT NULL, -- 1=Prima ora, 2=Seconda ora, ecc.
    subject_id INT,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
);
