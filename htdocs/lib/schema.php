<?php
require_once __DIR__ . '/db.php'; // Per sicurezza, includiamo il file db.php per garantire che la connessione al database sia disponibile prima di eseguire qualsiasi operazione sullo schema.
// Versione corrente dello schema del database. Aggiorna questo valore quando viene rilasciata una nuova versione della piattaforma che richiede modifiche al database.
const CURRENT_SCHEMA_VERSION = 2;
const MANDATORY_SCHEMA_UPDATE = true; // Imposta a true se l'aggiornamento dello schema è obbligatorio per la versione corrente della piattaforma.

// Funzioni di utilità per controlli vari
function schema_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare(
        "SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1"
    );
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $exists;
}

function get_schema_version(mysqli $conn): ?int
{
    if (!schema_table_exists($conn, 'schema_versions')) {
        return null;
    }

    $result = $conn->query('SELECT MAX(version) AS version FROM schema_versions');
    $row = $result->fetch_assoc();
    return $row['version'] === null ? null : (int)$row['version'];
}

function schema_update_required(mysqli $conn): bool
{
    $version = get_schema_version($conn);
    return $version === null || $version < CURRENT_SCHEMA_VERSION;
}

function schema_version_supported(mysqli $conn): bool
{
    $version = get_schema_version($conn);
    return $version !== null && $version <= CURRENT_SCHEMA_VERSION;
}

function legacy_schema_detected(mysqli $conn): bool
{
    $legacyTables = schema_table_exists($conn, 'classes_legacy')
        && schema_table_exists($conn, 'subjects_legacy')
        && schema_table_exists($conn, 'timetable_legacy');

    $oldTables = schema_table_exists($conn, 'classes')
        && schema_table_exists($conn, 'subjects')
        && schema_table_exists($conn, 'timetable');

    return ($legacyTables || $oldTables) && !schema_table_exists($conn, 'timetable_slots');
}

function normalized_schema_detected(mysqli $conn): bool
{
    return schema_table_exists($conn, 'classes')
        && schema_table_exists($conn, 'subjects')
        && schema_table_exists($conn, 'timetable_slots')
        && schema_table_exists($conn, 'timetable_lessons');
}

// Indica al frontend le modifiche apportate in ciascuna versione dello schema, per mostrare un messaggio all'utente dopo la migrazione.
function update_ops(int $version): string
{
    $changelog = schema_changelog();
    return 'Versione ' . $version . ' (' . ($changelog[$version]['title'] ?? 'Aggiornamento generale') . '): ' . ($changelog[$version]['description'] ?? 'Aggiornamento del database');
}

function schema_changelog(): array
{
    return [
        1 => [
            'title' => 'Schema normalizzato iniziale',
            'description' => 'Semplificazione dello schema, con tabelle separate per classi, materie, docenti, laboratori e lezioni.',
        ],
        2 => [
            'title' => 'Aggiunta tabella preferenze',
            'description' => 'Introduzione della tabella preferenze per la gestione delle impostazioni della piattaforma.',
        ],
    ];
}

// Funzione che gestisce il versionamento dello schema
function ensure_schema_version_table(mysqli $conn, int $version, string $description): void
{
    if (!schema_table_exists($conn, 'schema_versions')) {
        $conn->query(
            "CREATE TABLE schema_versions (
                version INT UNSIGNED NOT NULL PRIMARY KEY,
                description VARCHAR(255) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )"
        );
    }

    $stmt = $conn->prepare(
        "INSERT IGNORE INTO schema_versions (version, description)
         VALUES (?, ?)"
    );
    $stmt->bind_param('is', $version, $description);
    $stmt->execute();
    $stmt->close();
}

