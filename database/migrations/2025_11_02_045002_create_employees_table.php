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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('staff_number')->unique();
            $table->string('employee_name');
            $table->string('designation');
            $table->string('qualification')->nullable();
            $table->string('pp_status')->nullable();

            // Contact Information
            $table->string('uae_contact')->nullable();
            $table->string('home_country_contact')->nullable();

            // Personal Information
            $table->date('date_of_birth')->nullable();
            $table->integer('current_age')->nullable();

            // Employment Details
            $table->date('duty_joined_date')->nullable();
            $table->date('duty_end_date')->nullable();
            $table->integer('duty_days')->nullable();
            $table->decimal('duty_years', 5, 2)->nullable();
            $table->date('last_vacation_date')->nullable();

            // Salary Information
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('allowance', 10, 2)->default(0);
            $table->decimal('fixed_salary', 10, 2)->default(0);
            $table->decimal('total_salary', 10, 2)->default(0);
            $table->decimal('recent_increment_amount', 10, 2)->nullable();
            $table->date('increment_date')->nullable();

            // Document Expiry Dates
            $table->date('passport_expiry_date')->nullable();
            $table->date('visit_expiry_date')->nullable();
            $table->date('visa_expiry_date')->nullable();
            $table->date('eid_expiry_date')->nullable();
            $table->date('health_insurance_expiry_date')->nullable();
            $table->date('driving_license_expiry_date')->nullable();

            // Other Details
            $table->string('salary_card_details')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['active', 'inactive', 'vacation', 'terminated'])->default('active');

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
