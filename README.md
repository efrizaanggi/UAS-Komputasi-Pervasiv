# 📝 Aplikasi Ujian Online Berbasis Web
Aplikasi Ujian Online berbasis PHP Native yang digunakan untuk melaksanakan ujian secara online. Sistem memiliki dua hak akses yaitu **Admin** dan **Mahasiswa**.

# 📌 Fitur Aplikasi
## Admin
- Login Admin
- Dashboard Admin
- Kelola Soal Ujian
- Upload Soal dari File CSV
- Hapus Soal
- Melihat Hasil Ujian Mahasiswa
- Mengatur Durasi Ujian
- Mengatur Batas Pelanggaran
- Mengatur AFK Timeout
- Mengatur Countdown AFK

## Mahasiswa
- Login menggunakan NIM dan Nama
- Mengerjakan Ujian
- Timer Ujian
- Submit Jawaban
- Melihat Nilai Akhir

# 🛠 Teknologi yang Digunakan
- PHP Native
- MySQL
- HTML
- CSS
- JavaScript
- XAMPP
- PhpMyAdmin

# 📂 Struktur Folder
projectefriza/
│
├── admin/
│   ├── dashboard.php
│   ├── manage_questions.php
│   ├── view_results.php
│   ├── config.php
│   ├── index.php
│   └── logout.php
│
├── assets/
│   └── style.css
│
├── db.php
├── config_db.php
├── functions.php
├── exam.php
├── submit_exam.php
├── result.php
├── logout.php
├── soal.csv
└── xlsx_parser.php

# 💻 Software yang Dibutuhkan
- XAMPP
- PHP 8.x
- Apache
- MySQL
- Browser (Google Chrome / Microsoft Edge)

# ⚙ Cara Instalasi
## 1. Install XAMPP
Download XAMPP kemudian install.

## 2. Jalankan XAMPP
Buka XAMPP Control Panel kemudian aktifkan:
- Apache
- MySQL
Status harus berwarna hijau.

## 3. Copy Project
Salin folder
```projectefriza```
ke dalam folder
```C:\xampp\htdocs\```
Sehingga menjadi
```C:\xampp\htdocs\projectefriza```

## 4. Membuat Database
Buka browser
```http://localhost/phpmyadmin```
Klik
```New```
Buat database dengan nama
```exam_db_efriza```
Collation:
```utf8mb4_general_ci```
Klik
```Create```

## 5. Inisialisasi Database
Saat file `db.php` dipanggil untuk pertama kali, sistem akan secara otomatis membuat tabel-tabel yang diperlukan di dalam database apabila tabel tersebut belum tersedia.

Sebelum menjalankan aplikasi, pastikan Anda telah membuat database dengan nama:
```text
exam_db_efriza
```
Pastikan juga nama database tersebut telah sesuai dengan konfigurasi pada file:
```text
config_db.php
```

# ⚙ Konfigurasi Database
File konfigurasi berada pada
```config_db.php```
Konfigurasi default
```php
MYSQL_HOST = localhost
MYSQL_DB = exam_db_efriza
MYSQL_USER = root
MYSQL_PASS = ""
```
Apabila menggunakan password MySQL, ubah bagian
```php
MYSQL_PASS
```
sesuai password MySQL Anda.

# ▶ Menjalankan Aplikasi
Pastikan Apache dan MySQL telah aktif.
Buka browser
```http://localhost/projectefriza```

# 👨‍🎓 Login Mahasiswa
Halaman login:
```http://localhost/projectefriza```
Masukkan:
- NIM
- Nama Lengkap
Klik ```Mulai Ujian```
Mahasiswa tidak menggunakan username maupun password.

# 👨‍💼 Login Admin
Halaman login
```http://localhost/projectefriza/admin```
Masukkan

Username:
```(isi sesuai data pada tabel admins)```

Password:
```(isi sesuai data pada tabel admins)```
Klik```Masuk```

> **Catatan**
> Password admin disimpan dalam bentuk hash sehingga tidak dapat diketahui dari source code. Apabila lupa password, silakan ubah password melalui database atau buat akun admin baru.

# 📖 Cara Penggunaan Mahasiswa
1. Buka halaman utama.
2. Isi
- NIM
- Nama Lengkap
3. Klik
```Mulai Ujian```
4. Kerjakan semua soal.
5. Klik ```Submit
6. Sistem akan menghitung nilai otomatis.
7. Halaman hasil akan ditampilkan```

# 📖 Cara Penggunaan Admin
Setelah login admin berhasil, admin dapat:
## Dashboard
Menampilkan menu utama sistem.
## Kelola Soal
Admin dapat:
- Menambah soal
- Menghapus soal
- Upload soal CSV

## Upload Soal
Siapkan file CSV.
Klik menu
```Kelola Soal```

Upload file CSV.
Soal akan otomatis masuk ke database.

## Lihat Hasil
Admin dapat melihat:
- Nama Mahasiswa
- NIM
- Nilai
- Waktu Ujian

## Pengaturan Ujian
Admin dapat mengubah:
- Durasi ujian
- Maksimal pelanggaran
- Timeout AFK
- Countdown AFK

# 📄 Format Upload Soal
Contoh file
```soal.csv```
Berisi data soal yang akan diimport ke database.

# 🔒 Keamanan
- Session Login Admin
- Password Admin menggunakan Hash
- Validasi Input
- Sanitasi Data

# 🚪 Logout
Admin maupun mahasiswa dapat keluar dari sistem menggunakan tombol Logout.

# 👨‍💻 Developer
Nama :
Efriza Anggi
Program Studi :
Ilmu Komputer
Universitas Muhammadiyah Bangka Belitung

# 📌 Catatan
Pastikan:
- Apache aktif
- MySQL aktif
- Database berhasil diimport
- Folder project berada di dalam htdocs
Apabila salah satu belum dilakukan, aplikasi tidak dapat dijalankan.

# 📷 Demo
Video demo menjelaskan:
1. Menjalankan XAMPP.
2. Membuka PhpMyAdmin.
3. Membuat database.
4. Import database.
5. Meletakkan project ke folder htdocs.
6. Menjalankan aplikasi.
7. Login Admin.
8. Menambah atau upload soal.
9. Login Mahasiswa.
10. Mengerjakan ujian.
11. Submit jawaban.
12. Melihat hasil ujian.
13. Logout.