function load_application_settings(mysqli $conn): bool
{
    $settingNames = [
        'APP_NAME',
        'YEAR',
        'PDF_EXPORT',
        'MAINTENANCE',
        'AUTH_TYPE',
        'APP_DOMAIN',
        'OIDC_ISSUER',
        'OIDC_CLIENT_ID',
        'OIDC_CLIENT_SECRET',
        'OIDC_ALLOWED_USERS',
        'OIDC_NO_LOGOUT',
        'PHP_MAX_RAM',
        'SESSION_LIFETIME',
        'API_URL',
        'ANNOUNCEMENT_TEXT',
    ];
    $fromDatabase = schema_table_exists($conn, 'preferences') && get_schema_version($conn) >= 2;

    if ($fromDatabase) {
        $settings = [];
        $result = $conn->query('SELECT `identifier`, `value` FROM `preferences`');
        while ($row = $result->fetch_assoc()) {
            if (!in_array($row['identifier'], $settingNames, true)) {
                $settings[$row['identifier']] = null;
            }

            if (in_array($row['identifier'], ['PDF_EXPORT', 'MAINTENANCE', 'OIDC_NO_LOGOUT'], true)) {
                $settings[$row['identifier']] = $row['value'] === '1';
            } elseif ($row['identifier'] === 'SESSION_LIFETIME') {
                $settings[$row['identifier']] = (int)$row['value'];
            } elseif ($row['identifier'] === 'OIDC_ALLOWED_USERS') {
                $decoded = json_decode($row['value'], true);
                if (is_array($decoded)) {
                    $settings[$row['identifier']] = $decoded;
                }
            } else {
                $settings[$row['identifier']] = $row['value'];
            }
        }
        $missingSettings = array_diff($settingNames, array_keys($settings));
        foreach ($missingSettings as $missingSetting) {
            $settings[$missingSetting] = defined($missingSetting) ? constant($missingSetting) : null;
        }
    } else {
        $settings = [];
        foreach ($settingNames as $name) {
            $settings[$name] = defined($name) ? constant($name) : null;
        }
    }

    $GLOBALS['application_settings'] = $settings;

    return $fromDatabase;
}

function app_setting(string $name)
{
    if (isset($GLOBALS['application_settings']) && array_key_exists($name, $GLOBALS['application_settings'])) {
        return $GLOBALS['application_settings'][$name];
    }

    return constant($name);
}

function factory_reset_app_settings(mysqli $conn): int
{
    $preferences = [
        'APP_NAME' => ['Orario Scuola', 'Nome del sito'],
        'YEAR' => ['2025/26', 'Anno scolastico corrente'],
        'PDF_EXPORT' => ['1', 'Consenti esportazione degli orari in PDF'],
        'MAINTENANCE' => ['0', 'Abilita la modalità di manutenzione'],
        'ANNOUNCEMENT_TEXT' => ['', 'Testo annuncio'],
        'AUTH_TYPE' => ['local', 'Tipo di autenticazione amministrativa'],
        'APP_DOMAIN' => ['', 'Dominio del sito'],
        'OIDC_ISSUER' => ['', 'Issuer URL per OIDC'],
        'OIDC_CLIENT_ID' => ['', 'Client ID per OIDC'],
        'OIDC_CLIENT_SECRET' => ['', 'Client Secret per OIDC'],
        'OIDC_ALLOWED_USERS' => ['[]', 'Utenti OIDC autorizzati'],
        'OIDC_NO_LOGOUT' => ['0', 'Non eseguire il logout dal provider OIDC'],
        'PHP_MAX_RAM' => ['128M', 'Limite di memoria per PHP'],
        'SESSION_LIFETIME' => ['3600', 'Durata del cookie di login'],
        'API_URL' => ['', 'URL API di importazione'],
    ];

    $stmt = $conn->prepare(
        'INSERT INTO `preferences` (`identifier`, `value`, `description`) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`)'
    );

    foreach ($preferences as $identifier => [$value, $description]) {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } else {
            $value = (string)$value;
        }

        $stmt->bind_param('sss', $identifier, $value, $description);
        $stmt->execute();
    }

    $stmt->close();
    return count($preferences);
}

