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
6. **Modifica il file ``db.php`` cambiando l'host, il nome utente e la password (necessari per la connessione al database MySQL)**
- Esempio:
```php
$host = "localhost";
$user = "utente";
$pass = "password123";
```
7. **(Opzionale) Modifica ``admin/login.php.keycloak`` e ``admin/logout.php.keycloak`` con i dati di un'istanza keycloak, in caso tu voglia usare Keycloak e non l'autenticazione integrata. Cancella poi i file ``login.php`` e ``logout.php`` e rinomina ``admin/login.php.keycloak`` in ``login.php`` e ``admin/logout.php.keycloak`` in ``logout.php``**
- Esempio (``login.php.keycloak``):
```php
$oidc = new OpenIDConnectClient(
    'https://keycloak.local/realms/master/',
    'orario', // Client ID Keycloak
    'abcdefghijklmnop' // Client secret Keycloak
);
$oidc->setRedirectURL('https://orario.local/admin/login.php'); // orario.local è il dominio base di questa piattaforma
```
- Esempio (``logout.php.keycloak``):
```php
header('Location: https://keycloak.local/realms/master/protocol/openid-connect/logout?post_logout_redirect_uri=https://orario.local&client_id=orario');
```
8. **Apri ``http://localhost`` e goditi il sito**

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