<?php
include("../config/config.php");
session_start();
session_destroy();
if (AUTH_TYPE === 'local')
    header("Location: /index.php");
else if (AUTH_TYPE === 'keycloak')
    header('Location: https://' . KEYCLOAK_DOMAIN . '/realms/' . KEYCLOAK_REALM . '/protocol/openid-connect/logout?post_logout_redirect_uri=https://' . APP_DOMAIN . '&client_id=' . KEYCLOAK_CLIENT_ID);
?>
