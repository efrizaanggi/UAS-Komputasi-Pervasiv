<?php
// db.php
require_once __DIR__ . '/config_db.php';

try {
    if (DB_TYPE === 'mysql') {
        $dsn = "mysql:host=" . MYSQL_HOST . ";dbname=" . MYSQL_DB . ";charset=" . MYSQL_CHARSET;
        $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'"
        ]);
    } else {
        // Default SQLite
        $pdo = new PDO("sqlite:" . SQLITE_PATH);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if (DB_TYPE === 'mysql') {
        $pdo->exec("SET foreign_key_checks = 0");
    } else {
        $pdo->exec("PRAGMA foreign_keys = ON");
    }

    // Skema tabel disesuaikan dengan driver
    if (DB_TYPE === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS config (
            id INT PRIMARY KEY CHECK (id = 1),
            exam_duration_minutes INT NOT NULL DEFAULT 60,
            max_violations INT NOT NULL DEFAULT 3,
            afk_timeout_seconds INT NOT NULL DEFAULT 15,
            afk_countdown_seconds INT NOT NULL DEFAULT 10
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question_text TEXT NOT NULL,
            options JSON NOT NULL,
            correct_answer TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS exam_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_nim VARCHAR(50) NOT NULL,
            student_name VARCHAR(100) NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME,
            status ENUM('in_progress','completed','terminated') NOT NULL DEFAULT 'in_progress'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            question_id INT NOT NULL,
            selected_answer TEXT,
            is_correct TINYINT DEFAULT 0,
            FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES questions(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS behavior_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            detail TEXT,
            FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } else {
        // SQLite
        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS config (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            exam_duration_minutes INTEGER NOT NULL DEFAULT 60,
            max_violations INTEGER NOT NULL DEFAULT 3,
            afk_timeout_seconds INTEGER NOT NULL DEFAULT 15,
            afk_countdown_seconds INTEGER NOT NULL DEFAULT 10
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            question_text TEXT NOT NULL,
            options TEXT NOT NULL,
            correct_answer TEXT NOT NULL
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS exam_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_nim TEXT NOT NULL,
            student_name TEXT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME,
            status TEXT NOT NULL DEFAULT 'in_progress' CHECK (status IN ('in_progress','completed','terminated'))
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS answers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id INTEGER NOT NULL,
            question_id INTEGER NOT NULL,
            selected_answer TEXT,
            is_correct INTEGER DEFAULT 0,
            FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES questions(id)
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS behavior_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            detail TEXT,
            FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE
        )");
    }

    // Insert default config jika belum ada
    $stmt = $pdo->query("SELECT COUNT(*) FROM config");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO config (id, exam_duration_minutes, max_violations, afk_timeout_seconds, afk_countdown_seconds) 
            VALUES (1, 60, 3, 15, 10)");
    }

    // Insert admin default
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $hashed = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)")->execute(['admin', $hashed]);
    }
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>