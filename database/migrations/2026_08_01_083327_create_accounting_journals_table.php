```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel accounting_journals sudah ada.
        if (Schema::hasTable('accounting_journals')) {
            return;
        }

        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Tidak menghapus tabel secara otomatis.
    }
};