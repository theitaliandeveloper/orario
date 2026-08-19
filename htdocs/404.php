<?php
require_once __DIR__ . "/lib/variables.php";
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> | 404</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E">    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-body-tertiary">
    <nav class="navbar navbar-expand-md bg-primary shadow-sm rounded-bottom mb-4 px-3 text-light">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-reset" href="index.php">
                <i class="bi bi-clock"></i>&nbsp;
                <?php echo APP_NAME; ?> <?php echo YEAR; ?>
                <?php if (DEV_MODE) echo " - SVILUPPO"; ?>
                <?php if (isset($_SESSION['admin']) && MAINTENANCE) echo " - MANUTENZIONE"; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-reset" href="index.php"><i class="bi bi-house"></i> Home</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container min-vh-100 d-flex align-items-center justify-content-center">
        <div class="text-center">
            <h1 class="display-1 fw-bold text-danger">
                404
            </h1>
            <h2 class="mb-3">
                Pagina non trovata
            </h2>
            <p class="text-body-secondary mb-4">
                La pagina richiesta non esiste.
            </p>
            <a href="/index.php" class="btn btn-primary">
                Torna alla home
            </a>
        </div>
    </main>
    <footer class="text-center text-body-secondary small mt-5 mb-3">
        Copyright &copy; 2025-<?php echo date("Y"); ?> EmmeV. Rilasciato sotto
        <a href="https://git.vichingo455.com/emmev-code/orario/src/branch/stable/LICENSE.txt"
            target="_blank"
            class="fw-bold text-decoration-none">
            Licenza GNU AGPL 3.0
        </a>.
        <br>
        Codice sorgente disponibile su
        <a href="https://git.vichingo455.com/emmev-code/orario"
            target="_blank"
            class="fw-bold text-decoration-none">
            Gitea
        </a>.
    </footer>
    <script src="js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>