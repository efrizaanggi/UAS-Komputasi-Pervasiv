<?php
require_once 'db.php';
require_once 'functions.php';

// Endpoint untuk mencatat log pelanggaran (AJAX)
if (isset($_GET['log']) && $_GET['log'] == '1') {
    $session_id = (int)$_POST['session_id'];
    $type = $_POST['type'];
    $detail = $_POST['detail'];
    $stmt = $pdo->prepare("INSERT INTO behavior_logs (session_id, type, detail) VALUES (?, ?, ?)");
    $stmt->execute([$session_id, $type, $detail]);
    exit;
}

// Proses submit ujian
if (!isLoggedInStudent()) {
    redirect('index.php');
}

$session_id = $_SESSION['session_id'];
$stmt = $pdo->prepare("SELECT status FROM exam_sessions WHERE id=?");
$stmt->execute([$session_id]);
if ($stmt->fetchColumn() !== 'in_progress') redirect('result.php');

// Ambil kunci jawaban asli
$stmt = $pdo->query("SELECT id, correct_answer FROM questions");
$answersKey = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $answersKey[$row['id']] = $row['correct_answer'];
}

$insertStmt = $pdo->prepare("INSERT INTO answers (session_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
foreach ($answersKey as $qid => $correct) {
    $selected = $_POST['q'.$qid] ?? null; // teks dari radio
    $isCorrect = 0;
    if ($selected !== null) {
        // Perbandingan case-insensitive dan trim
        if (strcasecmp(trim($selected), trim($correct)) === 0) {
            $isCorrect = 1;
        }
    }
    $insertStmt->execute([$session_id, $qid, $selected, $isCorrect]);
}

// Update sesi selesai
$now = date('Y-m-d H:i:s');
$pdo->prepare("UPDATE exam_sessions SET end_time = ?, status = 'completed' WHERE id = ?")->execute([$now, $session_id]);
redirect('result.php');
?>