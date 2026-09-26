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
require_once __DIR__ . "/../lib/db.php";
require_once __DIR__ . "/../lib/csrf.php";
require_once __DIR__ . "/../lib/schema.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$now = time();
if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
$_SESSION['discard_after'] = $now + app_setting('SESSION_LIFETIME');

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== 'admin') {
    header("Location: index.php");
    exit;
}
if (schema_update_required($conn) && MANDATORY_SCHEMA_UPDATE) {
    header("Location: migrate.php?backto=preferences.php");
    exit;
}

$preferenceDefinitions = [
    'APP_NAME' => ['label' => 'Nome piattaforma', 'type' => 'text', 'group' => 'Generali'],
    'YEAR' => ['label' => 'Anno scolastico', 'type' => 'text', 'group' => 'Generali'],
    'PDF_EXPORT' => ['label' => 'Esportazione PDF', 'type' => 'checkbox', 'group' => 'Generali'],
    'MAINTENANCE' => ['label' => 'Modalita manutenzione', 'type' => 'checkbox', 'group' => 'Generali'],
    'AUTH_TYPE' => ['label' => 'Tipo autenticazione', 'type' => 'select', 'group' => 'Autenticazione'],
    'APP_DOMAIN' => ['label' => 'Dominio applicazione', 'type' => 'text', 'group' => 'Autenticazione'],
    'OIDC_ISSUER' => ['label' => 'Issuer OIDC', 'type' => 'url', 'group' => 'Autenticazione'],
    'OIDC_CLIENT_ID' => ['label' => 'Client ID OIDC', 'type' => 'text', 'group' => 'Autenticazione'],
    'OIDC_CLIENT_SECRET' => ['label' => 'Client secret OIDC', 'type' => 'password', 'group' => 'Autenticazione'],
    'OIDC_ALLOWED_USERS' => ['label' => 'Utenti OIDC autorizzati', 'type' => 'users', 'group' => 'Autenticazione'],
    'OIDC_NO_LOGOUT' => ['label' => 'Non disconnettere dal provider OIDC', 'type' => 'checkbox', 'group' => 'Autenticazione'],
    'PHP_MAX_RAM' => ['label' => 'Memoria massima PHP', 'type' => 'text', 'group' => 'Avanzate'],
    'SESSION_LIFETIME' => ['label' => 'Durata sessione (secondi)', 'type' => 'number', 'group' => 'Avanzate'],
    'API_URL' => ['label' => 'URL API importazione', 'type' => 'url', 'group' => 'Avanzate'],
    'ANNOUNCEMENT_TEXT' => ['label' => 'Testo annuncio', 'type' => 'textarea', 'group' => 'Generali'],
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(app_setting('APP_NAME')); ?> - Preferenze</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="../css/fonts.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script>
        const CSRF_TOKEN = "<?php echo generate_csrf_token(); ?>";
    </script>
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-md bg-primary shadow-sm rounded-bottom mb-4 px-3 text-light">
      <div class="container-fluid">
          <a class="navbar-brand fw-bold text-reset text-wrap text-break d-block" href="index.php">
              <i class="bi bi-clock"></i>&nbsp;
            <?php echo app_setting('APP_NAME'); ?> <?php echo app_setting('YEAR'); ?> - Admin
          </a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
              <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
              <ul class="navbar-nav">
                  <li class="nav-item">
                      <a class="nav-link fw-bold text-reset" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                  </li>
                  <li class="nav-item">
                      <a class="nav-link fw-bold text-reset" href="logout.php?csrf_token=<?php echo generate_csrf_token(); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
                  </li>
              </ul>
          </div>
      </div>
  </nav>

<main class="container my-4" style="max-width: 900px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="fw-bold mb-0"><i class="bi bi-sliders"></i> Preferenze piattaforma</h1>
        <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
    </div>
    <p class="text-secondary">Configura le impostazioni utilizzate dalla piattaforma.<br>Premere il pulsante "Salva preferenze" in fondo alla pagina per applicare le modifiche.</p>
    <div id="alert-container"></div>

    <form id="preferences-form">
        <?php foreach (['Generali', 'Autenticazione', 'Avanzate'] as $group): ?>
            <section class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-body-tertiary fw-bold"><?php echo htmlspecialchars($group); ?></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($preferenceDefinitions as $identifier => $definition): ?>
                            <?php if ($definition['group'] !== $group) continue; ?>
                            <div class="col-12 <?php echo $definition['type'] === 'checkbox' ? '' : 'col-md-6'; ?>">
                                <?php if ($definition['type'] === 'checkbox'): ?>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="<?php echo $identifier; ?>">
                                        <label class="form-check-label" for="<?php echo $identifier; ?>"><?php echo htmlspecialchars($definition['label']); ?></label>
                                    </div>
                                <?php elseif ($definition['type'] === 'select'): ?>
                                    <label class="form-label" for="<?php echo $identifier; ?>"><?php echo htmlspecialchars($definition['label']); ?></label>
                                    <select class="form-select" id="<?php echo $identifier; ?>">
                                        <option value="local">Local</option>
                                        <option value="oidc">OpenID Connect</option>
                                    </select>
                                <?php elseif ($definition['type'] === 'users'): ?>
                                    <label class="form-label" for="<?php echo $identifier; ?>"><?php echo htmlspecialchars($definition['label']); ?></label>
                                    <textarea class="form-control" id="<?php echo $identifier; ?>" rows="4"></textarea>
                                    <div class="form-text">Un nome utente per riga. Lascia vuoto per consentire tutti gli utenti OIDC.</div>
                                <?php elseif ($definition['type'] === 'textarea'): ?>
                                    <label class="form-label" for="<?php echo $identifier; ?>"><?php echo htmlspecialchars($definition['label']); ?></label>
                                    <textarea class="form-control" id="<?php echo $identifier; ?>" rows="4"></textarea>
                                <?php else: ?>
                                    <label class="form-label" for="<?php echo $identifier; ?>"><?php echo htmlspecialchars($definition['label']); ?></label>
                                    <input class="form-control" type="<?php echo $definition['type']; ?>" id="<?php echo $identifier; ?>" <?php echo $definition['type'] === 'number' ? 'min="60"' : ''; ?>>
                                    <?php if ($identifier === 'OIDC_CLIENT_SECRET'): ?><div class="form-text">Lascia vuoto per mantenere il valore attuale.</div><?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
        <div class="d-flex justify-content-end gap-2 mb-5">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Salva preferenze</button>
        </div>
    </form>
</main>

    <!-- Footer -->
<footer class="text-center text-body-secondary small mt-3 mb-3">
    Copyright &copy; 2025-<?php echo date("Y"); ?> EmmeV. Rilasciato sotto <a href="https://git.vichingo455.com/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" class="fw-bold text-decoration-none">Licenza GNU AGPL 3.0</a>.
    <br>
    Codice sorgente disponibile su <a href="https://git.vichingo455.com/emmev-code/orario" target="_blank" class="fw-bold text-decoration-none">Gitea</a>.
</footer>

<script>
    const form = document.getElementById('preferences-form');
    const alertContainer = document.getElementById('alert-container');
    const preferenceDefinitions = <?php echo json_encode($preferenceDefinitions, JSON_UNESCAPED_UNICODE); ?>;
    const oidcFieldIds = [
        'APP_DOMAIN',
        'OIDC_ISSUER',
        'OIDC_CLIENT_ID',
        'OIDC_CLIENT_SECRET',
        'OIDC_ALLOWED_USERS',
        'OIDC_NO_LOGOUT'
    ];

    function closeAlert() {
        alertContainer.innerHTML = "";
    }

    function showAlert(message, type = "success") {
        alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show shadow-sm" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="closeAlert()"></button>
        </div>`;
    }

    const pendingResult = sessionStorage.getItem('preferences_result');
    if (pendingResult) {
        sessionStorage.removeItem('preferences_result');
        const isSuccess = pendingResult.startsWith('OK:');
        showAlert(pendingResult.substring(isSuccess ? 4 : 8), isSuccess ? 'success' : 'danger');
    }

    function setPreferenceValue(identifier, value, type) {
        const input = document.getElementById(identifier);
        if (!input) return;
        if (type === 'checkbox') {
            input.checked = value === true;
        } else if (type === 'users') {
            input.value = Array.isArray(value) ? value.join('\n') : '';
        } else {
            input.value = value ?? '';
        }
    }

    function updateOidcFields() {
        const oidcEnabled = document.getElementById('AUTH_TYPE').value === 'oidc';
        oidcFieldIds.forEach(identifier => {
            const input = document.getElementById(identifier);
            if (input) input.disabled = !oidcEnabled;
        });
    }

    async function readApiResponse(response) {
        const body = await response.text();
        let data;
        try {
            data = JSON.parse(body);
        } catch (error) {
            throw new Error(body.trim() || 'Risposta non valida dall\'API.');
        }
        if (!response.ok) throw new Error(data.error || 'Errore nella richiesta API.');
        return data;
    }

    async function loadPreferences() {
        const response = await fetch('../api/admin/preferences.php', { signal: AbortSignal.timeout(5000) });
        const data = await readApiResponse(response);
        Object.entries(preferenceDefinitions).forEach(([identifier, definition]) => {
            setPreferenceValue(identifier, data.preferences[identifier], definition.type);
        });
        updateOidcFields();
    }

    document.getElementById('AUTH_TYPE').addEventListener('change', updateOidcFields);

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const values = {};
        Object.entries(preferenceDefinitions).forEach(([identifier, definition]) => {
            const input = document.getElementById(identifier);
            if (definition.type === 'checkbox') {
                values[identifier] = input.checked;
            } else if (definition.type === 'users') {
                values[identifier] = input.value.split(/\r?\n/).map(value => value.trim()).filter(Boolean);
            } else {
                values[identifier] = input.value;
            }
        });

        try {
            const response = await fetch('../api/admin/preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': CSRF_TOKEN
                },
                body: JSON.stringify(values),
                signal: AbortSignal.timeout(5000)
            });
            await readApiResponse(response);
            sessionStorage.setItem('preferences_result', 'OK: Preferenze salvate correttamente.');
            window.location.replace('../admin/preferences.php');
        } catch (error) {
            sessionStorage.setItem('preferences_result', `ERRORE: ${error.message}`);
            window.location.replace('../admin/preferences.php');
        }
    });

    loadPreferences().catch(error => showAlert(error.message, 'danger'));
</script>
<script src="../js/theme.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
