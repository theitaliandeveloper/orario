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
// Impostazioni autenticazione dashboard amministrativa
if (!defined('AUTH_TYPE')) {
    define('AUTH_TYPE','local'); // Può essere keycloak o local (integrata)
}
if (!defined('KEYCLOAK_DOMAIN')) {
    define('KEYCLOAK_DOMAIN','');
}
if (!defined('KEYCLOAK_REALM')) {
    define('KEYCLOAK_REALM','');
}
if (!defined('KEYCLOAK_CLIENT_ID')) {
    define('KEYCLOAK_CLIENT_ID','');
}
if (!defined('KEYCLOAK_CLIENT_SECRET')) {
    define('KEYCLOAK_CLIENT_SECRET','');
}
if (!defined('APP_DOMAIN')) {
    define('APP_DOMAIN','');
}
?>