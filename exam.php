<?php
require_once 'db.php';
require_once 'functions.php';

// Cek ketersedian soal dulu, Ambil semua soal, decode options
$questions = $pdo->query("SELECT id, question_text, options FROM questions")->fetchAll(PDO::FETCH_ASSOC);
shuffle($questions);
$processedQuestions = [];
foreach ($questions as $q) {
    $opts = json_decode($q['options'], true);
    if (!is_array($opts)) continue;
    $shuffled = $opts;
    shuffle($shuffled);
    $q['shuffled_options'] = $shuffled;
    $processedQuestions[] = $q;
}
$totalSoal = count($processedQuestions);

// Proses login (dari index.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($totalSoal === 0) {
        redirect('index.php?error=Belum ada soal. Silakan hubungi admin.');
    }
    $nim = trim($_POST['nim']);
    $nama = trim($_POST['nama']);
    if (empty($nim) || empty($nama)) redirect('index.php?error=NIM dan nama harus diisi');

    $stmt = $pdo->prepare("SELECT id FROM exam_sessions WHERE student_nim = ? AND student_name = ? AND status IN ('in_progress','completed')");
    $stmt->execute([$nim, $nama]);
    if ($stmt->fetch()) redirect('index.php?error=Anda sudah mengikuti ujian');

    $start = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO exam_sessions (student_nim, student_name, start_time, status) VALUES (?, ?, ?, 'in_progress')");
    $stmt->execute([$nim, $nama, $start]);
    $_SESSION['student_nim'] = $nim;
    $_SESSION['student_name'] = $nama;
    $_SESSION['session_id'] = $pdo->lastInsertId();
} elseif (!isLoggedInStudent()) {
    redirect('index.php');
}

// Jika sudah login tetapi soal tiba-tiba kosong (misal dihapus admin setelah login), antisipasi
if ($totalSoal === 0 && isLoggedInStudent()) {
    // Hapus sesi yang baru dibuat
    $pdo->prepare("DELETE FROM exam_sessions WHERE id = ?")->execute([$_SESSION['session_id']]);
    session_destroy();
    redirect('index.php?error=Soal ujian tidak tersedia. Ujian dibatalkan.');
}

// Ambil konfigurasi
$config = $pdo->query("SELECT exam_duration_minutes, max_violations, afk_timeout_seconds, afk_countdown_seconds FROM config WHERE id=1")->fetch(PDO::FETCH_ASSOC);
$duration_minutes = (int)$config['exam_duration_minutes'];
$max_violations = (int)$config['max_violations'];
$afk_timeout_seconds = (int)$config['afk_timeout_seconds'];
$afk_countdown_seconds = (int)$config['afk_countdown_seconds'];

