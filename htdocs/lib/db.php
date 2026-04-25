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
$baseConfigFile  = __DIR__ . "/../config/config.php";
$localConfigFile = __DIR__ . "/../config/config.local.php";

/*
 * 1. carico PRIMA il base
 * 2. poi il local che può sovrascrivere SOLO se la costante NON esiste ancora
 */
if (file_exists($localConfigFile)) {
    require $localConfigFile;
}

require $baseConfigFile;
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$dbname = DB_NAME;

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    if (DEV_MODE)
        die("[DEBUG] Connessione al database fallita: " . $conn->connect_error);
    else
        die("Connessione al database fallita!");
}
?>
