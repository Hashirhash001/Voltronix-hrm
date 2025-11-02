<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_expiry_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->enum('document_type', [
                'passport',
                'visa',
                'health_insurance',
                'driving_license',
                'eid',
                'visit_permit'
            ]);
            $table->date('expiry_date');
            $table->integer('days_until_expiry');
            $table->enum('alert_level', ['green', 'yellow', 'red', 'expired'])
                ->default('green');
            $table->boolean('is_notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'document_type']);
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_expiry_alerts');
    }
};
