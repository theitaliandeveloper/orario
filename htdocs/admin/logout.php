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
use Jumbojett\OpenIDConnectClient;

require_once __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../lib/variables.php';
require __DIR__ . '/../lib/csrf.php';
require __DIR__ . '/../lib/misc.php';

session_start();

if (!verify_csrf_token($_GET['csrf_token'] ?? '')) {
    http_response_code(400);
    exit("Token CSRF non valido.");
}

$idToken = $_SESSION['id_token'] ?? null;

// Salva il tipo di autenticazione prima di distruggere la sessione
$authType = $_SESSION['auth_type'] ?? null;

session_unset();
session_destroy();

if (strtolower($authType) !== 'oidc' || OIDC_NO_LOGOUT === true) {
    header("Location: ../index.php");
    exit;
}

$oidc = new OpenIDConnectClient(
    OIDC_ISSUER,
    OIDC_CLIENT_ID,
    OIDC_CLIENT_SECRET
);

try {
    $scheme = is_https() ? 'https://' : 'http://';
    $postLogoutRedirectUri = $scheme . APP_DOMAIN . '/index.php';

    if (!empty($idToken)) {
        $oidc->signOut($idToken, $postLogoutRedirectUri);
        exit;
    }

    header("Location: " . $postLogoutRedirectUri);
    exit;

} catch (Throwable $e) {
    http_response_code(500);

    if (DEV_MODE) {
        echo "<pre>";
        echo "Errore logout OIDC:\n";
        echo $e;
        echo "</pre>";
    } else {
        echo "Errore durante il logout.";
    }

    exit;
}