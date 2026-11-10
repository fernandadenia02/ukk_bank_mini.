# Aplikasi Bank Mini

Aplikasi web Bank Mini untuk mendukung kegiatan operasional bank mini sekolah: pengelolaan petugas dan nasabah, pembukaan rekening, transaksi, pencatatan harian, serta laporan. Aplikasi dibuat menggunakan Laravel dan menyediakan antarmuka web berbasis Blade serta API untuk kebutuhan nasabah.

## Fitur aplikasi

### Admin

- Melihat dashboard admin.
- Mengelola akun petugas dengan peran admin, teller, dan supervisor; mengaktifkan atau menonaktifkan akun.
- Mengelola data nasabah.
- Mengelola data anggota, mengimpor anggota dari berkas, dan mempromosikan anggota.
- Menyetujui atau menolak permintaan uang kas awal dari teller.
- Meninjau, menyetujui, atau menolak laporan harian teller.
- Melihat jurnal akuntansi dan laporan bulanan, serta mengunduh laporan bulanan dalam PDF.

### Teller

- Melayani transaksi setoran dan penarikan serta mencetak bukti transaksi.
- Memproses anggota menjadi nasabah dan membuka rekening.
- Mengajukan kas awal dan menutup laporan harian.
- Meninjau dan memproses janji penarikan tunai nasabah.
- Mengubah status operasional bank.
- Mengirim OTP untuk pemulihan PIN nasabah dan melihat status permintaan persetujuan PIN transaksi.

### Supervisor

- Melihat dashboard supervisor.
- Melihat laporan harian teller dan detail laporan.

### Nasabah

- Melihat dashboard dan saldo.
- Melihat barcode rekening dan riwayat transaksi.
- Mengelola profil melalui API.
- Memeriksa status bank, mengajukan janji penarikan, dan meminta persetujuan PIN penarikan melalui API.
- Memulihkan PIN dan kata sandi dengan OTP.

## Teknologi

- **Backend:** PHP 8.3 atau lebih baru, Laravel 13.
- **Database:** SQLite sebagai konfigurasi awal; konfigurasi Laravel juga menyediakan koneksi MySQL/MariaDB.
- **Frontend:** Blade, Vite 8, Tailwind CSS 4, dan JavaScript.
- **API:** Laravel Sanctum.
- **Pembuatan dokumen:** DomPDF untuk laporan PDF, PhpSpreadsheet untuk lembar kerja, dan Simple QrCode untuk barcode/QR.

## Struktur proyek

```text
app/
  Console/Commands/       Perintah Artisan aplikasi
  Http/
    Controllers/          Controller halaman web dan API
    Middleware/           Middleware, termasuk pembatasan akses berdasarkan peran
  Models/                 Model pengguna, nasabah, transaksi, laporan, dan data terkait
  Services/               Layanan bersama, misalnya pengelolaan lockout kredensial
database/
  factories/              Factory data untuk pengujian
  migrations/             Perubahan struktur database
  seeders/                Seeder aplikasi
public/                   Entry point dan aset publik/build
resources/
  css/                    Sumber stylesheet
  js/                     Sumber JavaScript
  views/                  Halaman Blade dan layout per peran
routes/
  web.php                 Halaman web dan tindakan berdasarkan peran
  api.php                 Endpoint API nasabah
tests/
  Feature/                Pengujian alur fitur dan endpoint
  Unit/                   Pengujian unit
```

### Bagian utama aplikasi

- **Autentikasi dan akses:** login web untuk petugas/nasabah, logout, reset kata sandi, pemulihan OTP, status akun, dan middleware peran.
- **Operasional teller:** transaksi, bukti transaksi, kas awal, laporan harian, pembukaan rekening, janji penarikan, dan status bank.
- **Administrasi dan laporan:** data pengguna, nasabah, anggota, jurnal akuntansi, persetujuan laporan, serta laporan bulanan PDF.
- **API nasabah:** autentikasi token Sanctum, profil, dashboard, saldo, barcode, transaksi, status bank, janji penarikan, persetujuan PIN, dan pemulihan PIN.

Model utama yang digunakan aplikasi mencakup `User`, `Customer`, `Member`, `Transaction`, `DailyReport`, `AccountingJournal`, `CashAdvanceRequest`, `BankStatus`, `WithdrawalAppointment`, dan `WithdrawalPinApproval`. Model OTP digunakan untuk proses pemulihan kredensial.

## Persyaratan lokal

Pastikan perangkat sudah memiliki:

1. **PHP 8.3+** dan ekstensi PHP yang dibutuhkan Laravel serta dependensi Composer. Untuk konfigurasi SQLite bawaan, aktifkan ekstensi `pdo_sqlite` dan `sqlite3`.
2. **Composer 2**.
3. **Node.js dan npm** yang kompatibel dengan Vite 8.
4. **Git**, jika proyek akan diambil dari repositori.

Di Windows, Laragon dapat digunakan untuk menyediakan PHP, Node.js, dan terminal. Periksa versi yang aktif di terminal:

```powershell
php -v
composer --version
node --version
npm --version
```

## Instalasi lokal (Windows/Laragon)

Jalankan perintah berikut dari PowerShell atau terminal Laragon. Sesuaikan lokasi folder jika proyek berada di lokasi berbeda.

### 1. Ambil proyek dan pasang dependensi PHP

Jika proyek belum ada di komputer:

