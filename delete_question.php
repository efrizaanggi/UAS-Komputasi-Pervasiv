<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
if (!isLoggedInAdmin()) redirect('index.php');
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$id]);
}
redirect('manage_questions.php');
?>