# Style Reference

Ringkasan ini memisahkan nilai yang terbaca langsung dari kode dengan keterangan pemakaian. Nilai CSS adalah bukti deklarasi, bukan hasil pengukuran screenshot.

## Kerangka dan urutan stylesheet

### Layout aplikasi (`layouts.app`)

Urutan yang tercantum di layout:

1. Bootstrap CSS 5.3.8 dari jsDelivr.
2. Bootstrap Icons 1.13.1 dari jsDelivr.
3. CSS inline layout untuk `body`, sidebar, topbar, konten, kartu, tabel, dan breakpoint.
4. `public/css/bank-mini-ui.css` melalui `asset('css/bank-mini-ui.css')`.

Setelah markup navigasi, layout memuat script inline untuk buka/tutup sidebar, kemudian Bootstrap bundle 5.3.8 dari jsDelivr.

### Layout autentikasi (`layouts.auth`)

Bootstrap CSS 5.3.8, lalu `public/css/bank-mini-ui.css`; Bootstrap bundle 5.3.8 dimuat setelah konten.

### Welcome page

`welcome.blade.php` menggunakan `@vite(['resources/css/app.css', 'resources/js/app.js'])` jika manifest build atau file hot tersedia. Jika tidak, halaman memakai CSS Tailwind v4 yang tertanam dalam `<style>`. Halaman welcome tidak disertakan sebagai halaman aplikasi; CSS fallback-nya tetap diekstrak.

`resources/css/app.css` memuat `@import 'tailwindcss'` dan mengatur tema font Tailwind. Layout aplikasi/autentikasi tidak memuat `@vite` pada markup yang diperiksa, jadi CSS tersebut tidak diasumsikan aktif pada semua halaman role.

## Font dan ikon

- Font stack yang dideklarasikan di `bank-mini-ui.css`: `Inter`, `Segoe UI`, `Roboto`, `Arial`, `sans-serif`. Deklarasi tidak membuktikan font Inter tersedia secara lokal.
- Tema Tailwind di `resources/css/app.css`: `Instrument Sans`, lalu fallback UI sans/system dan emoji.
- Vite mengonfigurasi helper Bunny Fonts untuk `Instrument Sans` dengan weight 400, 500, dan 600. File font tidak ditemukan sebagai aset lokal.
- Ikon halaman memakai Bootstrap Icons dengan class `bi bi-*`; stylesheet ikon eksternal tercatat pada `DEPENDENCIES.md`.

## Warna dan tipografi yang dideklarasikan

### Token `bank-mini-ui.css`

| Token | Nilai CSS |
|---|---|
| `--bm-primary` | `#175cd3` |
| `--bm-primary-dark` | `#1249a8` |
| `--bm-ink` | `#172b4d` |
| `--bm-muted` | `#66758a` |
| `--bm-canvas` | `#f4f7fb` |
| `--bm-surface` | `#ffffff` |
| `--bm-border` | `#e3eaf3` |
| `--bm-sidebar` | `#122642` |
| `--bm-radius` | `16px` |
| `--bm-shadow` | `0 8px 24px rgba(23, 43, 77, 0.07)` |

`body` memakai token warna canvas dan font stack Inter/Segoe UI/Roboto/Arial. Judul `h2` utama memakai `font-size: clamp(1.45rem, 2vw, 1.9rem)` dan `font-weight: 750`.

### Nilai lain dari inline CSS/layout

- CSS inline awal layout menetapkan body `#f4f6f9`, sidebar `#0d6efd`, topbar putih, dan shadow `0 2px 8px rgba(0,0,0,.08)`.
- Stylesheet lokal yang ditautkan sesudah CSS inline menetapkan ulang beberapa komponen, termasuk body, sidebar, topbar, kartu, tombol, dan formulir.
- Kartu khusus customer dan transaksi menetapkan radius `16px`, `15px`, dan `14px` pada selector masing-masing.
- Receipt adalah halaman mandiri dengan `Arial`, background `#f2f2f2`, panel putih, border abu-abu, dan warna tombol cetak/kembali/lanjut yang ditentukan di CSS receipt.

