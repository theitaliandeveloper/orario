<?php
require_once __DIR__ . "/lib/db.php";
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> | Pagina non trovata</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body class="bg-body-tertiary">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>