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
?>