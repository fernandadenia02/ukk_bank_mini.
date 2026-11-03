```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('password_reset_otps')) {
            return;
        }

        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();

            // ID nasabah yang meminta reset password.
            // customers.id bertipe INT.
            $table->unsignedInteger('customer_id');

            // OTP disimpan dalam bentuk hash.
            $table->string('otp_hash');

            // Batas waktu OTP berlaku.
            $table->timestamp('expires_at');

            // Jumlah percobaan memasukkan OTP.
            $table->unsignedTinyInteger('attempts')->default(0);

            // Waktu OTP digunakan.
            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();

            $table->index(['customer_id', 'used_at']);
        });
    }

    public function down(): void
    {
        // Tidak menghapus tabel secara otomatis.
    }
};
