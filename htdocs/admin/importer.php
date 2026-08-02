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
else if (!defined('API_URL') || API_URL == "") { header("Location: index.php"); exit; }
$message = "";
$messageType = "";

// Gestione importazione
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['import'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die("Token CSRF non valido."); }
    $classe_codice = trim($_POST['classe_codice']);
    $classe_id = intval($_POST['classe_id']);
    
    if (empty($classe_codice) || $classe_id === 0) {
        $message = "Compila tutti i campi obbligatori.";
        $messageType = "error";
    } else {
        try {
            // Controlli vari a prova di ignorante
            $baseUrl = rtrim(API_URL, '/');
            $suffix = str_ends_with($baseUrl, "/classe") ? "" : "/classe";
            $url = $baseUrl . $suffix . "?classe=" . urlencode($classe_codice);
            // Chiama l'API Node.js
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
            
            // Verifica che il dato sia un array (nuovo formato)
            if (!$data || !is_array($data)) {
                throw new Exception("Formato JSON non valido o vuoto");
            }
            
            // Cancella l'orario esistente per questa classe
            $stmt = $conn->prepare("DELETE FROM timetable WHERE class_id = ?");
            $stmt->bind_param("i", $classe_id);
            $stmt->execute();
            $stmt->close();
            
            $inserimenti = 0;
            $materie_create = [];
            
            // --- INIZIO LOGICA NUOVO FORMATO ---
            
            // Ciclo sui GIORNI (Indice 0 = Lunedì, 1 = Martedì...)
            foreach ($data as $dayIndex => $dayHours) {
                $giorno = $dayIndex + 1; // Convertiamo 0-based in 1-based per il DB
                
                // Ciclo sulle ORE del giorno (Indice 0 = 1a ora, 1 = 2a ora...)
                foreach ($dayHours as $hourIndex => $lessons) {
                    $ora = $hourIndex + 1; // Convertiamo 0-based in 1-based (8:00 = 1)
                    
                    // Se l'ora è vuota [], saltiamo
                    if (empty($lessons)) {
                        continue;
                    }

                    // Ciclo sulle LEZIONI nell'ora (solitamente 1, ma supporta eventuali compresentze strutturali)
                    foreach ($lessons as $lessonData) {
                        
                        // Mappatura dei campi dal NUOVO formato
                        // Ignoriamo 'materia' lunga e usiamo la short, ignoriamo bgcolor, etc.
                        $materia = $lessonData['materia_short'];
                        
                        // 'aule' è già un array nel nuovo formato ["Aula x", "Aula y"]
                        $laboratori = isset($lessonData['aule']) ? $lessonData['aule'] : [];
                        
                        // 'docenti' è un oggetto {"NOME": "NOME"}, prendiamo le chiavi
                        $docenti = [];
                        if (isset($lessonData['docenti']) && is_array($lessonData['docenti'])) {
                            $docenti = array_keys($lessonData['docenti']);
                        }

                        // Se non ci sono docenti o materia, saltiamo
                        if (empty($materia) || count($docenti) === 0) {
                            continue;
                        }

                        // --- DA QUI IN POI LA TUA LOGICA ORIGINALE RIMANE INVARIATA ---
                        
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
                    } // Fine foreach lessons
                } // Fine foreach ore
            } // Fine foreach giorni
            
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
    <title><?php echo APP_NAME; ?> - Importa Orario</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3E%3Cpath d='M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z'/%3E%3Cpath d='M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="../css/fonts.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-md bg-primary shadow-sm rounded-bottom mb-4 px-3 text-light">
      <div class="container-fluid">
          <a class="navbar-brand fw-bold text-reset" href="index.php">
              <i class="bi bi-clock"></i>&nbsp;
              <?php echo APP_NAME; ?> <?php echo YEAR; ?> - Admin
              <?php if (DEV_MODE) echo " - SVILUPPO"; ?>
              <?php if (isset($_SESSION['admin']) && MAINTENANCE) echo " - MANUTENZIONE"; ?>
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

<div class="container my-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="fw-bold mb-0"><i class="bi bi-cloud-arrow-down"></i> Importa Orario da Sistema Esterno</h1>
        <a href="index.php" class="btn btn-outline-info"><i class="bi bi-arrow-left"></i> Torna alla Dashboard</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo ($messageType === 'error') ? 'danger' : 'success'; ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-<?php echo ($messageType === 'error') ? 'exclamation-triangle-fill' : 'check-circle-fill'; ?> me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="alert alert-warning shadow-sm mb-4" role="alert">
        <h5 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle me-1"></i> Attenzione!</h5>
        L'importazione cancellerà l'orario esistente della classe selezionata 
        e lo sostituirà con i dati importati dal sistema esterno. 
        Verranno create automaticamente le materie mancanti.
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body-tertiary fw-bold fs-5">
            <i class="bi bi-gear-fill me-1"></i> Configura Importazione
        </div>
        <div class="card-body p-4">
            <form method="POST" class="row g-3">
                <?php echo csrf_field(); ?>
                <div class="col-12 col-md-6">
                    <label for="classe_id" class="form-label fw-semibold">Classe di destinazione *</label>
                    <select name="classe_id" id="classe_id" class="form-select" required>
                        <option value="">-- Seleziona classe --</option>
                        <?php
                        $res = $conn->query("SELECT * FROM classes ORDER BY name ASC");
                        while ($row = $res->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['name']}</option>";
                        }
                        ?>
                    </select>
                    <div class="form-text text-muted">Classe nel tuo database dove importare l'orario</div>
                </div>

                <div class="col-12 col-md-6">
                    <label for="classe_codice" class="form-label fw-semibold">Codice classe sorgente *</label>
                    <input type="text" name="classe_codice" id="classe_codice" 
                           class="form-control" placeholder="es: 1A, 2B, 3BIN..." required>
                    <div class="form-text text-muted">Codice della classe nel sistema esterno</div>
                </div>

                <div class="col-12 mt-4 text-end">
                    <button type="submit" name="import" class="btn btn-warning text-dark fw-bold px-4 py-2">
                        <i class="bi bi-cloud-arrow-down-fill me-1"></i> Importa Orario
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-body-tertiary fw-bold">
            <i class="bi bi-question-circle me-1"></i> Guida all'Importazione e Note Tecniche
        </div>
        <div class="card-body p-4">
            <h5 class="fw-bold text-primary mb-3">Come funziona l'importazione</h5>
            <ol class="mb-4">
                <li>Assicurati che il server Node.js sia avviato (<code>node server.js</code>)</li>
                <li>Seleziona la classe di destinazione nel tuo database</li>
                <li>Inserisci il codice della classe nel sistema esterno (es: 3BIN, 1A, 5AINF)</li>
                <li>Clicca su "Importa Orario"</li>
                <li>Il sistema cancellerà l'orario esistente e importerà i nuovi dati</li>
            </ol>

            <h5 class="fw-bold text-primary mb-3">Note tecniche</h5>
            <ul class="mb-0">
                <li>Ogni slot orario viene inserito UNA SOLA VOLTA nella tabella timetable</li>
                <li>Le informazioni su docenti e aule dall'API vengono estratte ma non salvate (la tabella timetable contiene solo: class_id, day, hour, subject_id)</li>
                <li>Le materie vengono create automaticamente se non esistono già</li>
                <li>Gli slot vuoti nell'orario vengono saltati</li>
            </ul>
        </div>
    </div>
</div>

<footer class="text-center text-body-secondary small mt-5 mb-3">
    Copyright &copy; 2025-<?php echo date("Y"); ?> EmmeV. Rilasciato sotto
    <a href="https://git.vichingo455.com/emmev-code/orario/src/branch/stable/LICENSE.txt" target="_blank" class="fw-bold text-decoration-none">Licenza GNU AGPL 3.0</a>.
    <br>
    Codice sorgente disponibile su <a href="https://git.vichingo455.com/emmev-code/orario" target="_blank" class="fw-bold text-decoration-none">Gitea</a>.
</footer>
<script src="../js/theme.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>