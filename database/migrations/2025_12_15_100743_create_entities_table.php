<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('entity_name');
            $table->text('entity_description')->nullable();

            // Document expiry dates
            $table->date('trade_license_renewal_date')->nullable();
            $table->date('est_card_renewal_date')->nullable();
            $table->date('warehouse_ejari_renewal_date')->nullable();
            $table->date('camp_ejari_renewal_date')->nullable();
            $table->date('workman_insurance_expiry_date')->nullable();

            // Document files
            $table->string('trade_license_document')->nullable();
            $table->string('est_card_document')->nullable();
            $table->string('warehouse_ejari_document')->nullable();
            $table->string('camp_ejari_document')->nullable();
            $table->string('workman_insurance_document')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
