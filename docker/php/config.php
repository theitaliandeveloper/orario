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
    $val = getenv('DB_HOST');
    if ($val !== false && $val !== '') {
        define('DB_HOST', $val);
    } else {
        define('DB_HOST', 'db');
    }
}
if (!defined('DB_USER')) {
    $val = getenv('DB_USER');
    if ($val !== false && $val !== '') {
        define('DB_USER', $val);
    } else {
        define('DB_USER', 'orario');
    }
}
if (!defined('DB_PASS')) {
    $val = getenv('DB_PASS');
    if ($val !== false && $val !== '') {
        define('DB_PASS', $val);
    } else {
        define('DB_PASS', 'orario');
    }
}
if (!defined('DB_NAME')) {
    $val = getenv('DB_NAME');
    if ($val !== false && $val !== '') {
        define('DB_NAME', $val);
    } else {
        define('DB_NAME', 'school_timetable');
    }
}
// Impostazioni sito generali
if (!defined('APP_NAME')) {
    $val = getenv('APP_NAME');
    if ($val !== false && $val !== '') {
        define('APP_NAME', $val);
    } else {
        define('APP_NAME', 'Orario Scuola');
    }
}
if (!defined('YEAR')) {
    $val = getenv('YEAR');
    if ($val !== false && $val !== '') {
        define('YEAR', $val);
    } else {
        define('YEAR', '2025/26');
    }
}
if (!defined('PDF_EXPORT')) {
    $val = getenv('PDF_EXPORT');
    if ($val !== false && $val !== '') {
        define('PDF_EXPORT', filter_var($val, FILTER_VALIDATE_BOOLEAN));
    } else {
        define('PDF_EXPORT', true);
    }
}
if (!defined('OPEN_DATA')) {
    $val = getenv('OPEN_DATA');
    if ($val !== false && $val !== '') {
        define('OPEN_DATA', filter_var($val, FILTER_VALIDATE_BOOLEAN));
    } else {
        define('OPEN_DATA', true);
    }
}
if (!defined('MAINTENANCE')) {
    $val = getenv('MAINTENANCE');
    if ($val !== false && $val !== '') {
        define('MAINTENANCE', filter_var($val, FILTER_VALIDATE_BOOLEAN));
    } else {
        define('MAINTENANCE', false);
    }
}
// Impostazioni autenticazione dashboard amministrativa
if (!defined('AUTH_TYPE')) {
    $val = getenv('AUTH_TYPE');
    if ($val !== false && $val !== '') {
        define('AUTH_TYPE', $val);
    } else {
        define('AUTH_TYPE','local');
    }
}
if (!defined('APP_DOMAIN')) {
    $val = getenv('APP_DOMAIN');
    if ($val !== false && $val !== '') {
        define('APP_DOMAIN', $val);
    } else {
        define('APP_DOMAIN','');
    }
}
// Impostazioni autenticazione via OpenID Connect (richiesto solo se AUTH_TYPE sta impostato su oidc)
if (!defined('OIDC_ISSUER')) {
    $val = getenv('OIDC_ISSUER');
    if ($val !== false && $val !== '') {
        define('OIDC_ISSUER', $val);
    } else {
        define('OIDC_ISSUER','');
    }
}
if (!defined('OIDC_CLIENT_ID')) {
    $val = getenv('OIDC_CLIENT_ID');
    if ($val !== false && $val !== '') {
        define('OIDC_CLIENT_ID', $val);
    } else {
        define('OIDC_CLIENT_ID','');
    }
}
if (!defined('OIDC_CLIENT_SECRET')) {
    $val = getenv('OIDC_CLIENT_SECRET');
    if ($val !== false && $val !== '') {
        define('OIDC_CLIENT_SECRET', $val);
    } else {
        define('OIDC_CLIENT_SECRET','');
    }
}
if (!defined('OIDC_ALLOWED_USERS')) {
    $json = getenv('OIDC_ALLOWED_USERS');
    if ($json === false || trim($json) === '') {
        define('OIDC_ALLOWED_USERS',[]);
    }
    $users = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        define('OIDC_ALLOWED_USERS',[]);
    }
    define('OIDC_ALLOWED_USERS',$users);
}
if (!defined('OIDC_NO_LOGOUT')) {
    $val = getenv('OIDC_NO_LOGOUT');
    if ($val !== false && $val !== '') {
        define('OIDC_NO_LOGOUT', filter_var($val, FILTER_VALIDATE_BOOLEAN));
    } else {
        define('OIDC_NO_LOGOUT', false);
    }
}
// Impostazioni avanzate
if (!defined('PHP_MAX_RAM')) {
        $val = getenv('PHP_MAX_RAM');
        if ($val !== false && $val !== '') {
            define('PHP_MAX_RAM', $val);
        } else {
            define('PHP_MAX_RAM','128M');
        }
}
if (!defined('SESSION_LIFETIME')) {
    $val = getenv('SESSION_LIFETIME');
    if ($val !== false && $val !== '' && is_numeric($val)) {
        define('SESSION_LIFETIME', (int)$val);
    } else {
        define('SESSION_LIFETIME', 3600);
    }
}
// Labs (funzioni interne)
if (!defined('API_URL')) {
    $val = getenv('API_URL');
    if ($val !== false && $val !== '') {
        define('API_URL', $val);
    } else {
        define('API_URL', '');
    }
}
?>