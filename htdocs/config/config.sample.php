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
// Impostazioni Database
if (!defined('DB_HOST')) {
    define('DB_HOST', '<MYSQL_HOST>'); // Host del database (ad esempio localhost)
}
if (!defined('DB_USER')) {
    define('DB_USER', '<MYSQL_USER>'); // Utente del database (ad esempio orario)
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '<MYSQL_PASSWORD>'); // Password dell'utente specificato prima (ad esempio password123)
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'school_timetable'); // Nome del database, non modificare se non sai cosa stai facendo.
}
// Impostazioni sito generali
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Orario Scuola'); // Nome del sito
}
if (!defined('YEAR')) {
    define('YEAR', '2025/26'); // Anno Scolastico Corrente
}
if (!defined('API_URL')) {
    define('API_URL', ''); // URL API di importazione, lascia vuoto per disabilitare. Esempio: http://localhost:3006/classe
}
if (!defined('PDF_EXPORT')) {
    define('PDF_EXPORT', true); // Consenti l'esportazione degli orari in PDF. Imposta su false per impedire.
}
if (!defined('OPEN_DATA')) {
    define('OPEN_DATA', true); // Abilita gli Open Data (API e JSON delle tabelle).
}
if (!defined('MAINTENANCE')) {
    define('MAINTENANCE', false); // Abilita la modalità di manutenzione della piattaforma.
}
// Impostazioni autenticazione dashboard amministrativa
if (!defined('AUTH_TYPE')) {
    define('AUTH_TYPE','local'); // Può essere local (integrata), oidc (OpenID Connect)
}
if (!defined('APP_DOMAIN')) {
    define('APP_DOMAIN',''); // Dominio del sito (ad esempio orario.yourdomain.com), richiesto per autenticazioni non local
}
// Impostazioni autenticazione via OAuth2 (richiesto solo se AUTH_TYPE sta impostato su oidc)
if (!defined('OIDC_ISSUER')) {
    define('OIDC_ISSUER',''); // Issuer URL per OIDC (ad esempio https://tuokeycloak.com/realms/master)
}
if (!defined('OIDC_CLIENT_ID')) {
    define('OIDC_CLIENT_ID',''); // Client ID per OIDC (ad esempio orario)
}
if (!defined('OIDC_CLIENT_SECRET')) {
    define('OIDC_CLIENT_SECRET',''); // Client Secret per OIDC (ad esempio abcdefghijklm)
}
if (!defined('OIDC_ALLOWED_USERS')) {
    define('OIDC_ALLOWED_USERS',[]); // Contiene i nomi utente degli utenti OIDC autorizzati ad accedere all'amministrazione
}
// Impostazioni avanzate. NON MODIFICARE SE NON SAI QUELLO CHE STAI FACENDO!!
if (!defined('PHP_MAX_RAM')) {
    define('PHP_MAX_RAM','128M'); // Limite di memoria per PHP, si consiglia di aumentarlo in caso di bisogno e di non andare sotto i 128 MB. Imposta a -1 per disattivare. - https://www.php.net/manual/en/ini.core.php#ini.memory-limit
}
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME',3600); // Durata del cookie di login
}
?>