<?php
require_once 'db.php';
require_once 'functions.php';

if (!isLoggedInStudent()) {
    redirect('index.php');
}

$session_id = $_SESSION['session_id'];
$nim = $_SESSION['student_nim'];
$nama = $_SESSION['student_name'];

// Ambil ringkasan
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM questions) AS total,
        COUNT(a.id) AS answered,
        SUM(a.is_correct) AS correct
    FROM answers a 
    WHERE a.session_id = ?
");
$stmt->execute([$session_id]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);
// Ambil detail jawaban
$detailStmt = $pdo->prepare("
    SELECT q.question_text, q.correct_answer, a.selected_answer, a.is_correct 
    FROM answers a 
    JOIN questions q ON a.question_id = q.id 
    WHERE a.session_id = ?
");
$detailStmt->execute([$session_id]);
$details = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil log pelanggaran
$logStmt = $pdo->prepare("SELECT type, timestamp, detail FROM behavior_logs WHERE session_id = ? ORDER BY timestamp");
$logStmt->execute([$session_id]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

// Setelah ditampilkan, hapus session agar tidak bisa akses lagi (mahasiswa hanya lihat sekali)
// Tapi jangan logout admin jika ada, hanya hapus session student.
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hasil Ujian</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>Hasil Ujian</h2>
    <p>Nama: <?= sanitize($nama) ?> (<?= sanitize($nim) ?>)</p>
    <p>Total Soal: <?= $summary['total'] ?></p>
    <p>Dijawab: <?= $summary['answered'] ?></p>
    <p>Benar: <?= $summary['correct'] ?></p>
    <p>Nilai: <?= $summary['total'] > 0 ? round(($summary['correct'] / $summary['total']) * 100, 2) : 0 ?>%</p>

    <h3>Catatan Perilaku</h3>
    <?php if (count($logs) > 0): ?>
        <table>
            <tr><th>Waktu</th><th>Tipe</th><th>Detail</th></tr>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= sanitize($log['timestamp']) ?></td>
                <td><?= sanitize($log['type']) ?></td>
                <td><?= sanitize($log['detail']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Tidak ada pelanggaran.</p>
    <?php endif; ?>
    <a href="index.php">Keluar</a>
</div>
</body>
</html>