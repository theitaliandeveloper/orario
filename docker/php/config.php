<?php
// Impostazioni Database
if (!defined('DB_HOST')) {
    define('DB_HOST', 'db');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'orario');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', 'orario');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'school_timetable');
}
// Impostazioni sito generali
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Orario Scuola');
}
if (!defined('YEAR')) {
    define('YEAR', '2025/26');
}
if (!defined('API_URL')) {
    define('API_URL', 'http://localhost:3006/classe');
}
if (!defined('DEV_MODE')) {
    define('DEV_MODE', false); // Modalita' di sviluppo
}
// Impostazioni autenticazione dashboard amministrativa
if (!defined('AUTH_TYPE')) {
    define('AUTH_TYPE','local'); // Può essere local (integrata), keycloak, google
}
if (!defined('APP_DOMAIN')) {
    define('APP_DOMAIN',''); // Dominio del sito (ad esempio orario.yourdomain.com), richiesto per autenticazioni non local
}
// Impostazioni autenticazione via Keycloak (richiesto solo se AUTH_TYPE sta impostato su keycloak)
if (AUTH_TYPE === 'keycloak') {
    if (!defined('KEYCLOAK_DOMAIN')) {
        define('KEYCLOAK_DOMAIN',''); // Dominio di Keycloak (ad esempio auth.yourdomain.com)
    }
    if (!defined('KEYCLOAK_REALM')) {
        define('KEYCLOAK_REALM',''); // Realm di Keycloak (ad esempio master)
    }
    if (!defined('KEYCLOAK_CLIENT_ID')) {
        define('KEYCLOAK_CLIENT_ID',''); // Client ID per Keycloak (ad esempio orario)
    }
    if (!defined('KEYCLOAK_CLIENT_SECRET')) {
        define('KEYCLOAK_CLIENT_SECRET',''); // Client Secret per Keycloak (ad esempio abcdefghijklm)
    }
    if (!defined('KEYCLOAK_ALLOWED_USERS')) {
        define('KEYCLOAK_ALLOWED_USERS',[]); // Contiene i nomi utente degli utenti autorizzati ad accedere all'amministrazione
    }
}
?>