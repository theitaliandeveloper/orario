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
include("../config/config.php");
session_start();
session_destroy();
if (AUTH_TYPE === 'local')
    header("Location: /index.php");
else if (AUTH_TYPE === 'keycloak')
    header('Location: https://' . KEYCLOAK_DOMAIN . '/realms/' . KEYCLOAK_REALM . '/protocol/openid-connect/logout?post_logout_redirect_uri=https://' . APP_DOMAIN + '&client_id=' . KEYCLOAK_CLIENT_ID);
?>
