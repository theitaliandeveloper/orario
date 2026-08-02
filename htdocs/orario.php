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
require_once __DIR__ . "/lib/db.php";
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

$days = ["Lunedì","Martedì","Mercoledì","Giovedì","Venerdì","Sabato"];
$hours = [
    1 => "Prima ora<br> 7:50 - 8:50",
    2 => "Seconda ora<br> 8:50 - 9:45",
    3 => "Terza ora<br> 9:55 - 10:50",
    4 => "Quarta ora<br> 10:50 - 11:45",
    5 => "Quinta ora<br> 11:55 - 12:50",
    6 => "Sesta ora<br> 12:50 - 13:50"
];

$view = $_GET['view'] ?? 'classe';
if (!in_array($view, ['classe', 'docente', 'laboratorio'], true)) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$raw_id = $_GET['id'];
$entity_name = '';
$view_label = $view;

if ($view === 'classe') {
    $id = intval($raw_id);
    $stmt = $conn->prepare("SELECT * FROM classes WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        header("Location: 404.php");
        exit;
    }
    $class_data = $res->fetch_assoc();
    $entity_name = $class_data['name'];
} elseif ($view === 'docente') {
    $id = $raw_id;
    if ($id == "No Lezione" || $id == "sconosciuto") {
        header("Location: index.php");
        exit;
    }
    $stmt = $conn->prepare("SELECT DISTINCT teacher FROM subjects WHERE teacher = ? LIMIT 1");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        header("Location: 404.php");
        exit;
    }
    $entity_name = normalise_string($id);
} elseif ($view === 'laboratorio') {
    $id = $raw_id;
    $stmt = $conn->prepare("SELECT DISTINCT room FROM subjects WHERE room = ? LIMIT 1");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        header("Location: 404.php");
        exit;
    }
    $entity_name = $id;
}

function joinList($arr) {
    if (empty($arr)) return '';
    if (count($arr) === 1) return $arr[0];
    $last = array_pop($arr);
    return implode(', ', $arr) . ' e ' . $last;
}

