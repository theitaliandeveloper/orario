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
$year = YEAR;

if (strtolower(AUTH_TYPE) == 'local') {
$csrf_field = csrf_field();
$error_alert = isset($error) ? "<div class='alert alert-danger mt-3 mb-0' role='alert'><i class='bi bi-exclamation-triangle-fill me-2'></i>{$error}</div>" : "";
echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>{$name} - Accedi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E">
  <link rel="stylesheet" href="../css/fonts.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

  <nav class="navbar navbar-expand-md bg-primary shadow-sm rounded-bottom mb-4 px-3 text-light">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-reset" href="../index.php">
        <i class="bi bi-clock"></i>&nbsp; {$name} {$year} - Admin
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link fw-bold text-reset" href="../index.php"><i class="bi bi-house"></i> Torna al sito</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-6 col-lg-4">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4">
            <h2 class="card-title text-center fw-bold mb-4"><i class="bi bi-shield-lock"></i> Accedi</h2>
            <form method="post">
              {$csrf_field}
              <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
              </div>
              <button type="submit" class="btn btn-primary w-100 fw-bold py-2"><i class="bi bi-box-arrow-in-right me-1"></i> Login</button>
            </form>
            {$error_alert}
          </div>
        </div>
      </div>
    </div>
  </div>

  <footer class="text-center text-body-secondary small mt-5 mb-3">
    Copyright &copy; 2025-2026 EmmeV. Rilasciato sotto
    <a href="https://git.vichingo455.com/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" class="fw-bold text-decoration-none">Licenza GNU AGPL 3.0</a>.
    <br>
    Codice sorgente disponibile su <a href="https://git.vichingo455.com/emmev-code/orario" target="_blank" class="fw-bold text-decoration-none">Gitea</a>.
  </footer>
  <script src="../js/theme.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
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
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E">
  <link rel="stylesheet" href="../css/fonts.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

  <nav class="navbar navbar-expand-md bg-primary shadow-sm rounded-bottom mb-4 px-3 text-light">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-reset" href="../index.php">
        <i class="bi bi-clock"></i>&nbsp; {$name} {$year} - Admin
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link fw-bold text-reset" href="../index.php"><i class="bi bi-house"></i> Torna al sito</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-12 col-md-6">
        <div class="alert alert-danger shadow-sm text-center p-4" role="alert">
          <h4 class="alert-heading fw-bold mb-3"><i class="bi bi-shield-x"></i> Accesso Negato</h4>
          <p class="mb-0">Non sei autorizzato ad accedere a questa parte del sito.</p>
        </div>
      </div>
    </div>
  </div>

  <footer class="text-center text-body-secondary small mt-5 mb-3">
    Copyright &copy; 2025-2026 EmmeV. Rilasciato sotto
    <a href="https://git.vichingo455.com/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" class="fw-bold text-decoration-none">Licenza GNU AGPL 3.0</a>.
    <br>
    Codice sorgente disponibile su <a href="https://git.vichingo455.com/emmev-code/orario" target="_blank" class="fw-bold text-decoration-none">Gitea</a>.
  </footer>
  <script src="../js/theme.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
HTML;
    exit;
  }
  } catch (Exception $e) {
    http_response_code(500);
    $errDetail = DEV_MODE ? "Errore durante l'autenticazione con OpenID Connect. Assicurati di avere impostato i vari parametri correttamente. Ulteriori dettagli: " . htmlspecialchars((string)$e) : "Errore durante l'autenticazione con OpenID Connect. Contatta l'amministratore del sito.";
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <title>{$name} - Accedi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E">
  <link rel="stylesheet" href="../css/fonts.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

  <nav class="navbar navbar-expand-md bg-primary shadow-sm rounded-bottom mb-4 px-3 text-light">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold text-reset" href="../index.php">
        <i class="bi bi-clock"></i>&nbsp; {$name} {$year} - Admin
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link fw-bold text-reset" href="../index.php"><i class="bi bi-house"></i> Torna al sito</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8">
        <div class="alert alert-danger shadow-sm p-4" role="alert">
          <h4 class="alert-heading fw-bold mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Errore Autenticazione</h4>
          <p class="mb-0">{$errDetail}</p>
        </div>
      </div>
    </div>
  </div>

  <footer class="text-center text-body-secondary small mt-5 mb-3">
    Copyright &copy; 2025-2026 EmmeV. Rilasciato sotto
    <a href="https://git.vichingo455.com/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" class="fw-bold text-decoration-none">Licenza GNU AGPL 3.0</a>.
    <br>
    Codice sorgente disponibile su <a href="https://git.vichingo455.com/emmev-code/orario" target="_blank" class="fw-bold text-decoration-none">Gitea</a>.
  </footer>
  <script src="../js/theme.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
HTML;
    exit;
  }
} else {
  die("Tipo di autenticazione non valido!");
}
?>
