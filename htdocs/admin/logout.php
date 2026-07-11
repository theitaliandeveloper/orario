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
require __DIR__ . "/../lib/variables.php";
require __DIR__ . "/../lib/csrf.php";
session_start();
if (!verify_csrf_token($_GET['csrf_token'] ?? '')) { echo "Token CSRF non valido per il logout."; exit; }
session_unset();
session_destroy();
if (strtolower(AUTH_TYPE) === 'oidc' && OIDC_LOGOUT_URI != '' && defined(OIDC_LOGOUT_URI))
    header('Location: ' . OIDC_LOGOUT_URI);
else
    header("Location: /index.php");
?>
