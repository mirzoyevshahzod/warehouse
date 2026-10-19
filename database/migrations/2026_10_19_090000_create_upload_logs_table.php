<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Dashboard "qachon qancha qo'shildi" so'rovlari tez ishlashi uchun created_at indeksi qo'yiladigan jadvallar */
    private const SOURCE_TABLES = [
        'uzbekistan_imports', 'uzbekistan_exports',
        'qozoqs', 'checked_qozoqs', 'import_qozoqs', 'import_eksport_qozoqs',
        'belarus_benyakonis', 'belarus_brests', 'belarus_grigorovschinas', 'belarus_komennis', 'belarus_kozlovichis',
        'e_ombors', 'r_w_e_ombors', 'a_v_i_a_e_ombors',
        'turkeys', 'mintrans', 'drivers',
    ];

    /**
     * Yuklashlar jurnali: qaysi manbaga kim, qachon, qaysi faylni yukladi va natijasi.
     * (O'zbekiston fayllari navbat orqali yuklanadi — ular uzbekistan_import_tasks'da.)
     */
    public function up(): void
    {
        Schema::create('upload_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 40)->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status', 10);
            $table->unsignedBigInteger('rows_before')->nullable();
            $table->unsignedBigInteger('rows_after')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        foreach (self::SOURCE_TABLES as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'created_at') && ! Schema::hasIndex($name, ['created_at'])) {
                Schema::table($name, fn (Blueprint $table) => $table->index('created_at'));
            }
        }
    }

    public function down(): void
    {
        foreach (self::SOURCE_TABLES as $name) {
            if (Schema::hasTable($name) && Schema::hasIndex($name, ['created_at'])) {
                Schema::table($name, fn (Blueprint $table) => $table->dropIndex(['created_at']));
            }
        }

        Schema::dropIfExists('upload_logs');
    }
};
