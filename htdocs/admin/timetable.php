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
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }

// --- Recupera tutte le materie ---
$subjects = [];
$res = $conn->query("SELECT * FROM subjects ORDER BY name ASC");
while ($r = $res->fetch_assoc()) {
    $label = $r['name'];
    if (!empty($r['teacher'])) $label .= " ({$r['teacher']})";
    if (!empty($r['room']))    $label .= " ({$r['room']})";
    $subjects[] = ['id' => $r['id'], 'label' => $label];
}

// --- Salvataggio orario ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['class_id']) && isset($_POST['subject'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die("Token CSRF non valido."); }
    $class_id = intval($_POST['class_id']);
    if ($class_id > 0) {
        // Cancella solo l'orario di questa classe
        $stmt_del = $conn->prepare("DELETE FROM timetable WHERE class_id=?");
        $stmt_del->bind_param("i", $class_id);
        $stmt_del->execute();

        $stmt_ins = $conn->prepare("INSERT INTO timetable (class_id, day, hour, subject_id) VALUES (?, ?, ?, ?)");
        
        foreach ($_POST['subject'] as $day => $hours) {
            foreach ($hours as $hour => $sub_ids) {
                foreach ($sub_ids as $subject_id) {
                    $subject_id = intval($subject_id);
                    if (!empty($subject_id)) {
                        $stmt_ins->bind_param("isii", $class_id, $day, $hour, $subject_id);
                        $stmt_ins->execute();
                    }
                }
            }
        }

        header("Location: timetable.php?class_id=$class_id&saved=1");
        exit;
    }
}

// --- Selezione classe corrente ---
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// --- Precaricamento dati orario ---
$preselectedData = [];
if ($class_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM timetable WHERE class_id=?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $preselectedData[$r['day']][$r['hour']][] = $r['subject_id'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo APP_NAME; ?> - Gestisci Orario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .subject-container select { min-width: 120px; }
        .subject-container button { cursor: pointer; margin-left: 3px; }
        .admin-container { max-width: 95%; margin: auto; background: #fff; padding: 15px; border-radius: 8px; }
        table { border-collapse: collapse; width: 100%; overflow-x: auto; display: block; }
        th, td { text-align: center; padding: 6px; border: 1px solid #ccc; }
        @media (max-width: 768px) {
            table { font-size: 14px; }
            th, td { padding: 4px; }
        }
        .saved-message {
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
            color: green;
        }
    </style>
    <link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
</head>
<body>
<div class="navbar">
    <div class="logo"><?php echo APP_NAME; ?> - Admin Dashboard<?php if (DEV_MODE){echo " - SVILUPPO";}?><?php if (isset($_SESSION['admin']) && MAINTENANCE){echo " - MANUTENZIONE";}?></div>
    <div class="links">
        <a href="index.php">Dashboard</a>
        <a href="logout.php?csrf_token=<?php echo generate_csrf_token(); ?>">Logout</a>
    </div>
</div>

<div class="admin-container">
    <h1>Gestisci Orario</h1>
    <a href="index.php" class="back-link">⬅ Torna al Dashboard</a>

    <form method="POST" autocomplete="off">
        <?php echo csrf_field(); ?>
        Classe:
        <select name="class_id" required onchange="window.location='timetable.php?class_id='+this.value;">
            <option value="" disabled <?= $class_id === 0 ? 'selected' : '' ?>>--Scegli un'opzione--</option>
            <?php
            $res = $conn->query("SELECT * FROM classes ORDER BY name ASC");
            while ($r = $res->fetch_assoc()) {
                $selected = ($class_id == $r['id']) ? 'selected' : '';
                echo "<option value='{$r['id']}' $selected>{$r['name']}</option>";
            }
            ?>
        </select>

        <br><br>
        <?php if ($class_id > 0): ?> 
        <table>
            <thead>
                <tr>
                    <th>Ora</th>
                    <th>Lunedì</th>
                    <th>Martedì</th>
                    <th>Mercoledì</th>
                    <th>Giovedì</th>
                    <th>Venerdì</th>
                    <th>Sabato</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $days = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
                for ($hour = 1; $hour <= 6; $hour++) {
                    echo "<tr>";
                    echo "<td>{$hour}ª ora</td>";
                    foreach ($days as $day) {
                        $preselected = $preselectedData[$day][$hour] ?? [''];
                        echo "<td>";
                        echo "<div class='subject-container' data-day='$day' data-hour='$hour'>";
                        foreach ($preselected as $subject_id) {
                            echo "<div class='subject-row' style='display:flex;align-items:center;gap:5px;margin-bottom:3px;'>";
                            echo "<select name='subject[$day][$hour][]'>";
                            echo "<option value=''>--</option>";
                            foreach ($subjects as $s) {
                                $sel = ($subject_id == $s['id']) ? 'selected' : '';
                                echo "<option value='{$s['id']}' $sel>" . htmlspecialchars($s['label']) . "</option>";
                            }
                            echo "</select>";
                            echo "<button type='button' class='remove-subject' style='background:#e74c3c;color:white;border:none;border-radius:3px;padding:2px 6px;'>−</button>";
                            echo "</div>";
                        }
                        echo "<button type='button' class='add-subject' style='background:#28a745;color:white;border:none;border-radius:3px;padding:2px 6px;'>+</button>";
                        echo "</div>";
                        echo "</td>";
                    }
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <br>
        <button type="submit">Salva orario</button>
        <?php endif; ?>
        <?php if (isset($_GET['saved'])): ?>
            <p class="saved-message">✅ Orario salvato con successo!</p>
        <?php endif; ?>
    </form>
    <p class="only-mobile" style="text-align: center;">
      Nota: Questa pagina si vede meglio da computer desktop. Se sei da computer, puoi ignorare questo messaggio.
    </p>
    <p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 20px;">
        Copyright &copy; 2025-<?php echo date("Y"); ?> EmmeV. - Rilasciato sotto <a href="https://git.vichingo455.com/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Licenza GNU AGPL 3.0</a>.<br>
        Codice sorgente disponibile su <a href="https://git.vichingo455.com/emmev-code/orario" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Gitea</a>.
        La favicon in uso è stata scaricata da <a href="https://www.vecteezy.com/free-png/clcok" target="_blank" style="color: #1f618d; text-decoration: none; font-weight: bold;">Vecteezy</a>.
    </p>
</div>

<script>
document.addEventListener('click', function(e){
    if(e.target.classList.contains('add-subject')){
        const container = e.target.closest('.subject-container');
        const firstRow = container.querySelector('.subject-row');
        const clone = firstRow.cloneNode(true);
        clone.querySelector('select').value = '';
        container.insertBefore(clone, e.target);
    }

    if(e.target.classList.contains('remove-subject')){
        const container = e.target.closest('.subject-container');
        const rows = container.querySelectorAll('.subject-row');
        if(rows.length > 1){
            e.target.closest('.subject-row').remove();
        } else {
            rows[0].querySelector('select').value = '';
        }
    }
});
</script>
</body>
</html>
