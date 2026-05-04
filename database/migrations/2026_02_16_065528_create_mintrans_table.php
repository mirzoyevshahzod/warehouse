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
        Schema::create('mintrans', function (Blueprint $table) {
            $table->id();
            $table->string('model')->nullable();
            $table->string('load_capacity')->nullable();
            $table->string('license_number')->nullable();
            $table->string('state_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('type_of_activity')->nullable();
            $table->string('transport_type')->nullable();
            $table->string('cargo_type')->nullable();
            $table->date('date_given')->nullable();
            $table->date('validity_period')->nullable();
            $table->string('status')->nullable();
            $table->string('inn')->nullable();
            $table->string('territorial_management')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mintrans');
    }
};