// Sesi
$stmt = $pdo->prepare("SELECT start_time FROM exam_sessions WHERE id=?");
$stmt->execute([$_SESSION['session_id']]);
$start_time = strtotime($stmt->fetchColumn());
$end_time = $start_time + ($duration_minutes * 60);
$remaining = max(0, $end_time - time());
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ujian</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .question-slide {
            display: none;
        }
        .question-slide.active {
            display: block;
        }
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .nav-buttons button {
            min-width: 100px;
        }
        .question-numbers {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin: 15px 0;
        }
        .question-numbers span {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            font-size: 0.9rem;
        }
        .question-numbers span.answered {
            background: #06d6a0;
            color: white;
        }
        .question-numbers span.active {
            background: var(--primary);
            color: white;
            transform: scale(1.2);
            box-shadow: 0 0 10px rgba(67,97,238,0.5);
        }
        .exam-info {
            display: flex;
            justify-content: space-between;
            background: #f0f4ff;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            font-weight: 500;
        }
        .battery { color: #2d6a4f; }
        .clock { color: #1b4332; }
        .modal-overlay {
            position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.6); display:none;
            justify-content:center; align-items:center; z-index:9999;
        }
        .modal-box {
            background: white; padding: 30px; border-radius: 16px;
            text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            max-width: 400px; width: 90%;
        }
        .modal-box h3 { margin-top:0; color:#d90429; }
        .afk-countdown { font-weight: bold; color: var(--danger); }
    </style>
</head>
<body>
<div class="container">
    <h2>Ujian - <?= sanitize($_SESSION['student_name']) ?> (<?= sanitize($_SESSION['student_nim']) ?>)</h2>

    <!-- Info Baterai & Jam -->
    <div class="exam-info">
        <div class="battery">🔋 Baterai: <span id="batteryLevel">--</span>%</div>
        <div class="clock">🕒 <span id="realTime">--:--:--</span></div>
    </div>

    <!-- Timer & Peringatan -->
    <div class="exam-meta">
        <div class="timer" id="timerDisplay">Sisa waktu: <span id="time">--</span></div>
    </div>

    <div class="violation-warning" id="violationWarning">
        ⚠️ Peringatan pelanggaran <span id="violationCount">0</span>/<?= $max_violations ?>
    </div>

    <!-- Navigasi Nomor Soal -->
    <div class="question-numbers" id="questionNumbers"></div>

    <!-- Form Ujian (semua soal, hanya satu terlihat) -->
    <form id="examForm" method="post" action="submit_exam.php">
        <?php foreach ($processedQuestions as $i => $q): ?>
        <div class="question-slide" data-index="<?= $i ?>">
            <p><strong><?= ($i+1) ?>. <?= sanitize($q['question_text']) ?></strong></p>
            <?php foreach ($q['shuffled_options'] as $optText): ?>
                <label>
                    <input type="radio" name="q<?= $q['id'] ?>" value="<?= htmlspecialchars($optText, ENT_QUOTES) ?>">
                    <?= sanitize($optText) ?>
                </label><br>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </form>

    <div class="nav-buttons">
        <button id="prevBtn" disabled>◀ Sebelumnya</button>
        <button id="nextBtn">Selanjutnya ▶</button>
    </div>
    <div style="text-align:right; margin-top:15px;">
        <button type="button" id="finishBtn">Selesaikan Ujian</button>
    </div>
</div>

<!-- Modal AFK -->
<div class="modal-overlay" id="afkModal">
    <div class="modal-box">
        <h3>⏸️ Anda terdeteksi tidak aktif (AFK)</h3>
        <p>Ujian dijeda sementara. Silakan klik tombol di bawah untuk melanjutkan.</p>
        <p class="afk-countdown" id="afkCountdown">Otomatis melanjutkan dalam 10 detik...</p>
        <button id="resumeBtn">Saya Kembali</button>
    </div>
</div>

<script>
// ====================== KONFIGURASI ======================
const MAX_VIOLATIONS = <?= $max_violations ?>;
const SESSION_ID = <?= $_SESSION['session_id'] ?>;
const AFK_TIMEOUT = <?= $afk_timeout_seconds ?>;
const AFK_COUNTDOWN = <?= $afk_countdown_seconds ?>;
const totalQuestions = <?= $totalSoal ?>;
let timeLeft = <?= $remaining ?>;
let timerInterval = null;
let violationCount = 0;
let currentIndex = 0;

// State AFK
let isAFK = false;
let idleSeconds = 0;
let afkTimerInterval = null;
let afkCountdownInterval = null;

// DOM Elements
const timeDisplay = document.getElementById('time');
const timerDisplay = document.getElementById('timerDisplay');
const violationWarning = document.getElementById('violationWarning');
const violationSpan = document.getElementById('violationCount');
const afkModal = document.getElementById('afkModal');
const afkCountdownSpan = document.getElementById('afkCountdown');
const resumeBtn = document.getElementById('resumeBtn');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const finishBtn = document.getElementById('finishBtn');
const questionNumbersDiv = document.getElementById('questionNumbers');
const slides = document.querySelectorAll('.question-slide');

// ====================== TIMER ======================
function formatTime(seconds) {
    let m = Math.floor(seconds / 60);
    let s = seconds % 60;
    return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
}

function updateTimerDisplay() {
    timeDisplay.textContent = formatTime(timeLeft);
    if (timeLeft <= 60) {
        timerDisplay.classList.add('warning');
    } else {
        timerDisplay.classList.remove('warning');
    }
    if (timeLeft <= 0) {
        clearInterval(timerInterval);
        finishExam();
    }
}

function startTimer() {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (!isAFK) {
            timeLeft--;
            updateTimerDisplay();
        }
    }, 1000);
}

