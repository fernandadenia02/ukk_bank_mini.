```php
<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel users sudah ditangani oleh migration utama.
        // Jangan membuat tabel users untuk kedua kalinya.
    }

    public function down(): void
    {
        // Tidak menghapus tabel users yang sudah digunakan aplikasi.
    }
};