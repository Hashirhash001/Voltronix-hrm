<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Education fields only
            $table->year('year_of_completion')->nullable()->after('qualification');
            $table->string('qualification_document')->nullable()->after('year_of_completion');
        });

        // Update status enum to include 'resigned'
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'vacation', 'terminated', 'resigned') DEFAULT 'active'");
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['year_of_completion', 'qualification_document']);
        });

        // Revert status enum
        DB::statement("ALTER TABLE employees MODIFY COLUMN status ENUM('active', 'inactive', 'vacation', 'terminated') DEFAULT 'active'");
    }
};
