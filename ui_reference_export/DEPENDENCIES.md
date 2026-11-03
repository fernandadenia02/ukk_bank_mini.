# Dependencies and External Visual Resources

## CSS/framework

| Resource | Versi/deklarasi | Sumber pemakaian | Status salinan |
|---|---|---|---|
| Bootstrap CSS | 5.3.8 | `layouts/app.blade.php`, `layouts/auth.blade.php` | CDN URL dicatat; tidak ada salinan lokal |
| Bootstrap Icons | 1.13.1 | `layouts/app.blade.php` | CDN URL dicatat; tidak ada salinan lokal |
| Tailwind CSS | `^4.0.0` pada `package.json`; fallback welcome mengidentifikasi `v4.0.7` | `resources/css/app.css`, `vite.config.js`, conditional welcome page | Import/source disalin; tidak ada output build atau `node_modules` |
| `@tailwindcss/vite` | `^4.0.0` | `vite.config.js` | Versi dependency manifest, bukan versi terpasang |
| Vite | `^8.0.0` | `package.json`, `vite.config.js` | Versi dependency manifest |
| `laravel-vite-plugin` | `^3.1` | `vite.config.js` | Versi dependency manifest |

## JavaScript/UI

| Resource | URL/deklarasi | Keterangan |
|---|---|---|
| Bootstrap bundle | `https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js` | Dimuat oleh app dan auth layout |
| html5-qrcode | `https://unpkg.com/html5-qrcode` | Script kamera/QR pada halaman transaksi; versi tidak dipin di URL |
| JavaScript first-party | `resources/js/app.js` | Snapshot disalin; hanya berisi komentar `//` |
| Script inline Blade | Lihat `js/` dan `SOURCE_MAP.csv` | Empat blok UI diekstrak dari layout app, dashboard teller, halaman transaksi, dan halaman aktivasi anggota |

## Font

- CSS app menentukan tema `Instrument Sans` melalui helper `bunny()` di Vite untuk weights 400, 500, dan 600.
- `bank-mini-ui.css` menentukan stack `Inter`, `Segoe UI`, `Roboto`, `Arial`, `sans-serif`.
- Tidak ada file font lokal yang ditemukan. Font yang harus diunduh saat build/browser tidak disalin.

## Aset visual

- `public/qrcode/*.svg` tidak disalin karena QR dapat membawa pengenal rekening/customer.
- `public/favicon.ico` ditemukan berukuran 0 byte dan tidak diekspor sebagai file kosong.
- Tidak ditemukan logo/gambar/font first-party lain yang layak disalin dalam inventaris file asset.
- Lihat `assets/README.md` untuk catatan pengecualian.

Tidak ada stylesheet atau script CDN yang diunduh. URL di atas merepresentasikan dependensi yang terlihat dalam sumber pada saat ekstraksi, bukan konfirmasi ketersediaan layanan eksternal.
