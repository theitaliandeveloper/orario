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
require_once __DIR__ . "/auth_check.php";
require_once __DIR__ . "/../../lib/schema.php";

if (($_SESSION['admin'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Privilegi insufficienti.']);
    exit;
}

$preferenceTypes = [
    'APP_NAME' => 'text',
    'YEAR' => 'text',
    'PDF_EXPORT' => 'boolean',
    'MAINTENANCE' => 'boolean',
    'AUTH_TYPE' => 'text',
    'APP_DOMAIN' => 'text',
    'OIDC_ISSUER' => 'text',
    'OIDC_CLIENT_ID' => 'text',
    'OIDC_CLIENT_SECRET' => 'secret',
    'OIDC_ALLOWED_USERS' => 'users',
    'OIDC_NO_LOGOUT' => 'boolean',
    'PHP_MAX_RAM' => 'text',
    'SESSION_LIFETIME' => 'integer',
    'API_URL' => 'text',
    'ANNOUNCEMENT_TEXT' => 'textarea',
];

$preferenceDescriptions = [
    'APP_NAME' => 'Nome piattaforma',
    'YEAR' => 'Anno scolastico',
    'PDF_EXPORT' => 'Esportazione PDF',
    'MAINTENANCE' => 'Modalita manutenzione',
    'AUTH_TYPE' => 'Tipo autenticazione',
    'APP_DOMAIN' => 'Dominio applicazione',
    'OIDC_ISSUER' => 'Issuer OIDC',
    'OIDC_CLIENT_ID' => 'Client ID OIDC',
    'OIDC_CLIENT_SECRET' => 'Client secret OIDC',
    'OIDC_ALLOWED_USERS' => 'Utenti OIDC autorizzati',
    'OIDC_NO_LOGOUT' => 'Logout nel provider OIDC disattivato',
    'PHP_MAX_RAM' => 'Memoria massima PHP',
    'SESSION_LIFETIME' => 'Durata sessione',
    'API_URL' => 'URL API importazione',
    'ANNOUNCEMENT_TEXT' => 'Testo annuncio',
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $settings = [];
    $secretConfigured = false;
    $result = $conn->query('SELECT `identifier`, `value` FROM `preferences`');
    while ($row = $result->fetch_assoc()) {
        $identifier = $row['identifier'];
        if (!array_key_exists($identifier, $preferenceTypes)) {
            continue;
        }
        if ($preferenceTypes[$identifier] === 'secret') {
            $secretConfigured = $row['value'] !== '';
            $settings[$identifier] = '';
        } elseif ($preferenceTypes[$identifier] === 'boolean') {
            $settings[$identifier] = $row['value'] === '1';
        } elseif ($preferenceTypes[$identifier] === 'integer') {
            $settings[$identifier] = (int)$row['value'];
        } elseif ($preferenceTypes[$identifier] === 'users') {
            $decoded = json_decode($row['value'], true);
            $settings[$identifier] = is_array($decoded) ? $decoded : [];
        } else {
            $settings[$identifier] = $row['value'];
        }
    }

    echo json_encode([
        'preferences' => $settings,
        'secretConfigured' => $secretConfigured,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload non valido.']);
    exit;
}

$values = [];
foreach ($preferenceTypes as $identifier => $type) {
    if ($type === 'boolean') {
        $values[$identifier] = !empty($input[$identifier]) ? '1' : '0';
    } elseif ($type === 'users') {
        $users = $input[$identifier] ?? [];
        if (!is_array($users)) {
            http_response_code(400);
            echo json_encode(['error' => 'Gli utenti OIDC devono essere un array.']);
            exit;
        }
        $users = array_values(array_filter(array_map('trim', $users), static fn(string $user): bool => $user !== ''));
        $values[$identifier] = json_encode($users, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    } elseif ($type === 'secret' && trim((string)($input[$identifier] ?? '')) === '') {
        $secretResult = $conn->query("SELECT `value` FROM `preferences` WHERE `identifier` = 'OIDC_CLIENT_SECRET'");
        $secretRow = $secretResult->fetch_assoc();
        $values[$identifier] = (string)($secretRow['value'] ?? '');
    } else {
        $values[$identifier] = trim((string)($input[$identifier] ?? ''));
    }
}

if ($values['APP_NAME'] === '' || $values['YEAR'] === '' || $values['PHP_MAX_RAM'] === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Nome piattaforma, anno scolastico e memoria PHP sono obbligatori.']);
    exit;
}
if (!in_array($values['AUTH_TYPE'], ['local', 'oidc'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo di autenticazione non valido.']);
    exit;
}
if (!is_numeric($values['SESSION_LIFETIME']) || (int)$values['SESSION_LIFETIME'] < 60) {
    http_response_code(400);
    echo json_encode(['error' => 'La durata della sessione deve essere almeno 60 secondi.']);
    exit;
}

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare(
        'INSERT INTO `preferences` (`identifier`, `value`, `description`) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`)'
    );
    foreach ($values as $identifier => $value) {
        $description = $preferenceDescriptions[$identifier];
        $stmt->bind_param('sss', $identifier, $value, $description);
        $stmt->execute();
    }
    $stmt->close();
    $conn->commit();
    load_application_settings($conn);
    echo json_encode(['success' => true]);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Errore salvataggio preferenze: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => DEV_MODE
            ? 'Errore nel salvataggio delle preferenze: ' . $error->getMessage()
            : 'Errore nel salvataggio delle preferenze.'
    ]);
}
