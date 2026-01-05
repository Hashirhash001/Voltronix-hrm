<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_vacations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->onDelete('cascade')  // Delete vacations when employee deleted
                  ->onUpdate('cascade');

            $table->date('start_date');
            $table->date('end_date');
            $table->text('remarks')->nullable();
            $table->timestamps();

            // REMOVE or COMMENT OUT the unique constraint - it's causing the issue
            // $table->unique(['employee_id', 'start_date', 'end_date'], 'unique_employee_vacation');

            // Just add index for performance
            $table->index(['employee_id', 'start_date', 'end_date'], 'idx_vacation_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_vacations');
    }
};