function migrate_preferences_from_config(mysqli $conn): int
{
    $preferences = [
        'APP_NAME' => [app_setting('APP_NAME'), 'Nome del sito'],
        'YEAR' => [app_setting('YEAR'), 'Anno scolastico corrente'],
        'PDF_EXPORT' => [app_setting('PDF_EXPORT'), 'Consenti esportazione degli orari in PDF'],
        'MAINTENANCE' => [app_setting('MAINTENANCE'), 'Abilita la modalità di manutenzione'],
        'ANNOUNCEMENT_TEXT' => [app_setting('ANNOUNCEMENT_TEXT'), 'Testo annuncio'],
        'AUTH_TYPE' => [app_setting('AUTH_TYPE'), 'Tipo di autenticazione amministrativa'],
        'APP_DOMAIN' => [app_setting('APP_DOMAIN'), 'Dominio del sito'],
        'OIDC_ISSUER' => [app_setting('OIDC_ISSUER'), 'Issuer URL per OIDC'],
        'OIDC_CLIENT_ID' => [app_setting('OIDC_CLIENT_ID'), 'Client ID per OIDC'],
        'OIDC_CLIENT_SECRET' => [app_setting('OIDC_CLIENT_SECRET'), 'Client Secret per OIDC'],
        'OIDC_ALLOWED_USERS' => [app_setting('OIDC_ALLOWED_USERS'), 'Utenti OIDC autorizzati'],
        'OIDC_NO_LOGOUT' => [app_setting('OIDC_NO_LOGOUT'), 'Non eseguire il logout dal provider OIDC'],
        'PHP_MAX_RAM' => [app_setting('PHP_MAX_RAM'), 'Limite di memoria per PHP'],
        'SESSION_LIFETIME' => [app_setting('SESSION_LIFETIME'), 'Durata del cookie di login'],
        'API_URL' => [app_setting('API_URL'), 'URL API di importazione'],
    ];

    $stmt = $conn->prepare(
        'INSERT INTO `preferences` (`identifier`, `value`, `description`) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `description` = VALUES(`description`)'
    );

    foreach ($preferences as $identifier => [$value, $description]) {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } else {
            $value = (string)$value;
        }

        $stmt->bind_param('sss', $identifier, $value, $description);
        $stmt->execute();
    }

    $stmt->close();
    return count($preferences);
}

