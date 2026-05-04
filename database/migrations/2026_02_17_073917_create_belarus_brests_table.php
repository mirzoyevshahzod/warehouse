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
        Schema::create('belarus_brests', function (Blueprint $table) {
            $table->id();
            $table->string('call_order')->nullable();
            $table->string('queue_type')->nullable();
            $table->string('car_number')->nullable();
            $table->date('date_of_registration_in_the_zo')->nullable();
            $table->date('status_changed')->nullable();
            $table->string('status')->nullable();
            $table->string('company_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('belarus_brests');
    }
};
