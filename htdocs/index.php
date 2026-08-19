<?php
/*
Orario Scuola, Copyright (C) 2025-2026 EmmeV.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
*/
require_once __DIR__ . "/lib/variables.php";
require_once __DIR__ . "/lib/csrf.php";
require_once __DIR__ . "/lib/misc.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$now = time();
if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) { 
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
$_SESSION['discard_after'] = $now + SESSION_LIFETIME; 
if (!isset($_SESSION['admin']) && MAINTENANCE) {
    header("Location: manutenzione.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - A.S. <?php echo YEAR; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="./css/fonts.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>

<body>
    <nav class="navbar navbar-expand-md bg-primary shadow-sm rounded-bottom mb-4 px-3 text-light">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-reset" href="index.php">
                <i class="bi bi-clock"></i>&nbsp;
                <?php echo APP_NAME; ?> <?php echo YEAR; ?>
                <?php if (DEV_MODE ?? false) echo " - SVILUPPO"; ?>
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
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-reset" href="admin/index.php"><i class="bi bi-shield"></i> Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <h1 class="fw-bold text-center mb-4">
        <?php echo APP_NAME; ?> - A.S. <?php echo YEAR; ?>
    </h1>
    <?php
    if (MAINTENANCE) {
    ?>
        <div class="alert alert-warning text-center" role="alert">
            <strong>Attenzione!</strong> Modalità di manutenzione attiva.
        </div>
    <?php
    }
    ?>
    <div class="container">
        <div class="row justify-content-center mb-4">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="input-group">
                    <input
                        type="text"
                        id="searchBox"
                        class="form-control"
                        placeholder="Cerca classe, docente o laboratorio..."
                        autocomplete="off">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                </div>
            </div>
        </div>
        <h2 class="mb-3"><i class="bi bi-calendar"></i> Classi</h2>
        <div class="row g-3" id="classes-container"></div>
        
        <h2 class="mt-5 mb-3"><i class="bi bi-people-fill"></i> Docenti</h2>
        <div class="row g-3" id="teachers-container"></div>
        
        <h2 class="mb-3 mt-4"><i class="bi bi-flask"></i> Laboratori</h2>
        <div class="row g-3" id="labs-container"></div>
    </div>
    <footer class="text-center text-body-secondary small mt-5 mb-3">
        Copyright &copy; 2025-<?php echo date("Y"); ?>
        EmmeV. Rilasciato sotto
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
    <script>
    document.addEventListener("DOMContentLoaded", async function() {

        // Fetch Classi
        try {
            const res = await fetch("api/getClassi.php");
            const classi = await res.json();
            
            const years = { 1: "Prime", 2: "Seconde", 3: "Terze", 4: "Quarte", 5: "Quinte" };
            let html = "";
            for (let y = 1; y <= 5; y++) {
                const filtered = classi.filter(c => c.name.startsWith(y.toString()));
                html += `<div class="col-12 col-sm-6 col-md-4 col-lg"><div class="card h-100"><div class="card-body">
                         <h5 class="card-title">${years[y]}</h5><div class="list-group list-group-flush">`;
                filtered.forEach(c => {
                    html += `<a href="orario.php?view=classe&id=${c.id}" class="list-group-item list-group-item-action">${c.name}</a>`;
                });
                html += `</div></div></div></div>`;
            }
            document.getElementById("classes-container").innerHTML = html;
        } catch (e) { console.error("Error loading classes", e); }

        // Fetch Docenti
        try {
            const res = await fetch("api/getDocenti.php");
            const docenti = await res.json();
            let html = "";
            docenti.forEach(d => {
                html += `<div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="card h-100 shadow-sm"><div class="card-body text-center">
                         <h5 class="card-title">${d}</h5>
                         <a href="orario.php?view=docente&id=${encodeURIComponent(d)}" class="btn btn-outline-info btn-sm">Visualizza orario</a>
                         </div></div></div>`;
            });
            document.getElementById("teachers-container").innerHTML = html;
        } catch (e) { console.error("Error loading teachers", e); }

        // Fetch Labs
        try {
            const res = await fetch("api/getLabs.php");
            const labs = await res.json();
            let html = "";
            labs.forEach(l => {
                html += `<div class="col-12 col-sm-6 col-md-4 col-lg-3"><div class="card h-100 shadow-sm"><div class="card-body text-center">
                         <h5 class="card-title">${l}</h5>
                         <a href="orario.php?view=laboratorio&id=${encodeURIComponent(l)}" class="btn btn-outline-info btn-sm">Visualizza orario</a>
                         </div></div></div>`;
            });
            document.getElementById("labs-container").innerHTML = html;
        } catch (e) { console.error("Error loading labs", e); }
    });
    </script>
    <script src="js/index.js"></script>
    <script src="js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
