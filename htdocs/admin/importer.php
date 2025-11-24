<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
include("../lib/db.php");
$message = "";
$messageType = "";

// Gestione importazione
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['import'])) {
    $classe_codice = trim($_POST['classe_codice']);
    $classe_id = intval($_POST['classe_id']);
    
    if (empty($classe_codice) || $classe_id === 0) {
        $message = "Compila tutti i campi obbligatori.";
        $messageType = "error";
    } else {
        try {
            // Chiama l'API Node.js
            $url = API_URL . "?classe=" . urlencode($classe_codice);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception("Errore nella chiamata API (HTTP $httpCode)");
            }
            
            $data = json_decode($response, true);
            
            if (!$data || !isset($data['giorni'])) {
                throw new Exception("Formato JSON non valido");
            }
            
            // Cancella l'orario esistente per questa classe
            $stmt = $conn->prepare("DELETE FROM timetable WHERE class_id = ?");
            $stmt->bind_param("i", $classe_id);
            $stmt->execute();
            $stmt->close();
            
            $inserimenti = 0;
            $materie_create = [];
            
            // Processa ogni giorno
            foreach ($data['giorni'] as $giorno => $ore) {
                foreach ($ore as $oraData) {
                    // Salta ore vuote
                    if ($oraData['materia'] === null) {
                        continue;
                    }
                    
                    $ora = $oraData['ora'];
                    $materia = $oraData['materia'];
                    $docenti = $oraData['docenti'];
                    $laboratori = $oraData['laboratori']; // Ora è un array
                    
                    // Se non ci sono docenti, salta (situazione anomala)
                    if (count($docenti) === 0) {
                        continue;
                    }
                    
                    // Caso 1: Stesso numero di docenti e laboratori → associazione 1:1
                    if (count($docenti) === count($laboratori) && count($laboratori) > 0) {
                        foreach ($docenti as $idx => $docente) {
                            $laboratorio = $laboratori[$idx];
                            
                            // Cerca/crea materia
                            $stmt = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND teacher = ? AND room = ?");
                            $stmt->bind_param("sss", $materia, $docente, $laboratorio);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $subject_id = $result->fetch_assoc()['id'];
                            } else {
                                $stmt2 = $conn->prepare("INSERT INTO subjects (name, teacher, room) VALUES (?, ?, ?)");
                                $stmt2->bind_param("sss", $materia, $docente, $laboratorio);
                                $stmt2->execute();
                                $subject_id = $conn->insert_id;
                                $stmt2->close();
                                $materie_create[] = "$materia ($docente - $laboratorio)";
                            }
                            $stmt->close();
                            
                            // Inserisci in timetable
                            $stmt3 = $conn->prepare("INSERT INTO timetable (class_id, day, hour, subject_id) VALUES (?, ?, ?, ?)");
                            $stmt3->bind_param("isii", $classe_id, $giorno, $ora, $subject_id);
                            $stmt3->execute();
                            $stmt3->close();
                            $inserimenti++;
                        }
                    }
                    // Caso 2: Più docenti, un laboratorio (o nessuno) → stesso laboratorio per tutti
                    else if (count($laboratori) <= 1) {
                        $laboratorio = count($laboratori) > 0 ? $laboratori[0] : null;
                        
                        foreach ($docenti as $docente) {
                            // Cerca/crea materia
                            if ($laboratorio) {
                                $stmt = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND teacher = ? AND room = ?");
                                $stmt->bind_param("sss", $materia, $docente, $laboratorio);
                            } else {
                                $stmt = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND teacher = ? AND (room IS NULL OR room = '')");
                                $stmt->bind_param("ss", $materia, $docente);
                            }
                            
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $subject_id = $result->fetch_assoc()['id'];
                            } else {
                                $stmt2 = $conn->prepare("INSERT INTO subjects (name, teacher, room) VALUES (?, ?, ?)");
                                $stmt2->bind_param("sss", $materia, $docente, $laboratorio);
                                $stmt2->execute();
                                $subject_id = $conn->insert_id;
                                $stmt2->close();
                                $materie_create[] = "$materia ($docente" . ($laboratorio ? " - $laboratorio" : "") . ")";
                            }
                            $stmt->close();
                            
                            // Inserisci in timetable
                            $stmt3 = $conn->prepare("INSERT INTO timetable (class_id, day, hour, subject_id) VALUES (?, ?, ?, ?)");
                            $stmt3->bind_param("isii", $classe_id, $giorno, $ora, $subject_id);
                            $stmt3->execute();
                            $stmt3->close();
                            $inserimenti++;
                        }
                    }
                    // Caso 3: Più laboratori che docenti → usa il primo laboratorio per tutti
                    else {
                        $laboratorio = $laboratori[0];
                        
                        foreach ($docenti as $docente) {
                            $stmt = $conn->prepare("SELECT id FROM subjects WHERE name = ? AND teacher = ? AND room = ?");
                            $stmt->bind_param("sss", $materia, $docente, $laboratorio);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                $subject_id = $result->fetch_assoc()['id'];
                            } else {
                                $stmt2 = $conn->prepare("INSERT INTO subjects (name, teacher, room) VALUES (?, ?, ?)");
                                $stmt2->bind_param("sss", $materia, $docente, $laboratorio);
                                $stmt2->execute();
                                $subject_id = $conn->insert_id;
                                $stmt2->close();
                                $materie_create[] = "$materia ($docente - $laboratorio)";
                            }
                            $stmt->close();
                            
                            $stmt3 = $conn->prepare("INSERT INTO timetable (class_id, day, hour, subject_id) VALUES (?, ?, ?, ?)");
                            $stmt3->bind_param("isii", $classe_id, $giorno, $ora, $subject_id);
                            $stmt3->execute();
                            $stmt3->close();
                            $inserimenti++;
                        }
                    }
                }
            }
            
            $message = "Importazione completata con successo!<br>";
            $message .= "- Inserite $inserimenti ore di lezione<br>";
            if (count($materie_create) > 0) {
                $message .= "- Create " . count($materie_create) . " nuove materie";
            }
            $messageType = "success";
            
        } catch (Exception $e) {
            $message = "Errore durante l'importazione: " . htmlspecialchars($e->getMessage());
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Importa Orario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        .import-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 0.9em;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .warning-box strong {
            color: #856404;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">Admin Dashboard</div>
    <div class="links">
        <a href="index.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="admin-container">
    <h1>Importa Orario da Sistema Esterno</h1>
    <a href="index.php" class="back-link">⬅ Torna al Dashboard</a>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="warning-box">
        <strong>Attenzione:</strong> L'importazione cancellerà l'orario esistente della classe selezionata 
        e lo sostituirà con i dati importati dal sistema esterno. 
        Verranno create automaticamente le materie mancanti.
    </div>

    <div class="import-form">
        <h2>Configura Importazione</h2>
        <form method="POST">
            <div class="form-group">
                <label for="classe_id">Classe di destinazione *</label>
                <select name="classe_id" id="classe_id" required>
                    <option value="">-- Seleziona classe --</option>
                    <?php
                    $res = $conn->query("SELECT * FROM classes ORDER BY name ASC");
                    while ($row = $res->fetch_assoc()) {
                        echo "<option value='{$row['id']}'>{$row['name']}</option>";
                    }
                    ?>
                </select>
                <small>Classe nel tuo database dove importare l'orario</small>
            </div>

            <div class="form-group">
                <label for="classe_codice">Codice classe sorgente *</label>
                <input type="text" name="classe_codice" id="classe_codice" 
                       placeholder="es: 1A, 2B, 3BIN..." required>
                <small>Codice della classe nel sistema esterno</small>
            </div>

            <button type="submit" name="import" style="width: 100%; padding: 12px; font-size: 16px;">
                🔄 Importa Orario
            </button>
        </form>
    </div>

    <div class="admin-container" style="margin-top: 30px;">
        <h3>Come funziona l'importazione</h3>
        <ol>
            <li>Assicurati che il server Node.js sia avviato (<code>node server.js</code>)</li>
            <li>Seleziona la classe di destinazione nel tuo database</li>
            <li>Inserisci il codice della classe nel sistema esterno (es: 3BIN, 1A, 5AINF)</li>
            <li>Clicca su "Importa Orario"</li>
            <li>Il sistema cancellerà l'orario esistente e importerà i nuovi dati</li>
        </ol>

        <h3>Gestione casi speciali</h3>
        <ul>
            <li><strong>Più docenti, più laboratori</strong>: Associazione 1:1 (docente1→lab1, docente2→lab2)</li>
            <li><strong>Più docenti, un laboratorio</strong>: Stesso laboratorio per tutti i docenti</li>
            <li><strong>Più docenti, nessun laboratorio</strong>: Nessun laboratorio per tutti</li>
            <li><strong>Un docente, più laboratori</strong>: Viene usato il primo laboratorio</li>
        </ul>
    </div>

    <p style="text-align: center; margin-top: 30px;">
        Copyright (C) 2025 EmmeV. - Released under <a href="https://git.vichingo455.freeddns.org/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank">GNU AGPL 3.0 License</a>.
    </p>
</div>

</body>
</html>
