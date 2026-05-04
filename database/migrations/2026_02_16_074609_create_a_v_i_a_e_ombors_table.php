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
        Schema::create('a_v_i_a_e_ombors', function (Blueprint $table) {
            $table->id();
            $table->string('air_waybill_number')->nullable();
            $table->string('flight_number')->nullable();
            $table->date('registration_date')->nullable();
            $table->string('consignee')->nullable();
            $table->string('total_net_weight')->nullable();
            $table->string('total_weight')->nullable();
            $table->string('border_customs_post_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a_v_i_a_e_ombors');
    }
};
