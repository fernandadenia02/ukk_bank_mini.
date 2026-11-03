<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_pin_reset_otps')) {
            Schema::table('customer_pin_reset_otps', function (Blueprint $table) {
                $table->index(
                    ['customer_id', 'used_at'],
                    'customer_pin_reset_otps_customer_used_index'
                );
            });

            return;
        }

        Schema::create('customer_pin_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->string('otp_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
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
        Schema::dropIfExists('customer_pin_reset_otps');
    }
};
