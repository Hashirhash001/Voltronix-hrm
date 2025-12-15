<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_number')->unique();
            $table->string('vehicle_name');
            $table->string('vehicle_plate_number');
            $table->string('assigned_to')->nullable();
            $table->string('under_company')->nullable();

            // Mulkiya (Vehicle Registration)
            $table->date('mulkiya_expiry_date')->nullable();
            $table->string('mulkiya_document')->nullable();

            // Driving License
            $table->date('driving_license_expiry_date')->nullable();
            $table->string('driving_license_document')->nullable();

            // Insurance
            $table->string('insurance')->nullable();

            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
