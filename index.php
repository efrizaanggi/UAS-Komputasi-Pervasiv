<?php require_once 'functions.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Ujian</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>Masuk Ujian</h2>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert error"><?= sanitize($_GET['error']) ?></div>
    <?php endif; ?>
    <form action="exam.php" method="post">
        <label>NIM:</label>
        <input type="text" name="nim" required>
        <label>Nama Lengkap:</label>
        <input type="text" name="nama" required>
        <button type="submit">Mulai Ujian</button>
    </form>
</div>
</body>
</html>