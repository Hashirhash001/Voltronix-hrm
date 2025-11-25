<?php
// Create a new migration file
// database/migrations/YYYY_MM_DD_HHMMSS_fix_duty_years_column.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Change duty_years to allow larger values
            $table->decimal('duty_years', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Revert to original
            $table->decimal('duty_years', 5, 2)->nullable()->change();
        });
    }
};
