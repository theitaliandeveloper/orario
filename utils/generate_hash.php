<?php
/*
Orario Scuola, Copyright (C) 2025 EmmeV.

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

if ($argc > 1) {
    $primoArgomento = $argv[1];
    $password = password_hash($primoArgomento, PASSWORD_DEFAULT);
    echo "Hash della password '" . $primoArgomento . "': " . $password . "\n";
} else {
    $password = password_hash("admin", PASSWORD_DEFAULT);
    echo "Hash della password 'admin': " . $password . "\n";
}

?>