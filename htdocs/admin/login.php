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
use Jumbojett\OpenIDConnectClient;
require_once '../vendor/autoload.php';
require_once __DIR__ . "/../lib/db.php";
require_once __DIR__ . "/../lib/csrf.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$now = time();
if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) { // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
}
$_SESSION['discard_after'] = $now + SESSION_LIFETIME; // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
if (isset($_SESSION['admin'])) { header("Location: index.php"); exit; }
if ($_SERVER["REQUEST_METHOD"] == "POST" && strtolower(AUTH_TYPE) == 'local') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF non valido.";
    } else {
        try {
          $username = $_POST['username'] ?? '';
          $password = $_POST['password'] ?? '';
          $stmt = $conn->prepare("SELECT username, password FROM admin WHERE username = ?");
          $stmt->bind_param("s", $username);
          $stmt->execute();
          $res = $stmt->get_result();
          if ($row = $res->fetch_assoc()) {
              if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin'] = $row['username'];
                $_SESSION['auth_type'] = 'local';
                header("Location: index.php");
                exit;
              }
          }
          sleep(2); // Brute force mitigation
          $error = "Credenziali non valide";
        } catch (Exception $e) {
          sleep(2); // Brute force mitigation
          if (DEV_MODE) {
            $error = "Errore del server durante l'autenticazione. Ulteriori dettagli: " . $e;
          } else {
            $error = "Errore durante l'autenticazione.";
          }
        }
    }
}
$name = APP_NAME;
if (DEV_MODE){
  $dev = " - SVILUPPO";
}
else {
  $dev = "";
}

if (strtolower(AUTH_TYPE) == 'local') {
$csrf_field = csrf_field();
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>{$name} - Accedi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
</head>
<body>

  <div class="navbar">
    <div class="logo">{$name} - Admin Dashboard{$dev}</div>
    <div class="links">
      <a href="../index.php">Torna al sito</a>
    </div>
  </div>

  <!-- Container login -->
  <div class="login-container">
    <h1>Accedi</h1>
    <form method="post">
      {$csrf_field}
      <input type="text" name="username" placeholder="Username" required><br>
      <input type="password" name="password" placeholder="Password" required><br>
      <button type="submit">Login</button>
    </form>
HTML;
if(isset($error)) echo "<br><div class='error'>$error</div>";
echo <<<HTML
</div>
<p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.qzz.io/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.qzz.io/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
</p>
</body>
</html>
HTML;
}
else if (strtolower(AUTH_TYPE) === 'oidc') {
  try {
    // Configura il client OIDC
    $oidc = new OpenIDConnectClient(
        OIDC_ISSUER,
        OIDC_CLIENT_ID,
        OIDC_CLIENT_SECRET
    );

    // Richiedi anche le informazioni del profilo
    $oidc->addScope(['openid', 'profile', 'email']);

    // Redirect post-login
    $oidc->setRedirectURL('https://' . APP_DOMAIN . '/admin/login.php');

    if (!$oidc->authenticate()) {
        throw new Exception("OIDC Authentication failed");
    }

    $_SESSION['id_token'] = $oidc->getIdToken();

    // Recupera le informazioni dell'utente dall'endpoint UserInfo
    $userinfo = $oidc->requestUserInfo();

    // Determina un identificativo dell'utente
    $username =
        $userinfo->preferred_username
        ?? $userinfo->username
        ?? $userinfo->email
        ?? null;

    // Debug (rimuovi dopo i test) TENERE CODICE PER TEST LOGIN
    /* var_dump($userinfo);
    var_dump($username);
    die(); */

    if ($username === null) {
        throw new Exception("Il provider OIDC non ha restituito preferred_username, username o email.");
    }

    if (OIDC_ALLOWED_USERS === [] || in_array($username, OIDC_ALLOWED_USERS, true)) {
        $_SESSION['admin'] = $username;
        $_SESSION['auth_type'] = 'oidc';

        header("Location: index.php");
        exit;
    } else {
        http_response_code(403);
        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>{$name} - Accedi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
</head>
<body>

  <div class="navbar">
    <div class="logo">{$name} - Admin Dashboard{$dev}</div>
    <div class="links">
      <a href="../index.php">Torna al sito</a>
    </div>
  </div>

  <!-- Container login -->
  <div class="login-container">
    <h1>Accedi</h1>
<br><div class='error'>Non sei autorizzato ad accedere a questa parte del sito.</div>
</div>
<p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.qzz.io/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.qzz.io/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
</p>
</body>
</html>
HTML;
    exit;
  }
  } catch (Exception $e) {
    http_response_code(500);
      echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>{$name} - Accedi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
</head>
<body>

  <div class="navbar">
    <div class="logo">{$name} - Admin Dashboard{$dev}</div>
    <div class="links">
      <a href="../index.php">Torna al sito</a>
    </div>
  </div>

  <!-- Container login -->
  <div class="login-container">
    <h1>Accedi</h1>
HTML;
if (DEV_MODE) {
  echo "<br><div class='error'>Errore durante l'autenticazione con OpenID Connect. Assicurati di avere impostato i vari parametri correttamente. Ulteriori dettagli: " . $e . "</div>";
} else {
  echo "<br><div class='error'>Errore durante l'autenticazione con OpenID Connect. Contatta l'amministratore del sito.</div>";
}
echo <<<HTML
<p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.qzz.io/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.qzz.io/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
</p>
</div>

</body>
</html>
HTML;
    exit;
  }
} else {
  die("Tipo di autenticazione non valido!");
}
?>
