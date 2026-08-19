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

$view = $_GET['view'] ?? 'classe';
$id = $_GET['id'] ?? '';
if (!in_array($view, ['classe', 'docente', 'laboratorio'], true) || empty($id)) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title><?= APP_NAME ?> <?= YEAR ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E">
<link rel="stylesheet" href="./css/fonts.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<script>
    const VIEW_TYPE = "<?php echo $view; ?>";
    const VIEW_ID = "<?php echo htmlspecialchars($id); ?>";
</script>
</head>
<body>
<nav class="navbar navbar-expand-md bg-primary shadow-sm rounded-bottom mb-4 px-3 text-light">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-reset" href="index.php">
            <i class="bi bi-clock"></i>&nbsp;
            <?= APP_NAME ?> <?= YEAR ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link fw-bold text-reset" href="index.php"><i class="bi bi-house"></i> Home</a>
                </li>
                <li class="nav-item" id="pdf-export">
                    <?php if (PDF_EXPORT):?>
                        <a class="nav-link fw-bold text-reset" href="api/getOrario.php?type=<?= urlencode($view) ?>&id=<?= urlencode($id) ?>&dl=1" target="_blank" download><i class="bi bi-file-earmark-pdf"></i> Esporta PDF</a>
                    <?php endif;?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<h1 class="fw-bold text-center mt-5 mb-5" id="page-title">Caricamento in corso...</h1>

<!-- Desktop View -->
<table class="table table-bordered table-striped-columns table-hover text-center d-none d-md-table" id="desktop-table">
    <thead>
        <tr id="desktop-head"></tr>
    </thead>
    <tbody id="desktop-body"></tbody>
</table>

<!-- Mobile View -->
<div class="d-block d-md-none" id="mobile-view"></div>

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
    const days = ["Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"];
    const hours = [
        "Prima ora<br> 7:50 - 8:50",
        "Seconda ora<br> 8:50 - 9:45",
        "Terza ora<br> 9:55 - 10:50",
        "Quarta ora<br> 10:50 - 11:45",
        "Quinta ora<br> 11:55 - 12:50",
        "Sesta ora<br> 12:50 - 13:50"
    ];

    try {
        const res = await fetch(`api/getOrario.php?type=${VIEW_TYPE}&id=${encodeURIComponent(VIEW_ID)}`,{ signal: AbortSignal.timeout(2000) }); // Prova a caricare i dati con timeout di 2 secondi
        if (!res.ok) {
            if (res.status == 404) {
                location.replace("404.php");
            }
            else {
                document.getElementById("page-title").innerText = "Errore nel caricamento";
                document.getElementById("desktop-table").innerHTML = "<a href=\"/index.php\" class=\"btn btn-primary\">Torna alla home</a>";
                document.getElementById("mobile-view").innerHTML = "<a href=\"/index.php\" class=\"btn btn-primary\">Torna alla home</a>";
                document.getElementById("pdf-export").innerHTML = "";
            }
            return;
        }
        
        const data = await res.json();
        
        let titleName = "";
        if (VIEW_TYPE === "classe") titleName = data.class_name;
        if (VIEW_TYPE === "docente") titleName = data.teacher;
        if (VIEW_TYPE === "laboratorio") titleName = data.room;
        document.getElementById("page-title").innerText = `Orario ${VIEW_TYPE}: ${titleName}`;
        document.title = `Orario ${titleName}`;

        const timetable = data.timetable;

        // Render Desktop
        let dHead = `<th>Ora/Giorno</th>`;
        days.forEach(d => dHead += `<th>${d}</th>`);
        document.getElementById("desktop-head").innerHTML = dHead;

        let dBody = "";
        for (let i = 1; i <= 6; i++) {
            dBody += `<tr><td class="fw-bold">${hours[i-1]}</td>`;
            days.forEach(d => {
                const dayClean = d.replace("ì", "i"); // getOrario cleans accents in JSON keys! Lunedi instead of Lunedì
                const slot = (timetable[dayClean] && timetable[dayClean][i]) ? timetable[dayClean][i] : null;
                if (slot && slot.subject) {
                    let secondary = "";
                    if (VIEW_TYPE === 'classe' && slot.teachers) secondary = slot.teachers.join(", ");
                    if (VIEW_TYPE === 'docente' && slot.classes) secondary = slot.classes.join(", ");
                    if (VIEW_TYPE === 'laboratorio' && slot.classes) secondary = slot.classes.map(c => `${c.class} (${c.teacher})`).join(", ");

                    let rooms = slot.rooms ? slot.rooms.join(", ") : "";
                    
                    dBody += `<td data-label="${d}">
                        <div class="subject fw-bold text-primary-emphasis">${slot.subject}</div>
                        ${secondary ? `<div class="teacher small">${secondary}</div>` : ''}
                        ${rooms ? `<div class="room text-secondary-emphasis small">${rooms}</div>` : ''}
                    </td>`;
                } else {
                    dBody += `<td data-label="${d}"></td>`;
                }
            });
            dBody += `</tr>`;
        }
        document.getElementById("desktop-body").innerHTML = dBody;

        // Render Mobile
        let mBody = "";
        days.forEach(d => {
            const dayClean = d.replace("ì", "i");
            mBody += `<div class="card mb-3 shadow-sm"><div class="card-header fw-semibold">${d}</div><div class="list-group list-group-flush">`;
            for (let i = 1; i <= 6; i++) {
                const slot = (timetable[dayClean] && timetable[dayClean][i]) ? timetable[dayClean][i] : null;
                const hlabel = hours[i-1].replace("<br>", " ");
                if (slot && slot.subject) {
                    let secondary = "";
                    if (VIEW_TYPE === 'classe' && slot.teachers) secondary = slot.teachers.join(", ");
                    if (VIEW_TYPE === 'docente' && slot.classes) secondary = slot.classes.join(", ");
                    if (VIEW_TYPE === 'laboratorio' && slot.classes) secondary = slot.classes.map(c => `${c.class} (${c.teacher})`).join(", ");

                    let rooms = slot.rooms ? slot.rooms.join(", ") : "";
                    
                    mBody += `<div class="list-group-item">
                        <div class="small text-body-secondary">${hlabel}</div>
                        <div class="fw-semibold text-primary-emphasis">${slot.subject}</div>
                        ${secondary ? `<div class="text-secondary-emphasis">${secondary}</div>` : ''}
                        ${rooms ? `<span class="badge border border-info text-info mt-1">${rooms}</span>` : ''}
                    </div>`;
                } else {
                    mBody += `<div class="list-group-item text-body-tertiary">
                        <div class="small">${hlabel}</div>
                        <div>—</div>
                    </div>`;
                }
            }
            mBody += `</div></div>`;
        });
        document.getElementById("mobile-view").innerHTML = mBody;
        
    } catch (e) {
        console.error(e);
        document.getElementById("page-title").innerText = "Errore nel caricamento";
        document.getElementById("desktop-table").innerHTML = "<a href=\"/index.php\" class=\"btn btn-primary\">Torna alla home</a>";
        document.getElementById("mobile-view").innerHTML = "<a href=\"/index.php\" class=\"btn btn-primary\">Torna alla home</a>";
        document.getElementById("pdf-export").innerHTML = "";
    }
});
</script>
<script src="js/theme.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
