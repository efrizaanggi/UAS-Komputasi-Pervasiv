<?php
// functions.php
session_start();

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedInStudent() {
    return isset($_SESSION['student_nim']) && isset($_SESSION['student_name']) && isset($_SESSION['session_id']);
}

function isLoggedInAdmin() {
    return isset($_SESSION['admin_id']);
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>