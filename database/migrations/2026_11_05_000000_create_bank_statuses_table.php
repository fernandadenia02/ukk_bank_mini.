<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statuses', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('is_open')->default(true);
        });

        DB::table('bank_statuses')->insert([
            'id' => 1,
            'is_open' => true,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statuses');
    }
};
