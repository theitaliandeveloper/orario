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
6. **Modifica il file ``db.php`` cambiando l'host, il nome utente e la password**
- Esempio:
```php
$host = "localhost";
$user = "utente";
$pass = "password123";
```
7. **Modifica ``admin/login.php`` e ``admin/logout.php`` con i dati di un'istanza keycloak. In caso tu voglia usare l'autenticazione via nome utente e password (e non keycloak), cancella quei due file e rinomina ``admin/login.php.backup`` in ``login.php`` e ``admin/logout.php.backup`` in ``logout.php``**
- Esempio (``login.php``):
```php
$oidc = new OpenIDConnectClient(
    'https://keycloak.local/realms/<REALM>/',
    'orario',
    'abcdefghijklmnop'
);
$oidc->setRedirectURL('https://orario.local/admin/login.php');
```
- Esempio (``logout.php``):
```php
header('Location: https://keycloak.local/realms/master/protocol/openid-connect/logout?post_logout_redirect_uri=https://orario.local&client_id=orario');
```
8. **Apri ``http://localhost`` e goditi il sito**

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