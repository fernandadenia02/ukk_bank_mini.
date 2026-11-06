<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL dapat menyisakan tabel jika penambahan constraint gagal.
        if (Schema::hasTable('withdrawal_pin_approvals')) {
            return;
        }

        Schema::create('withdrawal_pin_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('teller_id');
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->timestamp('expires_at')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status', 'expires_at']);
            $table->index(['teller_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_pin_approvals');
    }
};
