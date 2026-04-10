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
if (!defined('DEV_MODE')) {
    define('DEV_MODE', false); // Modalita' di sviluppo: abilita messaggi di debug aggiuntivi. Imposta su false se sei in produzione
}
if (!defined('PDF_EXPORT')) {
    define('PDF_EXPORT', true); // Consenti l'esportazione degli orari in PDF. Imposta su false per impedire.
}
if (!defined('OPEN_DATA')) {
    define('OPEN_DATA', true); // Abilita gli Open Data (API e JSON delle tabelle).
}
// Impostazioni autenticazione dashboard amministrativa
if (!defined('AUTH_TYPE')) {
    define('AUTH_TYPE','local'); // Può essere local (integrata), keycloak
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