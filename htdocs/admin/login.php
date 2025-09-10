<?php
require 'vendor/autoload.php';
use Jumbojett\OpenIDConnectClient;
session_start();
// Configura il client Keycloak
$oidc = new OpenIDConnectClient(
    'https://<KEYCLOAK_URL>/realms/<REALM>/',
    '<CLIENT_ID>',
    '<CLIENT_SECRET>' // opzionale se public client
);
// Redirect post-login
$oidc->setRedirectURL('https://<APP_DOMAIN>/admin/login.php');

$oidc->authenticate();
$userinfo = $oidc->getVerifiedClaims();
$_SESSION['admin'] = $userinfo->preferred_username;
header("Location: index.php");
exit;
