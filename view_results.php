<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
if (!isLoggedInAdmin()) redirect('index.php');

// Ambil semua sesi yang completed/terminated
$stmt = $pdo->query("
    SELECT es.id, es.student_nim, es.student_name, es.status, 
           es.start_time, es.end_time,
           (SELECT COUNT(*) FROM questions) AS total_questions,
           (SELECT COUNT(*) FROM answers WHERE session_id = es.id) AS answered,
           (SELECT SUM(is_correct) FROM answers WHERE session_id = es.id) AS correct,
           (SELECT COUNT(*) FROM behavior_logs WHERE session_id = es.id) AS violations
    FROM exam_sessions es
    WHERE es.status IN ('completed', 'terminated', 'in_progress')
    ORDER BY es.start_time DESC
");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>Hasil Ujian</title><link rel="stylesheet" href="../assets/style.css"></head>
<body>
<div class="container">
    <h2>Hasil Ujian Mahasiswa</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>NIM</th><th>Nama</th><th>Status</th><th>Soal Terjawab</th><th>Benar</th><th>Nilai (%)</th><th>Pelanggaran</th><th>Aksi</th>
        </tr>
        <?php foreach ($sessions as $s): 
            $score = $s['total_questions'] > 0 ? round(($s['correct'] / $s['total_questions']) * 100, 2) : 0;
        ?>
        <tr>
            <td><?= sanitize($s['student_nim']) ?></td>
            <td><?= sanitize($s['student_name']) ?></td>
            <td><?= $s['status'] ?></td>
            <td><?= $s['answered'] ?>/<?= $s['total_questions'] ?></td>
            <td><?= $s['correct'] ?></td>
            <td><?= $score ?>%</td>
            <td><?= $s['violations'] ?></td>
            <td>
                <a href="view_results.php?session_id=<?= $s['id'] ?>">Detail</a> |
                <a href="delete_student.php?id=<?= $s['id'] ?>" onclick="return confirm('Hapus data mahasiswa ini?')">Hapus</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <?php
    // Detail satu sesi
    if (isset($_GET['session_id'])) {
        $sid = (int)$_GET['session_id'];
        // Ambil log perilaku (sama)
        $logStmt = $pdo->prepare("SELECT type, timestamp, detail FROM behavior_logs WHERE session_id = ? ORDER BY timestamp");
        $logStmt->execute([$sid]);
        $logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
        // Ambil jawaban detail
        $ansStmt = $pdo->prepare("
            SELECT q.question_text, q.correct_answer, a.selected_answer, a.is_correct
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.session_id = ?
        ");
        $ansStmt->execute([$sid]);
        $answersDetail = $ansStmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Detail Jawaban</h3>";
        echo "<table><tr><th>Soal</th><th>Jawaban Benar</th><th>Jawaban Mahasiswa</th><th>Status</th></tr>";
        foreach ($answersDetail as $ans) {
            $status = $ans['is_correct'] ? '✅ Benar' : '❌ Salah';
            echo "<tr>
                <td>".sanitize($ans['question_text'])."</td>
                <td>".sanitize($ans['correct_answer'])."</td>
                <td>".sanitize($ans['selected_answer'] ?? '-')."</td>
                <td>$status</td>
            </tr>";
        }
        echo "</table>";
        
        echo "<h3>Catatan Perilaku</h3>";
        if (count($logs) > 0) {
            echo "<table>
                <tr><th>Waktu</th><th>Tipe</th><th>Detail</th></tr>";
            foreach ($logs as $log){
                $t = sanitize($log['timestamp']);
                $type = sanitize($log['type']);
                $d = sanitize($log['detail']);
                echo "<tr>
                    <td>$t</td>
                    <td>$type</td>
                    <td>$d</td>
                </tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Tidak ada pelanggaran.</p>";
        }
    }
    ?>
    <a href="dashboard.php">Kembali</a>
</div>
</body>
</html>