// Migrazione alla versione 1 dello schema, con tabelle separate per classi, materie, docenti, laboratori e lezioni.
function migrate_v1(mysqli $conn, string $projectRoot, ?callable $logger = null): array
{
    $log = $logger ?? static function (string $message): void {
    };

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $installedVersion = get_schema_version($conn);
    $version = 1;
    if ($installedVersion !== null && $installedVersion >= CURRENT_SCHEMA_VERSION) {
        throw new RuntimeException('La versione dello schema richiesta è già applicata.');
    }

    $legacyTables = ['classes', 'subjects', 'timetable'];
    $backupTables = ['classes_legacy', 'subjects_legacy', 'timetable_legacy'];
    $newTables = [
        'classes',
        'subjects',
        'teachers',
        'rooms',
        'timetable_slots',
        'timetable_lessons',
        'timetable_lesson_teachers',
        'timetable_lesson_rooms',
    ];

    $allExist = static function (mysqli $db, array $tables): bool {
        foreach ($tables as $table) {
            if (!schema_table_exists($db, $table)) {
                return false;
            }
        }
        return true;
    };

    $legacyRenamed = $allExist($conn, $backupTables);
    if (!$legacyRenamed && !$allExist($conn, $legacyTables)) {
        throw new RuntimeException('Tabelle legacy mancanti.');
    }
    $version = 1;

    if ($allExist($conn, ['timetable_slots', 'timetable_lessons'])) {
        if (get_schema_version($conn) === CURRENT_SCHEMA_VERSION) {
            throw new RuntimeException('Il database utilizza già lo schema aggiornato.');
        }
        else {
            ensure_schema_version_table($conn, $version, 'Schema normalizzato iniziale');
            return [
                'version' => $version,
                'backup_tables' => $backupTables,
                'counts' => [],
            ];
        }
    }

    if (!$legacyRenamed) {
        foreach ($backupTables as $table) {
            if (schema_table_exists($conn, $table)) {
                throw new RuntimeException("La tabella {$table} esiste già.");
            }
        }

        $log('Rinomino tabelle legacy...');
        $conn->query('RENAME TABLE classes TO classes_legacy, subjects TO subjects_legacy, timetable TO timetable_legacy');
    }

    foreach ($backupTables as $table) {
        $conn->query("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    $schemaPath = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'schema.sql';
    $migrationPath = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'migrate_sql_v1.sql';
    if (!is_readable($schemaPath) || !is_readable($migrationPath)) {
        throw new RuntimeException('File SQL di migrazione non disponibili.');
    }

    $schemaSql = file_get_contents($schemaPath);
    $schemaSql = preg_replace('/CREATE DATABASE IF NOT EXISTS school_timetable.*?;\s*/is', '', $schemaSql);
    $schemaSql = preg_replace('/USE school_timetable\s*;\s*/i', '', $schemaSql);
    $schemaSql = preg_replace('/CREATE TABLE admin\s*\(.*?\);\s*/is', '', $schemaSql);
    if ($schemaSql === null) {
        throw new RuntimeException('Schema SQL non valido.');
    }

    $log('Creo le nuove tabelle...');
    if (!$conn->multi_query($schemaSql)) {
        throw new RuntimeException('Impossibile creare il nuovo schema.');
    }
    while ($conn->more_results() && $conn->next_result()) {
    }

    // Lo schema corrente può contenere versioni successive a quella in corso.
    $conn->query('DELETE FROM schema_versions WHERE version > 1');

    $migrationSql = file_get_contents($migrationPath);
    if ($migrationSql === false || !$conn->multi_query($migrationSql)) {
        throw new RuntimeException('Impossibile migrare i dati legacy.');
    }
    while ($conn->more_results() && $conn->next_result()) {
    }

    ensure_schema_version_table($conn, $version, 'Schema normalizzato iniziale');
    $counts = [];
    foreach (['classes', 'subjects', 'teachers', 'rooms', 'timetable_slots', 'timetable_lessons'] as $table) {
        $result = $conn->query("SELECT COUNT(*) AS cnt FROM `{$table}`");
        $counts[$table] = (int)$result->fetch_assoc()['cnt'];
    }

    return [
        'version' => $version,
        'backup_tables' => $backupTables,
        'counts' => $counts,
    ];
}

function migrate_v2(mysqli $conn, string $projectRoot, ?callable $logger = null): array
{
    $log = $logger ?? static function (string $message): void {
    };

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $installedVersion = get_schema_version($conn);
    if ($installedVersion !== 1) {
        throw new RuntimeException('La migrazione v2 richiede uno schema v1 installato.');
    }

    $preferenceCount = 0;
    $conn->begin_transaction();
    try {
        $conn->query(
            "CREATE TABLE IF NOT EXISTS preferences (
                identifier VARCHAR(50) NOT NULL PRIMARY KEY,
                value TEXT NOT NULL,
                description VARCHAR(255) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        );

        $preferenceCount = migrate_preferences_from_config($conn);
        ensure_schema_version_table($conn, 2, 'Preferenze applicative nel database');
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw new RuntimeException('Impossibile applicare lo schema v2: ' . $error->getMessage(), 0, $error);
    }

    $log('Preferenze applicative migrate nel database.');

    return [
        'version' => 2,
        'backup_tables' => [],
        'counts' => ['preferences' => $preferenceCount],
    ];
}
