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

// Queste variabili sono per sviluppatori
if (!defined('VERSION')) {
    define('VERSION', 'dev'); // deve essere "dev" per la versione di sviluppo oppure X.Y.Z per la versione stabile.
}

if (!defined('DEV_MODE')) {
    define('DEV_MODE', VERSION == 'dev');
}

if (!DEV_MODE) {
    ini_set('error_reporting','E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR'); // Show only errors in production.
} else {
    ini_set('error_reporting','E_ALL'); // In development show everything
}

// Configurazione dell'utente
require __DIR__ . "/../config/config.php";

// Controllo sulle variabili impostate dall'utente (quelle critiche)
if (!defined('PHP_MAX_RAM')) {
    define('PHP_MAX_RAM','128M');
} else if (PHP_MAX_RAM == "") {
    die("Il limite di memoria di PHP non puo' essere una stringa vuota!");
}

if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME',3600);
}

if (!is_numeric(SESSION_LIFETIME) || SESSION_LIFETIME < 60) {
    die("Il limite di sessione deve essere un valore numerico non minore di 60.");
}

// Imposta le variabili user-defined sperando che effettivamente non siano eresie.
ini_set('memory_limit',PHP_MAX_RAM); // https://www.php.net/manual/en/ini.core.php#ini.memory-limit
ini_set('session.gc_maxlifetime', SESSION_LIFETIME); // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => '',
    'httponly' => true,
    'samesite' => 'Lax'
]);
?>