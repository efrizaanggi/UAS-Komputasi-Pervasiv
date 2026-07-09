<?php
// db.php
$dbFile = __DIR__ . '/exam.db';
$pdo = new PDO("sqlite:$dbFile");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("PRAGMA foreign_keys = ON");

// Buat tabel jika belum ada
$pdo->exec("CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS config (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    exam_duration_minutes INTEGER NOT NULL DEFAULT 60,
    max_violations INTEGER NOT NULL DEFAULT 3
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_text TEXT NOT NULL,
    options TEXT NOT NULL,
    correct_answer TEXT NOT NULL
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

$pdo->exec("CREATE TABLE IF NOT EXISTS exam_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_nim TEXT NOT NULL,
    student_name TEXT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME,
    status TEXT NOT NULL DEFAULT 'in_progress' CHECK (status IN ('in_progress','completed','terminated'))
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS behavior_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    detail TEXT,
    FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE
)");

// Insert default config jika belum ada
$stmt = $pdo->query("SELECT COUNT(*) FROM config");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO config (id, exam_duration_minutes, max_violations) VALUES (1, 60, 3)");
}

// Insert admin default jika belum ada (username: admin, password: admin123)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
$stmt->execute(['admin']);
if ($stmt->fetchColumn() == 0) {
    $hashed = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)")->execute(['admin', $hashed]);
}
?>