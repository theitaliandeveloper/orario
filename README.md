# Orario Scuola
Una piattaforma web per visualizzare gli orari scolastici delle classi, degli insegnanti e dei vari laboratori (se presenti)

## Requisiti
- Server web (consigliati nginx o apache2 per Linux, XAMPP per Windows)
- PHP 8.0 o successivo (configurato nel tuo server web)
- Composer
- MySQL ultima versione

## Installazione
1. **Clona la repository e copia la cartella htdocs in una cartella accessibile dal server web**
- Esempio (Debian):
```bash
git clone https://git.vichingo455.freeddns.org/emmev-code/orario
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

6. **Modifica il file ``config/config.php`` inserendo i valori richiesti**
- Esempio file ``config/config.php``:
```php
<?php
// Impostazioni Database
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost'); // Host del database (ad esempio localhost)
}
if (!defined('DB_USER')) {
    define('DB_USER', 'orario'); // Utente del database (ad esempio orario)
}
if (!defined('DB_PASS')) {
    define('DB_PASS', 'orario'); // Password dell'utente specificato prima (ad esempio password123)
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
wget https://git.vichingo455.qzz.io/emmev-code/orario/raw/branch/stable/schema.sql
wget https://git.vichingo455.qzz.io/emmev-code/orario/raw/branch/stable/docker-compose.yml
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
      # --- Configuratione Database ---
      DB_HOST: db # Host database
      DB_USER: orario # Utente database
      DB_PASS: orario # Password dell'utente del database
      DB_NAME: school_timetable # Nome del database

      # --- Impostazioni sito ---
      APP_NAME: "Orario Scuola" # Nome del sito
      YEAR: "2025/26" # Anno scolastico corrente
      DEV_MODE: "false" # Abilita modalita' di sviluppo per output dettagliato.
      PDF_EXPORT: true # Abilita l'esportazione degli orari in PDF
      OPEN_DATA: true # Abilita gli Open Data (API e tabelle in JSON)

      # --- Impostazioni Autenticazione ---
      AUTH_TYPE: "local" # Tipo di autenticazione: può essere local o keycloak
      APP_DOMAIN: "" # Dominio dell'app, ad esempio orario.tuosito.com

      # --- Impostazioni di Keycloak (solo se il tipo di autenticazione è Keycloak) ---
      KEYCLOAK_DOMAIN: "" # Dominio di Keycloak, ad esempio sso.tuosito.com
      KEYCLOAK_REALM: "" # Realm di Keycloak, ad esempio master
      KEYCLOAK_CLIENT_ID: "" # Client ID per Keycloak, ad esempio orario
      KEYCLOAK_CLIENT_SECRET: "" # Client Secret per Keycloak, ad esempio abcde12345
      KEYCLOAK_ALLOWED_USERS: '[]' # Nomi utente che possono accedere al pannello di controllo, lascia vuoto per consentire tutti gli utenti. Esempio: '["admin","prof","segreteria"]'
```

## Segnalare un problema
Per segnalare un problema puoi usare [Bugzilla](https://bugs.vichingo455.freeddns.org/describecomponents.cgi?product=Orario%20Scuola). Clicca [qui](https://bugs.vichingo455.freeddns.org/describecomponents.cgi?product=Orario%20Scuola) per andare a Bugzilla.

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