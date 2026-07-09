<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
if (!isLoggedInAdmin()) redirect('index.php');
$session_id = (int)($_GET['id'] ?? 0);
if ($session_id > 0) {
    // Hapus jawaban & log (CASCADE di DB), lalu hapus sesi
    $pdo->prepare("DELETE FROM exam_sessions WHERE id = ?")->execute([$session_id]);
    $pdo->prepare("DELETE FROM behavior_logs WHERE id = ?")->execute([$session_id]);
}
redirect('view_results.php');
?>