<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eksport faylida deklarant ham yo'q — xodim kabi firma (STIR) bo'yicha qo'lda kiritiladi
     * ("Xodim qo'shish" oynasining "Deklarant" tabi). Alohida jadvalda saqlanadi (keyingi yuklangan
     * fayllarga ham avtomatik qo'yilishi uchun) va shu firmaning barcha qatorlariga yoziladi.
     */
    public function up(): void
    {
        Schema::create('uzbekistan_exporter_declarants', function (Blueprint $table) {
            $table->id();
            $table->string('exporter_inn', 20)->unique();
            $table->string('exporter_name')->nullable();
            $table->string('full_name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->foreignId('user_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('uzbekistan_exports', function (Blueprint $table) {
            $table->string('declarant_name')->nullable()->after('responsible_person_phone');
            $table->string('declarant_phone', 30)->nullable()->after('declarant_name');
            $table->string('declarant_email')->nullable()->after('declarant_phone');
            $table->string('declarant_company')->nullable()->after('declarant_email');
        });
    }

    public function down(): void
    {
        Schema::table('uzbekistan_exports', function (Blueprint $table) {
            $table->dropColumn(['declarant_name', 'declarant_phone', 'declarant_email', 'declarant_company']);
        });

        Schema::dropIfExists('uzbekistan_exporter_declarants');
    }
};
