<?php
session_start();
session_destroy();
header('Location: https://<KEYCLOAK_URL>/realms/master/protocol/openid-connect/logout?post_logout_redirect_uri=https://<APP_DOMAIN>&client_id=<CLIENT_ID>');
exit;
