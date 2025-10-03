<?php
use Jumbojett\OpenIDConnectClient;
require 'vendor/autoload.php';
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
            if (DEV_MODE) {
              echo "[DEBUG] Password " . $password . " trovata con l'hash " . $row['password'] . '. <a href="index.php">Vai al panello amministrativo</a>';
            }
            else {
              header("Location: index.php");
            }
            exit;
          } else if (DEV_MODE) {
              echo "[DEBUG] Password " . $password . " non trovata nel database.";
          }
      }
      $error = "Credenziali non valide";
    } catch (Exception $e) {
      $error = "Errore durante l'autenticazione. Potrebbe essere un problema con PHP oppure col database. Ulteriori dettagli: " . $e;
    }
}
if (AUTH_TYPE == 'local') {
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>Login Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <div class="navbar">
    <div class="logo">Admin Dashboard</div>
    <div class="links">
      <a href="/">Torna al sito</a>
    </div>
  </div>

  <!-- Container login -->
  <div class="login-container">
    <h1>Login Admin</h1>
    <form method="post">
      <input type="text" name="username" placeholder="Username" required><br>
      <input type="password" name="password" placeholder="Password" required><br>
      <button type="submit">Login</button>
    </form>
HTML;
if(isset($error)) echo "<br><div class='error'>$error</div>";
echo <<<HTML
</div>
<p style="text-align: center;">Copyright (C) 2025 EmmeV. - Released under <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank">GNU AGPL 3.0 License</a>.</p>
</body>
</html>
HTML;
}
else if (AUTH_TYPE === 'keycloak') {
  try {
    // Configura il client Keycloak
  $oidc = new OpenIDConnectClient(
    'https://' + KEYCLOAK_DOMAIN + '/realms/' + KEYCLOAK_REALM + '/',
    KEYCLOAK_CLIENT_ID,
    KEYCLOAK_CLIENT_SECRET
  );
  // Redirect post-login
  $oidc->setRedirectURL('https://' + APP_DOMAIN + '/admin/login.php');
  $oidc->authenticate();
  $userinfo = $oidc->getVerifiedClaims();
  $_SESSION['admin'] = $userinfo->preferred_username;
  $_SESSION['auth_type'] = 'keycloak';
  header("Location: index.php");
  exit;
  } catch (Exception $e) {
    http_response_code(500);
      echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>Login Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <div class="navbar">
    <div class="logo">Admin Dashboard</div>
    <div class="links">
      <a href="/">Torna al sito</a>
    </div>
  </div>

  <!-- Container login -->
  <div class="login-container">
    <h1>Login Admin</h1>
HTML;
if (DEV_MODE) {
  echo "<br><div class='error'>Errore durante l'autenticazione con Keycloak. Assicurati di avere impostato i vari parametri correttamente. Ulteriori dettagli: " . $e . "</div>";
} else {
  echo "<br><div class='error'>Errore durante l'autenticazione con Keycloak. Contatta l'amministratore del sito.</div>";
}
echo <<<HTML
</div>
<p style="text-align: center;">Copyright (C) 2025 EmmeV. - Released under <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank">GNU AGPL 3.0 License</a>.</p>
</body>
</html>
HTML;
    exit;
  }
}
else if (AUTH_TYPE === 'google') {
  try {
    $oidc = new OpenIDConnectClient(
     'https://accounts.google.com',
      GOOGLE_CLIENT_ID,
      GOOGLE_CLIENT_SECRET
  );

  $oidc->setRedirectURL(GOOGLE_REDIRECT_URI);
  $oidc->addScope(['openid', 'email', 'profile']);

  // Callback da Google
  if (isset($_GET['code'])) {
    $oidc->authenticate();
    $email = $oidc->requestUserInfo('email');

    $domain = substr(strrchr($email, "@"), 1);

    if (!GOOGLE_ONLY_ALLOWED_DOMAINS || in_array($domain, GOOGLE_ALLOWED_DOMAINS)) {
      $_SESSION['admin'] = $email;
      $_SESSION['auth_type'] = 'google';
      header("Location: index.php");
      exit;
    } else {
      http_response_code(403);
      echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>Login Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <div class="navbar">
    <div class="logo">Admin Dashboard</div>
    <div class="links">
      <a href="/">Torna al sito</a>
    </div>
  </div>

  <!-- Container login -->
  <div class="login-container">
    <h1>Login Admin</h1>
HTML;
echo "<br><div class='error'>Non sei autorizzato ad accedere a questa pagina</div>";
echo <<<HTML
</div>
<p style="text-align: center;">Copyright (C) 2025 EmmeV. - Released under <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank">GNU AGPL 3.0 License</a>.</p>
</body>
</html>
HTML;
    exit;
    }
  }
  } catch (Exception $e) {
    http_response_code(500);
      echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>Login Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <div class="navbar">
    <div class="logo">Admin Dashboard</div>
    <div class="links">
      <a href="/">Torna al sito</a>
    </div>
  </div>

  <!-- Container login -->
  <div class="login-container">
    <h1>Login Admin</h1>
HTML;
if (DEV_MODE) {
  echo "<br><div class='error'>Errore durante l'autenticazione con Google. Assicurati di avere impostato i vari parametri correttamente. Ulteriori dettagli: " . $e . "</div>";
} else {
  echo "<br><div class='error'>Errore durante l'autenticazione con Google. Contatta l'amministratore del sito.</div>";
}
echo <<<HTML
</div>
<p style="text-align: center;">Copyright (C) 2025 EmmeV. - Released under <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank">GNU AGPL 3.0 License</a>.</p>
</body>
</html>
HTML;
    exit;
  }
}
?>
