<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
if (!isLoggedInAdmin()) redirect('index.php');

$duration = (int)$_POST['duration'];
$max = (int)$_POST['max_violations'];
$afk_timeout = (int)$_POST['afk_timeout'];
$afk_countdown = (int)$_POST['afk_countdown'];

$pdo->prepare("UPDATE config SET exam_duration_minutes = ?, max_violations = ?, afk_timeout_seconds = ?, afk_countdown_seconds = ? WHERE id = 1")
    ->execute([$duration, $max, $afk_timeout, $afk_countdown]);

redirect('config.php?success=1');