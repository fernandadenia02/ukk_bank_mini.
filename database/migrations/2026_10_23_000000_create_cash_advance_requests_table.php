<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_advance_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('daily_report_id');
            $table->integer('teller_id');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('requested_at');
            $table->integer('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'requested_at']);
            $table->foreign('daily_report_id')
                ->references('id')
                ->on('daily_reports')
                ->cascadeOnDelete();
            $table->foreign('teller_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_advance_requests');
    }
};
