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
        Schema::create('import_eksport_qozoqs', function (Blueprint $table) {
            $table->id();
            $table->string('boundary_name')->nullable();
            $table->string('car_number')->nullable();
            $table->dateTime('date_and_time')->nullable();
            $table->string('status')->nullable();
            $table->string('company_name')->nullable();
            $table->timestamps();

                $table->unique(
                    ['boundary_name', 'car_number', 'date_and_time'],
                    'import_eksport_qozoqs_unique_record'
                );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_eksport_qozoqs');
    }
};
