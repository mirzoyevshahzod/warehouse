<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new  class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('turkeys', function (Blueprint $table) {
            $table->id();
            $table->integer('ordinal_number')->nullable();
            $table->integer('input_sequence_number')->nullable();
            $table->string('car_number')->nullable();
            $table->date('date')->nullable();
            $table->string('entrance')->nullable();
            $table->string('company_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turkeys');
    }
};
