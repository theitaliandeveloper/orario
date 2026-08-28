<?php
// Versione corrente dello schema del database. Aggiorna questo valore quando viene rilasciata una nuova versione della piattaforma che richiede modifiche al database.
const CURRENT_SCHEMA_VERSION = 1;
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
    $migrationPath = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'migrate_sql.sql';
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