## Komponen

- **Navbar/topbar:** `.topbar`, `.topbar-title`, `.user-info`; sticky di bagian atas pada stylesheet bersama.
- **Sidebar:** `.sidebar`, `.sidebar a`, `.sidebar a.active`, `.sidebar-close`, `.sidebar-overlay`, `.menu-button`; menu ditampilkan menurut role/session di layout aplikasi.
- **Konten:** `.content`, `.main-content`, lebar maksimum konten 1720px pada stylesheet bersama.
- **Kartu/statistik:** Bootstrap `.card`, `.card-header`, `.card-body`; dashboard memakai grid Bootstrap `.row`, `.col-*`, serta shadow.
- **Tabel:** Bootstrap `.table`, `.table-hover`, `.table-bordered`, `.table-dark`, `.table-responsive`; tabel lebar dapat scroll horizontal.
- **Formulir:** Bootstrap `.form-control`, `.form-select`, `.input-group`, `.form-label`; stylesheet lokal menetapkan tinggi minimum dan gaya fokus.
- **Tombol:** Bootstrap `.btn` varian primary/success/danger/secondary/warning/outline; border radius 9px pada stylesheet lokal.
- **Status/notifikasi:** Bootstrap `.badge`, `.alert`; status menggunakan varian warna Bootstrap.
- **Modal:** Bootstrap `.modal-content` menerima radius dan shadow stylesheet lokal. Tidak ada modal khusus yang ditemukan dalam stylesheet sumber ini.
- **Receipt:** selector lokal `.receipt`, `.header`, `.title`, `.amount`, `.buttons`; style print menyembunyikan kontrol cetak.

## Responsive dan print

- Layout inline: breakpoint `max-width: 767.98px` untuk sidebar mobile, tombol menu, overlay, konten penuh, dan topbar; breakpoint `max-width: 400px` untuk layar kecil.
- Stylesheet bersama: breakpoint `max-width: 767.98px` dan `max-width: 420px`.
- Halaman transaksi memiliki breakpoint `max-width: 576px`; dashboard customer juga memiliki breakpoint `max-width: 767.98px`.
- Bootstrap menyediakan grid/utilitas responsif; versi persis CSS Bootstrap tidak tersedia sebagai file lokal.
- CSS inline laporan bulanan dan receipt memiliki aturan `@media print`.

## Pemetaan role ke layout dan halaman

Semua view role yang berisi markup aplikasi menggunakan `layouts.app`; halaman login/reset password menggunakan `layouts.auth`.

| Role / area | Halaman yang disertakan |
|---|---|
| Admin | `admin/dashboard`; `users/index`, `users/create`, `users/edit`; `customers/index`, `customers/create`, `customers/edit`, `customers/show`; `members/index`, `members/create`, `members/promotion`; `accounting_journal/index`; `monthly_reports/index`; `daily_reports/index`, `daily_reports/show` |
| Teller | `teller/dashboard`; `members/index`, `members/activate`; `transactions/index`, `transactions/create`, `transactions/receipt`; `daily_reports/index`, `daily_reports/show` |
| Supervisor | `supervisor/dashboard`; `daily_reports/index`, `daily_reports/show` |
| Customer | `customer/dashboard` |
| Autentikasi | `auth/login`, `auth/forgot-password`, `auth/reset-password` |

Daftar ini adalah pemetaan view yang tersedia, bukan jaminan semua route atau alur runtime berfungsi. View kosong atau non-role dicatat terpisah di `SOURCE_MAP.csv`.

## Sumber visual yang terbatas

- Bootstrap dan Bootstrap Icons hanya direferensikan melalui CDN; salinan lokal yang tepat tidak ditemukan.
- Tidak ada font lokal yang ditemukan. Font CDN yang mungkin diunduh melalui Vite tidak tersedia dalam paket.
- QR rekening yang dirujuk oleh view tidak diekspor untuk menghindari menyertakan pengenal rekening/customer.
- Browser tidak tersedia untuk tugas ini dan aplikasi tidak dijalankan; tidak ada screenshot runtime.
