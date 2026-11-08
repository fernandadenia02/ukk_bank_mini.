<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('withdrawal_date');
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'withdrawal_date']);
            $table->index(['customer_id', 'withdrawal_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_appointments');
    }
};
