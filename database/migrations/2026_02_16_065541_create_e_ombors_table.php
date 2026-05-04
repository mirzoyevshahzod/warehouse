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
        Schema::create('e_ombors', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->nullable();
            $table->string('custom_code')->nullable();
            $table->date('custom_date')->nullable();
            $table->string('TEBHN_number')->nullable();
            $table->string('transport_number')->nullable();
            $table->string('gross_weight')->nullable();
            $table->string('inn')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('delivery_post')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('arrival_place')->nullable();
            $table->string('status')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_ombors');
    }
};