```powershell
cd C:\laragon\www
git clone <URL-REPOSITORI> ukk_bank_mini
cd C:\laragon\www\ukk_bank_mini
```

Jika folder proyek sudah tersedia, langsung masuk ke folder tersebut. Kemudian pasang dependensi dari lock file:

```powershell
composer install
```

### 2. Siapkan konfigurasi aplikasi

Buat file `.env` dari contoh apabila belum tersedia, lalu buat kunci aplikasi:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
```

Pengaturan `.env.example` menggunakan SQLite. Pastikan baris ini tetap aktif:

```dotenv
DB_CONNECTION=sqlite
```

Pada konfigurasi bawaan, Laravel memakai `database/database.sqlite`. Buat file tersebut hanya jika belum ada:

```powershell
if (-not (Test-Path database\database.sqlite)) {
    New-Item -ItemType File database\database.sqlite | Out-Null
}
```

> Jika ingin memakai MySQL/MariaDB, buat database terlebih dahulu lalu sesuaikan `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env`. Pastikan ekstensi `pdo_mysql` aktif.

### 3. Siapkan database

Jalankan migrasi untuk membuat atau memperbarui tabel:

```powershell
php artisan migrate
```

Jangan gunakan `php artisan migrate:fresh` pada database yang berisi data penting karena perintah tersebut menghapus tabel dan data. Seeder bawaan tidak membuat akun operasional admin/teller/supervisor/nasabah yang siap dipakai; gunakan akun yang disediakan pengelola proyek atau siapkan akun sesuai kebijakan aplikasi. Jangan menganggap akun contoh sebagai akun operasional.

### 4. Pasang dependensi frontend dan buat aset

```powershell
npm ci
npm run build
```

`npm ci` memasang versi dependensi yang tercatat di `package-lock.json`. Jalankan `npm install` jika lock file tidak tersedia atau memang perlu diperbarui.

### 5. Jalankan aplikasi

Untuk pengembangan, jalankan:

```powershell
composer run dev
```

Perintah ini menjalankan server Laravel, worker antrean, log aplikasi, dan Vite secara bersamaan. Buka alamat yang ditampilkan oleh server—biasanya **http://127.0.0.1:8000**.

Alternatifnya, jalankan server saja:

```powershell
php artisan serve
```

Dalam terminal terpisah, jalankan Vite agar perubahan CSS/JavaScript dimuat langsung:

```powershell
npm run dev
```

Halaman login tersedia di `/login`, sedangkan dokumentasi API dalam aplikasi tersedia di `/api-docs`.

## Konfigurasi lokal tambahan

- `.env.example` mengatur `MAIL_MAILER=log`; email/OTP yang dikirim melalui mailer Laravel pada pengembangan lokal dicatat ke log aplikasi, bukan dikirim ke alamat email sungguhan. Periksa `storage/logs/laravel.log`.
- `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database`, dan `CACHE_STORE=database` merupakan nilai bawaan. Pastikan migrasi telah dijalankan sebelum menggunakan fitur yang bergantung pada tabel terkait.
- Simpan konfigurasi lokal dan kredensial hanya di `.env`. Jangan membagikan atau mengunggah file `.env`.
- Jika konfigurasi lama masih tersimpan setelah perubahan `.env`, jalankan `php artisan config:clear`.

## API nasabah

Endpoint API didefinisikan di `routes/api.php` dan secara default menggunakan awalan `/api`. Endpoint yang tersedia meliputi:

| Kelompok | Endpoint utama | Kegunaan |
|---|---|---|
| Autentikasi | `POST /api/customer/login`, `POST /api/customer/logout` | Login dan logout nasabah |
| Pemulihan akun | `POST /api/customer/forgot-password`, `POST /api/customer/reset-password` | Pemulihan kata sandi dengan OTP |
| Profil dan rekening | `GET/PUT /api/customer/profile`, `GET /api/customer/dashboard`, `GET /api/customer/balance`, `GET /api/customer/barcode` | Data profil, dashboard, saldo, dan barcode |
| Transaksi | `GET /api/customer/transactions`, `GET /api/customer/transactions/{id}` | Riwayat dan detail transaksi |
| Status bank dan penarikan | `GET /api/customer/bank-status`, `GET/POST /api/customer/withdrawal-appointments` | Status bank dan pengajuan janji penarikan |
| Persetujuan PIN | `GET /api/customer/withdrawal-pin-requests/pending`, `POST /api/customer/withdrawal-pin-requests/{id}/verify`, `POST /api/customer/withdrawal-pin-requests/{id}/decline` | Melihat, memverifikasi, atau menolak permintaan PIN |
| Pemulihan PIN | `POST /api/customer/forgot-pin`, `POST /api/customer/reset-pin` | Mengirim OTP dan memperbarui PIN |

Kecuali endpoint login dan pemulihan kata sandi, endpoint nasabah menggunakan autentikasi `auth:sanctum`. Batas permintaan (throttle) juga diterapkan pada endpoint autentikasi, OTP, dan beberapa tindakan sensitif. Lihat halaman `/api-docs` atau `routes/api.php` untuk daftar dan detail terbaru.

## Pengujian

Jalankan seluruh pengujian PHPUnit/Laravel dengan:

```powershell
php artisan test
```

Test suite menggunakan SQLite in-memory sesuai konfigurasi `phpunit.xml`, sehingga pengujian tidak memerlukan database lokal terpisah.
