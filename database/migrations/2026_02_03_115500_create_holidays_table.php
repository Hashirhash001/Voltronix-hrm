<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('holiday_name');
            $table->date('holiday_date');
            $table->enum('type', ['public', 'optional', 'entity_specific'])->default('public');
            $table->foreignId('entity_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Prevent duplicate holidays for same entity/date
            $table->unique(['holiday_date', 'entity_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('holidays');
    }
};
