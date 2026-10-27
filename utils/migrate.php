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

echo "Orario Scuola\n";
echo "Copyright (C) 2025-" . date("Y") . " EmmeV Code. Rilasciato sotto licenza GNU AGPL 3.0.\n\n\n";

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Questo script deve essere eseguito da CLI.\n");
    exit(1);
}

$yes = in_array('--yes', $argv, true);
$help = in_array('--help', $argv, true) || in_array('-h', $argv, true);

if ($help) {
    echo "Migrazione del database all'ultima versione disponibile.\n\n";
    echo "Uso:\n";
    echo "  php utils/migrate.php --yes\n\n";
    echo "Opzioni:\n";
    echo "  --yes    Esegue la migrazione senza richiesta interattiva.\n";
    echo "  --help   Mostra questo messaggio.\n";
    exit(0);
}

if (!$yes) {
    echo "ATTENZIONE: la migrazione rinomina le tabelle legacy e crea le nuove tabelle nello stesso database.\n";
    echo "Non dare per scontato che la migrazione vada a buon fine, esegui prima un backup del DB.\n";
    echo "Sicuro di voler continuare? (conferma digitando YES): ";
    $line = trim((string)fgets(STDIN));
    if ($line !== 'YES') {
        echo "Migrazione annullata.\n";
        exit(0);
    }
}

require_once __DIR__ . '/../htdocs/lib/variables.php';
require_once __DIR__ . '/../htdocs/lib/schema.php';

if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
    fwrite(STDERR, "Errore: variabili di connessione al database non definite.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');
load_application_settings($conn);

if (!app_setting('MAINTENANCE')) {
    fwrite(STDERR, "Errore: La modalità di manutenzione deve essere abilitata per eseguire la migrazione.\n");
    exit(1);
}

try {
    echo "Connessione DB riuscita.\n";
    $installedVersion = get_schema_version($conn);
    if ($installedVersion !== null && $installedVersion >= CURRENT_SCHEMA_VERSION) {
        throw new RuntimeException("La versione dello schema installata ({$installedVersion}) è già aggiornata.");
    }

    if ($installedVersion === null) {
        $installedVersion = 0;
    }

    echo "Versione schema rilevata: " . $installedVersion . "\n";
    echo "Versione schema richiesta: " . CURRENT_SCHEMA_VERSION . "\n";

    $result = null;
    if ($installedVersion < 1) {
        $result = migrate_v1(
            $conn,
            dirname(__DIR__),
            static function (string $message): void {
                echo $message . PHP_EOL;
            }
        );
        $installedVersion = (int)$result['version'];
        echo "Migrazione alla versione 1 completata con successo.";
    }
    if ($installedVersion < 2) {
        $result = migrate_v2(
            $conn,
            dirname(__DIR__),
            static function (string $message): void {
                echo $message . PHP_EOL;
            }
        );
        $installedVersion = (int)$result['version'];
        echo "Migrazione alla versione 2 completata con successo.";
    }

    echo "Versione schema: {$result['version']}\n";
    echo "Migrazione completata con successo.\n";
    exit(0);
} catch (Throwable $error) {
    fwrite(STDERR, "Errore migrazione: {$error->getMessage()}\n");
    exit(1);
}
