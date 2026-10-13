<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eksport faylida mas'ul xodim yo'q — uni foydalanuvchi firma (STIR) bo'yicha qo'lda kiritadi.
     * Xodim alohida jadvalda saqlanadi (keyingi yuklangan fayllarga ham avtomatik qo'yilishi uchun)
     * va shu firmaning barcha qatorlariga ham yoziladi (jadvalda filtr/saralash/export uchun).
     */
    public function up(): void
    {
        Schema::create('uzbekistan_exporter_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('exporter_inn', 20)->unique();
            $table->string('exporter_name')->nullable();
            $table->string('full_name');
            $table->string('position')->nullable();
            $table->string('phone', 30)->nullable();
            $table->foreignId('user_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('uzbekistan_exports', function (Blueprint $table) {
            $table->string('responsible_person_name')->nullable()->after('transportation_method_code');
            $table->string('responsible_person_position')->nullable()->after('responsible_person_name');
            $table->string('responsible_person_phone', 30)->nullable()->after('responsible_person_position');
        });
    }

    public function down(): void
    {
        Schema::table('uzbekistan_exports', function (Blueprint $table) {
            $table->dropColumn(['responsible_person_name', 'responsible_person_position', 'responsible_person_phone']);
        });

        Schema::dropIfExists('uzbekistan_exporter_contacts');
    }
};