function getSlotData($conn, $view, $id, $d, $hnum) {
    if ($view === 'classe') {
        $stmt = $conn->prepare("SELECT subjects.name, subjects.teacher, subjects.room 
                                FROM timetable 
                                LEFT JOIN subjects ON timetable.subject_id = subjects.id 
                                WHERE class_id=? AND day=? AND hour=?");
        $stmt->bind_param("isi", $id, $d, $hnum);
        $stmt->execute();
        $q = $stmt->get_result();

        if ($q->num_rows === 0) return null;

        $subject = null;
        $teachers = [];
        $rooms = [];
        while ($row = $q->fetch_assoc()) {
            if ($subject === null) {
                $subject = normalise_string($row['name']);
            }
            if (!empty($row['teacher'])) {
                $t_norm = normalise_string($row['teacher']);
                if (!in_array($t_norm, $teachers, true)) {
                    $teachers[] = $t_norm;
                }
            }
            if (!empty($row['room']) && !in_array($row['room'], $rooms, true)) {
                $rooms[] = $row['room'];
            }
        }
        return [
            'subject'      => $subject,
            'secondary'    => joinList($teachers),
            'rooms'        => joinList($rooms),
            'teachers_arr' => $teachers,
            'rooms_arr'    => $rooms
        ];
    } elseif ($view === 'docente') {
        $stmt = $conn->prepare("SELECT subjects.name, classes.name AS class_name, subjects.room
                                FROM timetable
                                LEFT JOIN subjects ON timetable.subject_id = subjects.id
                                LEFT JOIN classes ON timetable.class_id = classes.id
                                WHERE subjects.teacher=? AND timetable.day=? AND timetable.hour=?");
        $stmt->bind_param("ssi", $id, $d, $hnum);
        $stmt->execute();
        $q = $stmt->get_result();

        if ($q->num_rows === 0) return null;

        $subject = null;
        $classes = [];
        $rooms = [];
        while ($row = $q->fetch_assoc()) {
            if ($subject === null) {
                $subject = normalise_string($row['name']);
            }
            if (!empty($row['class_name']) && !in_array($row['class_name'], $classes, true)) {
                $classes[] = $row['class_name'];
            }
            if (!empty($row['room']) && !in_array($row['room'], $rooms, true)) {
                $rooms[] = $row['room'];
            }
        }
        return [
            'subject'     => $subject,
            'secondary'   => joinList($classes),
            'rooms'       => joinList($rooms),
            'classes_arr' => $classes,
            'rooms_arr'   => $rooms
        ];
    } elseif ($view === 'laboratorio') {
        $stmt = $conn->prepare("SELECT subjects.name AS subject_name, subjects.teacher, classes.name AS class_name
                                FROM timetable
                                LEFT JOIN subjects ON timetable.subject_id = subjects.id
                                LEFT JOIN classes ON timetable.class_id = classes.id
                                WHERE subjects.room=? AND timetable.day=? AND timetable.hour=?");
        $stmt->bind_param("ssi", $id, $d, $hnum);
        $stmt->execute();
        $q = $stmt->get_result();

        if ($q->num_rows === 0) return null;

        $subject = null;
        $pairs = [];
        while ($row = $q->fetch_assoc()) {
            if ($subject === null) {
                $subject = normalise_string($row['subject_name']);
            }
            $t_norm = !empty($row['teacher']) ? normalise_string($row['teacher']) : '';
            $pair = $row['class_name'] . ($t_norm !== '' ? " (" . $t_norm . ")" : "");
            $pairs[$pair] = true;
        }
        $entries = array_keys($pairs);
        return [
            'subject'   => $subject,
            'secondary' => joinList($entries),
            'rooms'     => '',
            'pairs_arr' => $entries
        ];
    }
    return null;
}

if (isset($_GET['json']) && $_GET['json'] == '1' && OPEN_DATA) {
    require_once 'lib/json.php';
    exportTimetableJSON($conn, $view, $id);
    exit;
}

if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && PDF_EXPORT) {
    require_once 'lib/pdf.php';
    $pdf_type = ($view === 'classe') ? 'class' : (($view === 'docente') ? 'teacher' : 'room');
    exportTimetablePDF($conn, $pdf_type, $id);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Orario <?= htmlspecialchars($view_label . ' ' . $entity_name) ?> | <?= APP_NAME ?> <?= YEAR ?></title>
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
            <?= APP_NAME ?> <?= YEAR ?>
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
                <li class="nav-item">
                    <?php if (PDF_EXPORT):?>
                        <a class="nav-link fw-bold text-reset" href="?view=<?= urlencode($view) ?>&id=<?= urlencode($id) ?>&pdf=1" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Esporta PDF</a>
                    <?php endif;?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<h1 class="fw-bold text-center mt-5 mb-5">Orario <?= htmlspecialchars($view_label . ' ' . $entity_name) ?></h1>

<!-- Visualizzazione Desktop -->
<table class="table table-bordered table-striped-columns table-hover text-center d-none d-md-table">
<thead>
    <tr>
        <th>Ora/Giorno</th>
        <?php foreach ($days as $d) echo "<th>$d</th>"; ?>
    </tr>
</thead>

<?php foreach ($hours as $hnum => $hlabel): ?>
    <tr>
        <td class="fw-bold"><?= $hlabel ?></td>
        <?php foreach ($days as $d): ?>
            <?php
            $slot = getSlotData($conn, $view, $id, $d, $hnum);
            if ($slot !== null):
            ?>
            <td data-label="<?= htmlspecialchars($d) ?>">
                <div class="subject fw-bold text-primary-emphasis"><?= htmlspecialchars($slot['subject']) ?></div>
                <?php if (!empty($slot['secondary'])): ?>
                <div class="teacher small"><?= htmlspecialchars($slot['secondary']) ?></div>
                <?php endif; ?>
                <?php if (!empty($slot['rooms'])): ?>
                <div class="room text-secondary-emphasis small"><?= htmlspecialchars($slot['rooms']) ?></div>
                <?php endif; ?>
            </td>
            <?php else: ?>
            <td data-label="<?= htmlspecialchars($d) ?>"></td>
            <?php endif; ?>
        <?php endforeach; ?>
    </tr>
<?php endforeach; ?>
</table>

<!-- Visualizzazione Mobile -->
<div class="d-block d-md-none">
    <?php foreach ($days as $d): ?>
        <div class="card mb-3 shadow-sm">
            <div class="card-header fw-semibold">
                <?= htmlspecialchars($d) ?>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($hours as $hnum => $hlabel): ?>
                    <?php
                    $slot = getSlotData($conn, $view, $id, $d, $hnum);
                    ?>
                    <?php if ($slot !== null): ?>
                        <div class="list-group-item">
                            <div class="small text-body-secondary">
                                <?= strip_tags($hlabel) ?>
                            </div>
                            <div class="fw-semibold text-primary-emphasis">
                                <?= htmlspecialchars($slot['subject']) ?>
                            </div>
                            <?php if (!empty($slot['secondary'])): ?>
                                <div class="text-secondary-emphasis">
                                    <?= htmlspecialchars($slot['secondary']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($slot['rooms'])): ?>
                                <span class="badge border border-info text-info mt-1">
                                    <?= htmlspecialchars($slot['rooms']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="list-group-item text-body-tertiary">
                            <div class="small">
                                <?= strip_tags($hlabel) ?>
                            </div>
                            <div>—</div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

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
