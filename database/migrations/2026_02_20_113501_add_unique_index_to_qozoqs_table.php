<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qozoqs', function (Blueprint $table) {
            $table->unique(
                ['boundary_name', 'car_number', 'date_and_time'],
                'qozoqs_unique_record'
            );
        });
    }

    public function down(): void
    {
        Schema::table('qozoqs', function (Blueprint $table) {
            $table->dropUnique('qozoqs_unique_record');
        });
    }
};
