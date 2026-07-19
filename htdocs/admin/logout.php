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
require_once __DIR__ . '../vendor/autoload.php';
require __DIR__ . "/../lib/misc.php";
use Jumbojett\OpenIDConnectClient;
session_start();
if (!verify_csrf_token($_GET['csrf_token'] ?? '')) { echo "Token CSRF non valido per il logout."; exit; }
$idToken = $_SESSION['id_token'] ?? null;
session_unset();
session_destroy();
if (strtolower(AUTH_TYPE) === 'oidc') {
    $oidc = new OpenIDConnectClient(
        OIDC_ISSUER,
        OIDC_CLIENT_ID,
        OIDC_CLIENT_SECRET
    );
    /* Sto codice non mi piace
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }*/
    if (is_https()) {
        $postLogoutRedirectUri = 'http://' + APP_DOMAIN + '/index.php';
    } else {
        $postLogoutRedirectUri = 'https://' + APP_DOMAIN + '/index.php';
    }
    if ($idToken && $oidc->getProviderConfigValue('end_session_endpoint')) {
        $oidc->signOut($idToken, $postLogoutRedirectUri);
        exit;
    } else {
        header("Location: " . $postLogoutRedirectUri);
        exit;
    }
}
else {
    header("Location: ../index.php");
}
?>
