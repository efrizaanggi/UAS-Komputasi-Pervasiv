<?php
// config_db.php
// Pilih tipe database: 'sqlite' atau 'mysql'
define('DB_TYPE', 'mysql');

// Konfigurasi SQLite (hanya digunakan jika DB_TYPE = 'sqlite')
define('SQLITE_PATH', __DIR__ . '/exam.db');

// Konfigurasi MySQL (hanya digunakan jika DB_TYPE = 'mysql')
define('MYSQL_HOST', 'localhost');
define('MYSQL_DB',   'exam_db_efriza');
define('MYSQL_USER', 'root');
define('MYSQL_PASS', '');
define('MYSQL_CHARSET', 'utf8mb4');
?>