<?php require_once __DIR__ . '/../functions.php'; if (!isLoggedInAdmin()) redirect('index.php'); ?>
<!DOCTYPE html>
<html>
<head><title>Dashboard Admin</title><link rel="stylesheet" href="../assets/style.css"></head>
<body>
<div class="container">
    <h2>Dashboard Admin</h2>
    <ul>
        <li><a href="manage_questions.php">Kelola Soal (Upload Excel)</a></li>
        <li><a href="view_results.php">Lihat Hasil Mahasiswa</a></li>
        <li><a href="config.php">Atur Durasi & Batas Pelanggaran</a></li>
        <li><a href="logout.php">Logout</a></li>
    </ul>
</div>
</body>
</html>