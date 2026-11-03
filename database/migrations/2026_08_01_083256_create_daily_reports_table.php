```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel daily_reports sudah ada di database.
        if (Schema::hasTable('daily_reports')) {
            return;
        }

        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Tidak menghapus tabel secara otomatis.
    }
};