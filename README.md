## AVVISO IMPORTANTE
Questo è il ramo di SVILUPPO. È inteso per testare le ultime funzionalità e aiutarmi nello sviluppo. La stabilità e il funzionamento del codice non sono garantiti. Usare a proprio rischio e pericolo.

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
cd /var/www/orario/admin
composer install
```
- Windows (con XAMPP):
```batch
cd C:\xampp\htdocs\admin
composer install
```
4. **(opzionale) Genera una password hashata**
- Debian
```bash
cd /var/www/orario/utils
php generate_hash.php <password>
```
- Windows:
```batch
cd C:\xampp\htdocs\utils
C:\xampp\php\php.exe generate_hash.php <password>
```
- Modifica quindi questa linea nel file ``schema.sql``, sostituendo l'hash predefinito con quello generato prima:
```sql
VALUES ('admin', '$2y$10$IS9v8CJNJnRXslV1NWDSquAjJ0GgU1sm6spBmGp6mjTLiNApfGcQi'); 
```
5. **Importa il file ``schema.sql`` nel tuo database MySQL**
6. **Modifica il file ``config/config.php`` inserendo i valori richiesti**
- Esempio file ``config/config.php``:
```php
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
    define('AUTH_TYPE','local'); // Può essere local (integrata), keycloak, google
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
}
// Impostazioni autenticazione con Google (richieste solo se AUTH_TYPE sta impostato su google)
if (AUTH_TYPE === 'google') {
    if (!defined('GOOGLE_CLIENT_ID')) {
        define('GOOGLE_CLIENT_ID',''); // Client ID fornito da Google
    }
    if (!defined('GOOGLE_CLIENT_SECRET')) {
        define('GOOGLE_CLIENT_SECRET',''); // Client Secret fornito da Google
    }
    if (!defined('GOOGLE_ONLY_ALLOWED_DOMAINS')) {
        define('GOOGLE_ONLY_ALLOWED_DOMAINS', false); // Attivare (impostare su true) per impostare restrizioni sui domini e-mail consentiti
    }
    if (!defined('GOOGLE_ALLOWED_DOMAINS')) {
        define('GOOGLE_ALLOWED_DOMAINS', ['']); // Domini E-Mail consentiti. Serve abilitare l'opzione GOOGLE_ONLY_ALLOWED_DOMAINS
    }
}
?>
```
7. **Apri ``http://localhost`` e goditi il sito**

## Installazione con Docker
NOTA: L'installazione con Docker è in fase di sviluppo attivo, quindi potrebbe non funzionare.
1. Installa Docker
```bash
curl -fsSL https://get.docker.com | bash
```
2. Compila e crea il container:
```bash
git clone https://git.vichingo455.freeddns.org/emmev-code/orario
cd orario
git checkout dev # richiesto per passare alla versione di sviluppo
docker compose up -d --build
```
3. Il container dovrebbe diventare disponibile su ``http://localhost:8080``

### Per utenti Docker avanzati
Se sei un utente Docker avanzato e vuoi personalizzare puoi modificare la configurazione di docker nei file ``docker/php/config.php``, ``docker-compose.yml`` e ``Dockerfile`` per adattare tutto al tuo ambiente.
Per la maggior parte degli utenti consigliamo di usare la configurazione per Docker predefinita.

## Licenza
**Orario Scuola, Copyright (C) 2025 EmmeV.**

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