// ====================== BATERAI & JAM ======================
function updateBattery(battery) {
    let level = Math.round(battery.level * 100);
    document.getElementById('batteryLevel').textContent = level;
}
if ('getBattery' in navigator) {
    navigator.getBattery().then(battery => {
        updateBattery(battery);
        battery.addEventListener('levelchange', () => updateBattery(battery));
    });
} else {
    document.getElementById('batteryLevel').textContent = 'N/A';
}

function updateClock() {
    let now = new Date();
    let timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
    document.getElementById('realTime').textContent = timeStr;
}
setInterval(updateClock, 1000);
updateClock();

// ====================== NAVIGASI ======================
function showSlide(index) {
    if (slides.length === 0) return;
    slides.forEach(s => s.classList.remove('active'));
    slides[index].classList.add('active');
    currentIndex = index;
    updateNavButtons();
    updateQuestionNumbers();
    // Fokuskan radio yang mungkin sudah dipilih agar ter-highlight
    let savedAnswers = JSON.parse(localStorage.getItem('exam_answers') || '{}');
    let questionId = getQuestionId(index);
    if (savedAnswers['q'+questionId]) {
        let radio = document.querySelector(`input[name="q${questionId}"][value="${CSS.escape(savedAnswers['q'+questionId])}"]`);
        if (radio) radio.checked = true;
    }
}

function getQuestionId(index) {
    // Ambil id soal dari name radio di slide
    let slide = slides[index];
    let radio = slide.querySelector('input[type="radio"]');
    if (radio) {
        return radio.name.replace('q', '');
    }
    return null;
}

function prevSlide() {
    if (currentIndex > 0) showSlide(currentIndex - 1);
}

function nextSlide() {
    if (currentIndex < totalQuestions - 1) showSlide(currentIndex + 1);
}

function updateNavButtons() {
    prevBtn.disabled = currentIndex === 0;
    nextBtn.disabled = currentIndex === totalQuestions - 1;
}

// Buat navigasi nomor soal
function createQuestionNumbers() {
    for (let i = 0; i < totalQuestions; i++) {
        let span = document.createElement('span');
        span.textContent = i + 1;
        span.addEventListener('click', () => showSlide(i));
        questionNumbersDiv.appendChild(span);
    }
    updateQuestionNumbers();
}

function updateQuestionNumbers() {
    let savedAnswers = JSON.parse(localStorage.getItem('exam_answers') || '{}');
    let spans = questionNumbersDiv.querySelectorAll('span');
    spans.forEach((span, i) => {
        span.classList.remove('active', 'answered');
        if (i === currentIndex) span.classList.add('active');
        let qid = getQuestionId(i);
        if (savedAnswers['q'+qid]) span.classList.add('answered');
    });
}

// ====================== SIMPAN JAWABAN ======================
document.addEventListener('change', function(e) {
    if (e.target.type === 'radio') {
        let answers = JSON.parse(localStorage.getItem('exam_answers') || '{}');
        answers[e.target.name] = e.target.value;
        localStorage.setItem('exam_answers', JSON.stringify(answers));
        updateQuestionNumbers();
    }
});

// ====================== PELANGGARAN & LOG ======================
function logViolation(type, detail) {
    fetch('submit_exam.php?log=1', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `session_id=${SESSION_ID}&type=${encodeURIComponent(type)}&detail=${encodeURIComponent(detail)}`
    });
}

function addViolation(reason) {
    violationCount++;
    violationSpan.textContent = violationCount;
    violationWarning.style.display = 'block';
    logViolation('afk_timeout', reason);
    if (violationCount >= MAX_VIOLATIONS) {
        alert('Terlalu banyak pelanggaran. Ujian dihentikan.');
        finishExam();
    }
}

