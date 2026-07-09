<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
if (!isLoggedInAdmin()) redirect('index.php');
$config = $pdo->query("SELECT * FROM config WHERE id=1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>Konfigurasi Ujian</title><link rel="stylesheet" href="../assets/style.css"></head>
<body>
<div class="container">
    <h2>Konfigurasi Ujian</h2>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert">Konfigurasi berhasil disimpan.</div>
    <?php endif; ?>
    <form method="post" action="save_config.php">
        <label>Durasi Ujian (menit):</label>
        <input type="number" name="duration" value="<?= $config['exam_duration_minutes'] ?>" min="1" required>

        <label>Batas Maksimal Pelanggaran:</label>
        <input type="number" name="max_violations" value="<?= $config['max_violations'] ?>" min="1" required>

        <label>AFK Timeout (detik, setelah tidak aktif):</label>
        <input type="number" name="afk_timeout" value="<?= $config['afk_timeout_seconds'] ?>" min="1" required>

        <label>AFK Countdown (detik, 0 = tanpa batas):</label>
        <input type="number" name="afk_countdown" value="<?= $config['afk_countdown_seconds'] ?>" min="0" required>
        <p><small>Jika 0, ujian dijeda sampai mahasiswa klik tombol kembali (tidak ada auto-lanjut).</small></p>

        <button type="submit">Simpan</button>
    </form>
    <a href="dashboard.php">Kembali</a>
</div>
</body>
</html>