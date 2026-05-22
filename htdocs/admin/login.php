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
require '../vendor/autoload.php';
session_start();
include("../lib/db.php");
if (isset($_SESSION['admin'])) { header("Location: index.php"); exit; }
if ($_SERVER["REQUEST_METHOD"] == "POST" && AUTH_TYPE == 'local') {
    try {
      $username = $_POST['username'];
      $password = $_POST['password'];
      $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) {
          if (password_verify($password, $row['password'])) {
            $_SESSION['admin'] = $row['username'];
            $_SESSION['auth_type'] = 'local';
            header("Location: index.php");
            exit;
          }
      }
      $error = "Credenziali non valide";
    } catch (Exception $e) {
      $error = "Errore durante l'autenticazione. Potrebbe essere un problema con PHP oppure col database. Ulteriori dettagli: " . $e;
    }
}
$name = APP_NAME;
if (strtolower(AUTH_TYPE) == 'local') {
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>{$name} - Accedi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="../favicon.svg" type="image/svg+xml">
</head>
<body>

  <div class="navbar">
    <div class="logo">{$name} - Admin Dashboard</div>
    <div class="links">
      <a href="/">Torna al sito</a>
    </div>
  </div>

  <!-- Container login -->
  <div class="login-container">
    <h1>Accedi</h1>
    <form method="post">
      <input type="text" name="username" placeholder="Username" required><br>
      <input type="password" name="password" placeholder="Password" required><br>
      <button type="submit">Login</button>
    </form>
HTML;
if(isset($error)) echo "<br><div class='error'>$error</div>";
echo <<<HTML
</div>
<p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.freeddns.org/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
</p>
</body>
</html>
HTML;
}
else if (strtolower(AUTH_TYPE) === 'keycloak') {
  try {
    // Configura il client Keycloak
  $oidc = new OpenIDConnectClient(
    'https://' . KEYCLOAK_DOMAIN . '/realms/' . KEYCLOAK_REALM . '/',
    KEYCLOAK_CLIENT_ID,
    KEYCLOAK_CLIENT_SECRET
  );
  // Redirect post-login
  $oidc->setRedirectURL('https://' . APP_DOMAIN . '/admin/login.php');
  $oidc->authenticate();
  $userinfo = $oidc->getVerifiedClaims();
  if (in_array($userinfo->preferred_username, KEYCLOAK_ALLOWED_USERS, true) || empty(KEYCLOAK_ALLOWED_USERS)) {
    $_SESSION['admin'] = $userinfo->preferred_username;
    $_SESSION['auth_type'] = 'keycloak';
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
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="../favicon.svg" type="image/svg+xml">
</head>
<body>

  <div class="navbar">
    <div class="logo">{$name} - Admin Dashboard</div>
    <div class="links">
      <a href="/">Torna al sito</a>
    </div>
  </div>

  <!-- Container login -->
  <div class="login-container">
    <h1>Accedi</h1>
<br><div class='error'>Non sei autorizzato ad accedere a questa parte del sito.</div>
</div>
<p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.freeddns.org/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
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
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="../favicon.svg" type="image/svg+xml">
</head>
<body>

  <div class="navbar">
    <div class="logo">{$name} - Admin Dashboard</div>
    <div class="links">
      <a href="/">Torna al sito</a>
    </div>
  </div>

  <!-- Container login -->
  <div class="login-container">
    <h1>Accedi</h1>
HTML;
if (DEV_MODE) {
  echo "<br><div class='error'>Errore durante l'autenticazione con Keycloak. Assicurati di avere impostato i vari parametri correttamente. Ulteriori dettagli: " . $e . "</div>";
} else {
  echo "<br><div class='error'>Errore durante l'autenticazione con Keycloak. Contatta l'amministratore del sito.</div>";
}
echo <<<HTML
<p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-2026 EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.freeddns.org/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
</p>
</div>

</body>
</html>
HTML;
    exit;
  }
}
?>