// ====================== AFK ======================
function freezeExam() {
    if (isAFK) return;
    isAFK = true;
    afkModal.style.display = 'flex';
    if (AFK_COUNTDOWN > 0) {
        let countdown = AFK_COUNTDOWN;
        afkCountdownSpan.textContent = `Otomatis melanjutkan dalam ${countdown} detik...`;
        afkCountdownInterval = setInterval(() => {
            countdown--;
            afkCountdownSpan.textContent = `Otomatis melanjutkan dalam ${countdown} detik...`;
            if (countdown <= 0) {
                clearInterval(afkCountdownInterval);
                addViolation('Tidak merespon AFK');
                resumeExam();
            }
        }, 1000);
    } else {
        afkCountdownSpan.textContent = 'Ujian dijeda. Klik tombol untuk melanjutkan.';
    }
}

function resumeExam() {
    if (!isAFK) return;
    isAFK = false;
    afkModal.style.display = 'none';
    if (afkCountdownInterval) clearInterval(afkCountdownInterval);
    idleSeconds = 0;
}

resumeBtn.addEventListener('click', () => {
    if (AFK_COUNTDOWN === 0) {
        addViolation('AFK (diresume manual)');
    }
    resumeExam();
});

function resetIdle() {
    idleSeconds = 0;
    if (isAFK) {
        if (AFK_COUNTDOWN > 0) {
            resumeExam(); // kembali sebelum countdown habis, tidak ada pelanggaran
        } else {
            addViolation('AFK (diresume dengan aktivitas)');
            resumeExam();
        }
    }
}

['mousemove', 'keydown', 'scroll', 'click', 'touchstart'].forEach(ev => {
    window.addEventListener(ev, resetIdle);
});

afkTimerInterval = setInterval(() => {
    if (!isAFK) {
        idleSeconds++;
        if (idleSeconds >= AFK_TIMEOUT) {
            freezeExam();
        }
    }
}, 1000);

// Tab Visibility Monitor dengan validasi tunda & fokus
let tabHiddenTimer = null;

document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        // Tunda 500 ms, jika masih hidden dan tidak fokus, baru dianggap pelanggaran
        clearTimeout(tabHiddenTimer);
        tabHiddenTimer = setTimeout(() => {
            if (document.hidden && !document.hasFocus()) {
                violationCount++;
                violationSpan.textContent = violationCount;
                violationWarning.style.display = 'block';
                logViolation('tab_hidden', 'Pindah tab');
                if (violationCount >= MAX_VIOLATIONS) {
                    alert('Terlalu banyak pelanggaran. Ujian dihentikan.');
                    finishExam();
                }
            }
        }, 500);
    } else {
        // Tab kembali terlihat, batalkan timer jika ada
        clearTimeout(tabHiddenTimer);
    }
});

// ====================== FINISH EXAM ======================
function finishExam() {
    // Kumpulkan semua jawaban dari localStorage ke form (hidden input)
    let answers = JSON.parse(localStorage.getItem('exam_answers') || '{}');
    for (let [name, value] of Object.entries(answers)) {
        // Pastikan input hidden ada di form
        let hidden = document.querySelector(`input[type="hidden"][name="${name}"]`);
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            document.getElementById('examForm').appendChild(hidden);
        }
        hidden.value = value;
    }
    localStorage.removeItem('exam_answers');
    document.getElementById('examForm').submit();
}

finishBtn.addEventListener('click', () => {
    if (confirm('Apakah Anda yakin ingin menyelesaikan ujian?')) {
        finishExam();
    }
});

prevBtn.addEventListener('click', prevSlide);
nextBtn.addEventListener('click', nextSlide);

// Inisialisasi
if (slides.length > 0) {
    createQuestionNumbers();
    showSlide(0);
    startTimer();
    updateTimerDisplay();
} else {
    // Tampilkan pesan di halaman
    document.body.innerHTML = '<div class="container"><h2>Tidak ada soal tersedia</h2></div>';
}
</script>
</body>
</html>