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
// Impostazioni Database
if (!defined('DB_HOST')) {
    define('DB_HOST', '<MYSQL_HOST>'); // Host del database (ad esempio localhost)
}
if (!defined('DB_USER')) {
    define('DB_USER', '<MYSQL_USER>'); // Utente del database (ad esempio orario)
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '<MYSQL_PASSWORD>'); // Password dell'utente specificato prima (ad esempio password123)
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'school_timetable'); // Nome del database, non modificare se non sai cosa stai facendo.
}
if (!defined('FACTORY_RESET')) {
    define('FACTORY_RESET', false); // Reset delle preferenze alle impostazioni di fabbrica, usare solo in caso di problemi con le impostazioni attuali
}
?>