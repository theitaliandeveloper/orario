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
    if (isset($_SESSION['discard_after']) && $now > $_SESSION['discard_after']) { // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
    session_unset();
    session_destroy();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    }
    $_SESSION['discard_after'] = $now + SESSION_LIFETIME; // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
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

    if (!isset($_GET['class_id'])) {
    header("Location: index.php");
    exit;
    }

    $class_id = intval($_GET['class_id']);
    $stmt = $conn->prepare("SELECT * FROM classes WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
    header("Location:404.php");
    exit;
    }

    $class = $res->fetch_assoc();

    // Helper: dato un risultato query, estrae subject, teachers[], rooms[]
    function parseRows($q) {
    $subject = null;
    $teachers = [];
    $rooms = [];

    while ($row = $q->fetch_assoc()) {
        if ($subject === null) {
            $subject = $row['name'];
        }
        if (!empty($row['teacher']) && !in_array($row['teacher'], $teachers)) {
            $teachers[] = $row['teacher'];
        }
        if (!empty($row['room']) && !in_array($row['room'], $rooms)) {
            $rooms[] = $row['room'];
        }
    }

    return [$subject, $teachers, $rooms];
    }

    // Helper: array → stringa "A, B e C"
    function joinList($arr) {
    if (empty($arr)) return '';
    if (count($arr) === 1) return $arr[0];
    $last = array_pop($arr);
    return implode(', ', $arr) . ' e ' . $last;
    }

    if (isset($_GET['json']) && $_GET['json'] == '1') {
    if (OPEN_DATA) {
        header('Content-Type: application/json; charset=utf-8');

        $timetable = [];

        foreach ($days as $d) {
            $d_clean = str_replace(
                ['à','è','é','ì','ò','ù'],
                ['a','e','e','i','o','u'],
                $d
            );
            $timetable[$d_clean] = [];

            foreach ($hours as $hnum => $hlabel) {
                $stmt = $conn->prepare("SELECT subjects.name, subjects.teacher, subjects.room 
                                FROM timetable 
                                LEFT JOIN subjects ON timetable.subject_id = subjects.id 
                                WHERE class_id=? AND day=? AND hour=?");
                $stmt->bind_param("isi", $class_id, $d, $hnum);
                $stmt->execute();
                $q = $stmt->get_result();

                if ($q->num_rows > 0) {
                    [$subject, $teachers, $rooms] = parseRows($q);

                    $timetable[$d_clean][$hnum] = [
                        'hour'     => $hnum,
                        'time'     => strip_tags($hlabel),
                        'subject'  => $subject,
                        'teachers' => $teachers,
                        'rooms'    => $rooms   // <-- ora è un array
                    ];
                } else {
                    $timetable[$d_clean][$hnum] = [
                        'hour'     => $hnum,
                        'time'     => strip_tags($hlabel),
                        'subject'  => null,
                        'teachers' => [],
                        'rooms'    => []
                    ];
                }
            }
        }

        echo json_encode([
            'class_id'   => $class_id,
            'class_name' => $class['name'],
            'timetable'  => $timetable
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        http_response_code(403);
        if (DEV_MODE) {
            echo "Non puoi accedere a questo JSON perchè gli Open Data in questa istanza sono disattivati. Per attivarli, apri il file config.php e modifica OPEN_DATA su true.";
        }
        else {
            echo "Non puoi accedere a questo JSON perchè non hai i permessi necessari per farlo.";
        }
        exit;
    }
    }

    if (isset($_GET['pdf']) && $_GET['pdf'] == '1' && PDF_EXPORT) {
    require_once 'lib/pdf.php';
    exportTimetablePDF($conn, 'class', $class_id);
    exit;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
    <title>Orario <?php echo htmlspecialchars($class['name']); ?> | <?php echo APP_NAME; ?> <?php echo YEAR; ?></title> <!-- OUTPUT previsto: "Orario + classe/docente/laboratorio + NOME"-->
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
                <?php if (DEV_MODE) echo " - SVILUPPO"; ?>
                <?php if (isset($_SESSION['admin']) && MAINTENANCE) echo " - MANUTENZIONE"; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-reset" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <?php if (PDF_EXPORT):?>
                            <a class="nav-link fw-bold text-reset" href="?class_id=<?= $class_id ?>&pdf=1" target="_blank">Esporta PDF</a>
                        <?php endif;?>
                    </li>
                </ul>
            </div>

        </div>
    </nav>

    <h1 class="fw-bold text-center mt-5 mb-5">Orario <!--"classe/docente/laboratorio"--> <?php echo htmlspecialchars($class['name']); ?></h1>

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
                $stmt = $conn->prepare("SELECT subjects.name, subjects.teacher, subjects.room 
                                FROM timetable 
                                LEFT JOIN subjects ON timetable.subject_id = subjects.id 
                                WHERE class_id=? AND day=? AND hour=?");
                $stmt->bind_param("isi", $class_id, $d, $hnum);
                $stmt->execute();
                $q = $stmt->get_result();

                if ($q->num_rows > 0):
                    [$subject, $teachers, $rooms] = parseRows($q);
                    $teachers_str = joinList($teachers);
                    $rooms_str    = joinList($rooms);
                ?>
                <td data-label="<?= htmlspecialchars($d) ?>">
                    <div class="subject fw-bold text-success"><?= normalise_string(htmlspecialchars($subject)) ?></div>
                    <div class="teacher"><?= normalise_string(htmlspecialchars($teachers_str)) ?></div>
                    <?php if (!empty($rooms_str)): ?>
                    <div class="room text-secondary-emphasis"><?= htmlspecialchars($rooms_str) ?></div>
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
    <div class="day">
        <h2><?= htmlspecialchars($d) ?></h2>
        <?php foreach ($hours as $hnum => $hlabel): ?>
        <?php
        $stmt = $conn->prepare("SELECT subjects.name, subjects.teacher, subjects.room 
                            FROM timetable 
                            LEFT JOIN subjects ON timetable.subject_id = subjects.id 
                            WHERE class_id=? AND day=? AND hour=?");
        $stmt->bind_param("isi", $class_id, $d, $hnum);
        $stmt->execute();
        $q = $stmt->get_result();

        if ($q->num_rows > 0):
            [$subject, $teachers, $rooms] = parseRows($q);
            $teachers_str = joinList($teachers);
            $rooms_str    = joinList($rooms);
        ?>
            <div class="lesson">
            <div class="hour"><?= strip_tags($hlabel) ?></div>
            <div class="subject"><?= htmlspecialchars($subject) ?></div>
            <div class="teacher"><?= htmlspecialchars($teachers_str) ?></div>
            <?php if (!empty($rooms_str)): ?>
                <div class="room"><?= htmlspecialchars($rooms_str) ?></div>
            <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="lesson empty">
            <div class="hour"><?= strip_tags($hlabel) ?></div>
            <div class="subject">—</div>
            </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <footer class="text-center text-body-secondary small mt-5 mb-3">
        Copyright &copy; 2025-2026 EmmeV. Rilasciato sotto
        <a href="https://git.vichingo455.qzz.io/emmev-code/orario/src/branch/stable/LICENSE.txt"
            target="_blank"
            class="fw-bold text-decoration-none">
            Licenza GNU AGPL 3.0
        </a>.
        <br>
        Codice sorgente disponibile su
        <a href="https://git.vichingo455.qzz.io/emmev-code/orario"
            target="_blank"
            class="fw-bold text-decoration-none">
            Gitea
        </a>.
    </footer>
    <script src="js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    </body>
    </html>
