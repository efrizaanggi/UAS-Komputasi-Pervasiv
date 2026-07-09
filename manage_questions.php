<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../xlsx_parser.php';
if (!isLoggedInAdmin()) redirect('index.php');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $tmpPath = $file['tmp_name'];
        $inserted = 0;

        if ($ext === 'csv') {
            if (($handle = fopen($tmpPath, 'r')) !== false) {
                // Baca header untuk abaikan
                fgetcsv($handle);
                $stmt = $pdo->prepare("INSERT INTO questions (question_text, options, correct_answer) VALUES (?, ?, ?)");
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 3) continue; // minimal: question, 1 opsi, correct
                    $question = trim($row[0]);
                    $correct  = trim(end($row)); // elemen terakhir
                    // Opsi adalah dari indeks 1 sampai sebelum terakhir, filter kosong
                    $options = [];
                    for ($i = 1; $i < count($row) - 1; $i++) {
                        $opt = trim($row[$i]);
                        if ($opt !== '') $options[] = $opt;
                    }
                    if (empty($question) || empty($correct) || empty($options)) continue;
                    if (!in_array($correct, $options, true)) continue;
                    $jsonOptions = json_encode($options, JSON_UNESCAPED_UNICODE);
                    $stmt->execute([$question, $jsonOptions, $correct]);
                    $inserted++;
                }
                fclose($handle);
                $message = "$inserted soal berhasil diimpor dari CSV.";
            }
        } elseif ($ext === 'xlsx') {
            $data = parseXlsxFlexible($tmpPath);
            if ($data !== false && count($data) > 0) {
                $stmt = $pdo->prepare("INSERT INTO questions (question_text, options, correct_answer) VALUES (?, ?, ?)");
                foreach ($data as $row) {
                    $stmt->execute([
                        $row['question'],
                        json_encode($row['options'], JSON_UNESCAPED_UNICODE),
                        $row['correct']
                    ]);
                    $inserted++;
                }
                $message = "$inserted soal berhasil diimpor dari Excel.";
            } else {
                $message = "Gagal membaca file Excel. Pastikan format: kolom pertama 'question', kolom terakhir 'correct', sisanya opsi.";
            }
        } else {
            $message = "Format file tidak didukung (hanya .csv atau .xlsx).";
        }
    } else {
        $message = "Upload gagal.";
    }
}

// Ambil semua soal
$questions = $pdo->query("SELECT * FROM questions ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>Kelola Soal</title><link rel="stylesheet" href="../assets/style.css"></head>
<body>
<div class="container">
    <h2>Kelola Soal (Opsi Dinamis)</h2>
    <?php if ($message): ?><div class="alert"><?= sanitize($message) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label>Upload Soal (CSV/Excel):</label>
        <input type="file" name="file" accept=".csv,.xlsx" required>
        <p><small>Format: kolom pertama <strong>question</strong>, kolom terakhir <strong>correct</strong>, di antaranya <strong>opsi-opsi</strong> (jumlah bebas, kosong diabaikan).</small></p>
        <button type="submit">Upload</button>
    </form>

    <h3>Daftar Soal (<?= count($questions) ?>)</h3>
    <table border="1" cellpadding="5">
        <tr><th>ID</th><th>Pertanyaan</th><th>Opsi</th><th>Jawaban Benar</th><th>Aksi</th></tr>
        <?php foreach ($questions as $index => $q): 
            $opts = json_decode($q['options'], true);
        ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td><?= sanitize($q['question_text']) ?></td>
            <td><small><?= implode(' | ', $opts) ?></small></td>
            <td><strong><?= sanitize($q['correct_answer']) ?></strong></td>
            <td><a href="delete_question.php?id=<?= $q['id'] ?>" onclick="return confirm('Hapus?')">Hapus</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a href="dashboard.php">Kembali</a>
</div>
</body>
</html>