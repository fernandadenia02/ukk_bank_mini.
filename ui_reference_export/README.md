# UI Reference Export - Bank Mini / E-Teller

Paket ini berisi snapshot sumber tampilan untuk membantu peninjauan dan pembuatan mockup. File Blade, CSS, dan JavaScript di dalam paket adalah salinan dari file yang sedang ada di working tree saat ekstraksi, bukan hasil render browser.

## Isi paket

- `css/`: 2 file CSS first-party dan 7 blok `<style>` Blade yang diekstrak.
- `blade/layouts/`: snapshot layout utama dan autentikasi yang berisi markup.
- `blade/pages/`: 25 halaman Blade berisi markup untuk role dan fitur aplikasi.
- `js/`: snapshot JavaScript first-party dan 4 blok script inline.
- `assets/README.md`: catatan aset yang sengaja tidak disertakan.
- `screenshots/README.md`: alasan screenshot tidak tersedia.
- `CSS_INVENTORY.csv`: inventaris stylesheet lokal, inline, dan CDN.
- `CSS_INLINE_REFERENCE.md`: lokasi blok CSS dan seluruh atribut `style=`.
- `DEPENDENCIES.md`: framework dan sumber aset eksternal.
- `STYLE_REFERENCE.md`: ringkasan gaya berdasarkan kode.
- `SOURCE_MAP.csv`: pemetaan hasil ekspor ke sumber asli.

## Batasan dan pengecualian

- Bootstrap CSS/JS, Bootstrap Icons, dan html5-qrcode berasal dari CDN. File vendor tidak tersedia secara lokal, sehingga paket hanya mencatat URL dan versi yang terlihat; tidak menyalin isi CDN.
- `resources/js/app.js` hanya berisi komentar, tetapi disertakan sebagai snapshot sumber.
- Layout `admin`, `teller`, `supervisor`, `customer`, `journal`, `report`, dan `transaction` yang terpisah, dua view transaksi `deposit`/`withdrawal`, serta `public/favicon.ico` berukuran 0 byte tidak disalin sebagai file kosong. Statusnya dicatat di `SOURCE_MAP.csv`.
- `welcome.blade.php` dan `api-docs.blade.php` tidak disalin sebagai halaman; blok CSS inline keduanya diekstrak karena termasuk sumber CSS yang ditemukan.
- File di `public/qrcode/` tidak disalin karena merupakan QR rekening yang dapat mengodekan pengenal rekening/customer.
- Tidak ada file `.env`, kredensial, token, data nasabah, database, `vendor`, `node_modules`, atau cache dalam paket.
- Screenshot tidak diambil: tidak ada halaman browser yang dibagikan dan aplikasi tidak dijalankan agar tidak memicu operasi runtime atau perubahan data.
- Isi mencerminkan working tree yang sudah memiliki perubahan lokal sebelum ekstraksi. Ekstraksi ini tidak mengubah file sumber aplikasi.

Lihat `DEPENDENCIES.md` dan `CSS_INVENTORY.csv` untuk stylesheet eksternal yang tidak dapat disalin secara lokal.
