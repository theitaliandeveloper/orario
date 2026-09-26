## AVVISO IMPORTANTE
Questo è il ramo di SVILUPPO. È inteso per testare le ultime funzionalità e aiutarmi nello sviluppo. La stabilità e il funzionamento del codice non sono garantiti. Usare a proprio rischio e pericolo.

[Roadmap sviluppo](https://todo.vichingo455.com/share/hFNyiwMDYNY57JDhKcHNAdiYKz0DKdbI5FizTfht/auth?view=8)

# Orario Scuola
Una piattaforma web per visualizzare gli orari scolastici delle classi, degli insegnanti e dei vari laboratori (se presenti)

## Requisiti
- Server web (consigliati nginx o apache2 per Linux, XAMPP per Windows)
- PHP 8.0 o successivo (configurato nel tuo server web)
- Composer
- MariaDB 10/11/12

## Installazione
1. **Clona la repository e copia la cartella htdocs in una cartella accessibile dal server web**
- Esempio (Debian):
```bash
git clone https://git.vichingo455.com/emmev-code/orario
git checkout dev # Ramo di sviluppo
cp -r orario/htdocs/* /var/www/html/
```

2. **Installa ``composer`` e le estensioni di php**
- Debian:
```bash
sudo apt install -y composer php-cli curl php-mysql php-curl php-mbstring php-xml php-zip
```
- Windows: Scarica [il programma di installazione di Composer](https://getcomposer.org/Composer-Setup.exe) ed installalo. Assicurati di modificare il file php.ini per abilitare le estensioni mysql, curl, mbstring, xml, zip, cli.


3. **Installa le dipendenze del pannello d'amministrazione**
- Debian:
```bash
cd /var/www/html
composer install
```
- Windows (con XAMPP):
```batch
cd C:\xampp\htdocs
composer install
```
4. **(opzionale) Genera una password hashata**
- Debian
```bash
cd orario/utils
php generate_hash.php <password>
```
- Windows:
```batch
cd orario\utils
C:\xampp\php\php.exe generate_hash.php <password>
```
- Modifica quindi questa linea nel file ``schema.sql``, sostituendo l'hash predefinito con quello generato prima:
```sql
VALUES ('admin', '$2y$10$IS9v8CJNJnRXslV1NWDSquAjJ0GgU1sm6spBmGp6mjTLiNApfGcQi');
```
5. **Importa il file ``schema.sql`` nel tuo database MySQL**
- Esempio Debian:
```bash
mysql -u root -p < orario/schema.sql
```

6. **Crea il file ``config/config.php`` inserendo i valori richiesti**
- Esempio file ``config/config.php`` (trovi il file base sotto ``config/config.sample.php``):
```php
<?php
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
?>
```
7. **Apri ``http://localhost`` e goditi il sito**

## Installazione con Docker
1. Installa Curl, Git e Docker
```bash
apt install curl git
curl -fsSL https://get.docker.com | bash
```
2. Scarica i file richiesti:
```bash
wget https://git.vichingo455.com/emmev-code/orario/raw/branch/dev/schema.sql
wget https://git.vichingo455.com/emmev-code/orario/raw/branch/dev/docker-compose.yml
```

3. Avvia il container:
```bash
docker compose up -d
```
4. Il container dovrebbe diventare disponibile su ``http://localhost:8080``

### Personalizzare l'istanza
Per cambiare le impostazioni dell'istanza basta aprire ``docker-compose.yml`` con un editor di testo e modificare le variabili d'ambiente:
```yaml
    environment:
      DB_HOST: db # Host database
      DB_USER: orario # Utente database
      DB_PASS: orario # Password dell'utente del database
      DB_NAME: school_timetable # Nome del database
```

## Migrazione dello schema
Per usare nuove versioni della piattaforma, potrebbe necessario migrare il database dallo schema vecchio a quello nuovo.
Prima di iniziare la migrazione, assicurarsi di avere un backup del database. Le tabelle vecchie verranno mantenute aggiungendogli il suffisso ``_legacy``.

### Requisiti (solo installazione manuale)
Supponendo che la cartella di installazione sia ``/var/www/html``:
- Assicurarsi che la cartella ``/var/www/utils`` esista e contenga i file richiesti(richiesta solo per la migrazione). Per correggere:
```bash
mkdir /var/www/utils
cp -r orario/utils/* /var/www/utils/
```
- Assicurarsi che il file ``/var/www/schema.sql esista`` (richiesto solo durante la migrazione). Per correggere:
```bash
cp orario/schema.sql /var/www/schema.sql
```
- Una volta eseguita la migrazione questi file possono essere eliminati

### Migrazione dal browser (Docker e installazione manuale)
Quando un amministratore accede al dashboard con lo schema legacy ancora attivo, viene aperta automaticamente la pagina ``admin/migrate.php``.
Quella pagina mostrerà una descrizione breve dei cambiamenti effettuati. Basterà premere il pulsante "Avvia aggiornamento" e il database verrà aggiornato.

### Migrazione da CLI (solo installazione manuale)
Per migrare da riga di comando, è possibile usare lo strumento incluso con la piattaforma:
```bash
php utils/migrate.php
```
Lo strumento chiederà il permesso a procedere e vi informerà del risultato della migrazione.

### Migrazione manuale (Docker e installazione manuale)
Se vuoi fare la migrazione manuale, puoi usare ``migrate_sql_v1.sql``.

Passi consigliati:
1. Esegui backup completo del database.
2. Rinomina le tabelle legacy:
```sql
USE school_timetable;
RENAME TABLE classes TO classes_legacy, subjects TO subjects_legacy, timetable TO timetable_legacy;
```
3. Importa ``schema.sql``.
4. Esegui ``migrate_sql_v1.sql`` per popolare il nuovo modello.

## Licenza
**Orario Scuola, Copyright (C) 2025-2026 EmmeV.**

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.