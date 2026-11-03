<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('daily_reports') && !Schema::hasColumn('daily_reports', 'closed_at')) {
            Schema::table('daily_reports', function (Blueprint $table) {
                $table->timestamp('closed_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('daily_reports') && Schema::hasColumn('daily_reports', 'closed_at')) {
            Schema::table('daily_reports', function (Blueprint $table) {
                $table->dropColumn('closed_at');
            });
        }
    }
};
